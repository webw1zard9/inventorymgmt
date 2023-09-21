<?php

namespace App\Http\Livewire\SaleOrder;

use App\ChartOfAccount;
use App\Events\SaleOrderDelivered;
use App\SaleOrder;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Scottlaurent\Accounting\Services\Accounting;

class Show extends Component
{
    public SaleOrder $saleOrder;
    public $discount_type;
    public $discount_applied;

    public $addItemButtonDisabled = '';
    public $deliver_conf_message='';

    protected $listeners = [
        'orderDetailUpdated',
        'addItemModalClosed' => '$refresh',
        'allItemsFulfilled',
    ];

    protected $rules = [
        'saleOrder.discount_type' => '',
        'saleOrder.discount_applied' => '',
        'saleOrder.discount_description' => '',
        'saleOrder.notes' => '',
    ];

    protected $messages = [
        'saleOrder.discount_applied.required' => 'Discount amount required',
    ];

    public function mount(SaleOrder $saleOrder)
    {
        $this->saleOrder = $saleOrder;

        $this->discount_type = $saleOrder->discount_type;
        $this->discount_applied = display_currency_no_sign($saleOrder->discount_applied);
//        $this->discount_description = $saleOrder->discount_description;

        if(!$this->saleOrder->canAddItems()) {
            $this->addItemButtonDisabled = 'disabled="disabled"';
        }
    }

    public function render()
    {
//        debug($this->saleOrder);

        return view('livewire.sale-order.show');
    }

    public function reverse()
    {

        try {

            $this->saleOrder->order_details()->update(['units_accepted'=>null]);

            DB::beginTransaction();

            switch($this->saleOrder->status) {

                case "ready to pack": //reverse to hold.
                    $this->saleOrder->hold();
                    $this->reset('addItemButtonDisabled');

                    break;

                case "ready for delivery":
                    $this->saleOrder->ready_to_pack();

                    break;

                case "delivered":
                    $transaction_group = Accounting::newDoubleEntryTransactionGroup();

                    $so_total_money = convert_to_cents($this->saleOrder->total, true);
                    if ($this->saleOrder->total) {
                        $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'credit', $so_total_money, null, $this->saleOrder->customer);  // your user journal probably is an income ledger
                        $transaction_group->addTransaction(ChartOfAccount::Revenue()->journal, 'debit', $so_total_money, null, $this->saleOrder->location); // this is an asset ledder
                    }

                    //credit cogs
                    //debit inventory
                    $so_cost_money = convert_to_cents($this->saleOrder->cost, true);
                    if ($this->saleOrder->cost) {
                        $transaction_group->addTransaction(ChartOfAccount::COG()->journal, 'credit', $so_cost_money, null, $this->saleOrder->location);  // your user journal probably is an income ledger
                        $transaction_group->addTransaction(ChartOfAccount::Inventory()->journal, 'debit', $so_cost_money, null, $this->saleOrder->location); // this is an asset ledder
                    }

                    $transaction_group->commit();

                    foreach($this->saleOrder->order_details as $order_detail) {
                        DB::select("SET @batch_id := ?", [$order_detail->batch_id]);
                        DB::select("CALL sync_batch_location_aggregate()");
                    }

                    $this->saleOrder->ready_to_pack();

                    break;

            }

            DB::commit();

            session()->flash('success-message','Order Reversed!');

        } catch(\Exception $e) {
            DB::rollBack();
            session()->flash('error-message', $e->getMessage());
        }

        return redirect(route('sale-orders.show', $this->saleOrder));

    }

    public function readyToPack()
    {
        $this->saleOrder->ready_to_pack();

        session()->flash('success-message','Order Ready to Pack!');

        return redirect(route('sale-orders.show', $this->saleOrder));
//        $this->emit('soStatusChanged');
    }

    public function readyToDeliver()
    {
        try {
            DB::beginTransaction();

            foreach($this->saleOrder->order_details as $order_detail) {
                if(is_null($order_detail->units_fulfilled)) {
                    throw new \Exception('All items must be fulfilled!');
                }
            }

            $this->saleOrder->ready_for_delivery();

            $this->addItemButtonDisabled = 'disabled="disabled"';

            DB::commit();

            session()->flash('success-message','Order Ready for Delivery!');

            return redirect(route('sale-orders.show', $this->saleOrder));

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error-message', $e->getMessage());
        }

    }

//    public function deliverOrder()
//    {
//        $actual_balance = ($this->saleOrder->balance + $this->saleOrder->discount);
//
//        $this->deliver_conf_message = ($actual_balance < 0 ? 'Order is currently over paid by: '.display_currency($actual_balance * -1)."\nThis amount will be credited to the customers profile." : null);
//
//        try {
//            DB::beginTransaction();
//
//            $this->saleOrder->delivered();
//
//            if ($this->saleOrder->balance < 0) { //issue customer credit
//
//                $transaction_group = Accounting::newDoubleEntryTransactionGroup();
//                $transaction_group->addDollarTransaction(ChartOfAccount::PrepaidInventory()->journal, 'debit', abs($this->saleOrder->balance), null, $this->saleOrder->customer);
//                $transaction_group->addDollarTransaction($this->saleOrder->customer->journal, 'credit', abs($this->saleOrder->balance), null, $this->saleOrder);
//                $transaction_group_id = $transaction_group->commit();
//
//                $txn = $this->saleOrder->journal->creditDollars(abs($this->saleOrder->balance));
//                $txn->refresh();
//
//                $this->saleOrder->applyPayment($this->saleOrder->balance, Carbon::now(), 'Credit', null, 'System issued due to over payment.', $txn->acct_journal_txn_pid);
//
//                $this->saleOrder->journal->resetCurrentBalances();
//            }
//
//            event(new SaleOrderDelivered($this->saleOrder));
//
//            DB::commit();
//
//            session()->flash('success-message','Successfully Delivered!');
//
//            return redirect(route('sale-orders.index'));
//
//        } catch (\Exception $e) {
//            DB::rollBack();
//            Bugsnag::notifyException($e);
//            $this->addError('od-error', 'There is an issue with this order and cannot be delivered! Please fix. - '.$e->getMessage());
//        }
//
//    }

    public function applyDiscount()
    {
        $this->validate();

        try {

            DB::beginTransaction();

            $this->saleOrder->discount_applied = abs((float)$this->saleOrder->discount_applied);

            $activity_prop=collect();

            if ($this->saleOrder->discount_applied > 0) {
                if ($this->saleOrder->discount_type == 'perc') {
                    $this->saleOrder->discount = $this->saleOrder->subtotal * ($this->saleOrder->discount_applied / 100);
                    $activity_amount = $this->saleOrder->discount_applied.'%';
                } else {
                    $this->saleOrder->discount_type = 'amt';
                    $this->saleOrder->discount = $this->saleOrder->discount_applied;
                    $activity_amount = display_currency($this->saleOrder->discount);
                }

                if($this->saleOrder->discount > $this->saleOrder->subtotal) {
                    $this->saleOrder->discount = $this->saleOrder->getOriginal('discount');
                    $this->saleOrder->discount_applied = $this->saleOrder->getOriginal('discount_applied');
                    $this->saleOrder->discount_type = $this->saleOrder->getOriginal('discount_type');
                    throw new \Exception("Discount cannot be greater than subtotal");
                }

                if (Auth::user()->hasRole('salesrep')) {
                    $this->saleOrder->discount_approved = 0;
                }

                $activity_prop = collect([
                    'Discount' => $activity_amount,
                    'Amount' => display_currency($this->saleOrder->discount)
                ]);
                $activity_log_name = 'Discount Applied';
                session()->flash('success-message',$activity_log_name);

            } else {
                $this->saleOrder->removeDiscount();

                session()->flash('success-message', 'Discount Removed');

            }

            $this->saleOrder->total = ($this->saleOrder->subtotal - $this->saleOrder->discount);
            $total_change = (float)bcsub($this->saleOrder->total, $this->saleOrder->getOriginal('total'), 2);
            if($total_change < 0) {
                $this->saleOrder->journal->debit($total_change * -100);
            } elseif($total_change > 0) {
                $this->saleOrder->journal->credit($total_change * 100);
            }

            $this->saleOrder->journal->resetCurrentBalances();

            $this->saleOrder->save();

            if(!empty($activity_log_name)) {
                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($this->saleOrder)
                    ->withProperties($activity_prop)
                    ->log($activity_log_name);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error-message', $e->getMessage());
        }
    }

    public function approveDiscount()
    {
        try {
            DB::beginTransaction();

            $this->saleOrder->approveDiscount();

            DB::commit();

            session()->flash('success-message','Discount Approved!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error-message', $e->getMessage());
        }
    }

    public function rejectDiscount()
    {
        try {

            DB::beginTransaction();

            $this->saleOrder->rejectDiscount();

            DB::commit();

            session()->flash('success-message','Discount Rejected!');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error-message', $e->getMessage());
        }
    }

    public function updateSaleOrder()
    {
        $this->saleOrder->save();

        session()->flash('success-message', 'Successfully updated.');
    }

    public function allItemsFulfilled()
    {
        $this->addItemButtonDisabled = 'disabled="disabled"';
        $this->emit('$refresh');
    }

    public function removeAllItems()
    {

        try {

            DB::beginTransaction();

            foreach($this->saleOrder->order_details as $order_detail) {
                $this->saleOrder->removeItem($order_detail);
            }

            $this->saleOrder->removeDiscount();

            $this->saleOrder->refresh();

            $this->saleOrder->calculateTotals();

            DB::commit();

            return redirect(route('sale-orders.show', $this->saleOrder));

        } catch(\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
            return redirect(route('sale-orders.show', $this->saleOrder));
        }
    }

    public function orderDetailUpdated()
    {
        $this->saleOrder->refresh();
        $this->discount_type = $this->saleOrder->discount_type;
        $this->discount_applied = display_currency_no_sign($this->saleOrder->discount_applied);
    }
}
