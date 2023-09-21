<?php

namespace App\Http\Controllers;

use App\ChartOfAccount;
use App\Location;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CustomersController extends Controller
{
    public function index()
    {
        if (Gate::denies('users.index.customer')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Customers');

        $users = User::customers()
            ->with([
                'roles',
                'userPermissions',
                'journal.ledger',
            ])
            ->orderBy('name')
            ->get();

        $role = 'Customer';

        return view('users.index', compact('users', 'role'));
    }

    public function payment(Request $request, User $user)
    {
        try {
            $payment = request('payment');
            $txn_fee = request('txn_fee');
            $txn_date = request('txn_date');
            $payment_type = request('payment_type');
            $payment_method = request('payment_method');
            $ref_number = request('ref_number');
            $memo = request('memo');
            $location_id = request('location_id');

            if(Auth::user()->active_locations->count() > 1 && !$location_id) {
                throw new \Exception('Payments can only be made from a store!');
            }

            if (! $payment) {
                throw new \Exception('Payment amount required!');
            }

            if ($payment_method == 'Credit') {
                throw new \Exception('Invalid payment method!');
            }

            if (in_array($payment_method, ['BTC', 'ETH']) && ! $ref_number) {
                throw new \Exception('Crypto amount required!');
            }

            if ($payment_type == 'refund') {
                if ($user->available_balance == 0) {
                    throw new \Exception('Customer has no credit to refund!');
                } elseif ($payment > $user->available_balance) {
                    throw new \Exception('Unable to refund more than the customers credit of: '.display_currency($user->available_balance));
                }
            }

            if(!empty($location_id)) {
                $location = Location::whereId($location_id)->withTrashed()->first();
            } else {
                $location = Auth::user()->active_locations->first();
            }

//            $user_location = $user->sale_orders()->first()->location;
            $cash_account = ChartOfAccount::Cash();

            DB::beginTransaction();

            $payment = ($payment_type == 'payment' ? $payment : ($payment * -1));
            $ref_number = ($payment_type == 'payment' ? $ref_number : ($ref_number * -1));
            $txn_fee = ($payment_type == 'payment' ? $txn_fee : ($txn_fee * -1));

            $user->payment($cash_account, $payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, null, null, $location);

            DB::commit();

            flash()->success('Payment received');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('users.show', $user));
    }
}
