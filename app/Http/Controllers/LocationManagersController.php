<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Gate;

class LocationManagersController extends Controller
{
    public function index()
    {
        if (Gate::denies('users.index.locationmanager')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Location Managers');

        $users = User::locationManagers()->with(['roles', 'locations'])->orderBy('name')->get();
        $role = 'Location Manager';
//        dd($users);
        return view('users.index', compact('users', 'role'));
    }
}
