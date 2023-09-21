<?php

namespace App\Http\Controllers;

use App\Repositories\DbUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    protected $user;

    /**
     * ProfileController constructor.
     *
     * @param  UserRepository  $user
     */
    public function __construct(DbUserRepository $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function index()
    {
        $user = $this->user->user();

        return view('profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'required|regex:/^(\d{3})-(\d{3})-(\d{4})$/',
            'password' => 'sometimes|nullable|min:6|confirmed',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'phone.regex' => 'Phone format: 310-555-1234',
        ]);

        if ($validator->fails()) {
            return redirect(route('profile'))
                ->withErrors($validator)
                ->withInput($request->except(['password', 'password_confirmation']));
        } else {
            $user->name = $request->get('name');
            $user->email = $request->get('email');
            $user->phone = $request->get('phone');
            $user->details = $request->get('details');
            if ($password = $request->get('password')) {
                $user->password = $password;
            }
            $user->pin = $request->get('pin');
            $user->save();

            flash()->success('Successfully updated your profile');

            return redirect(route('profile'));
        }
    }
}
