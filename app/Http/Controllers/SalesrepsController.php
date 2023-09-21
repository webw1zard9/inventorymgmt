<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Gate;

class SalesrepsController extends Controller
{
    public function index()
    {
        if (Gate::denies('users.index.salesrep')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Sales Reps');

        $users = User::salesrep()->with(['roles', 'locations'])->orderBy('name')->get();
        $role = 'Sales Rep';
        //dd($users);
        return view('users.index', compact('users', 'role'));
    }
}
