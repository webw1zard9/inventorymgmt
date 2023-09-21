<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Gate;

class SauceController extends Controller
{
    public function index()
    {
        if (Gate::denies('users.index.sauce')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Sauce');

        $users = User::sauce()->with(['roles', 'locations'])->orderBy('name')->get();
        $role = 'Sauce';
        //dd($users);
        return view('users.index', compact('users', 'role'));
    }
}
