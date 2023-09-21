<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/28/17
 * Time: 01:27
 */

namespace App\Repositories;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\User;
use Illuminate\Support\Facades\Auth;

class DbUserRepository implements UserRepositoryInterface
{
    public function all($with = ['roles', 'license_types', 'locations'])
    {
        return User::with($with)->get();
    }

    public function user()
    {
        return Auth::user();
    }

    public function create($data, $roles = [], $locations = [], $permissions = [])
    {
        $user = User::create($data);
        $user->roles()->attach($roles);
        $user->locations()->attach($locations);
        $user->userPermissions()->attach($permissions);

        if (Auth::user()->hasRole('salesrep')) {
            $user->customer_sales_reps()->attach([Auth::user()->id]);
        }

        $user->save();

        return $user;
    }

    public function buyers($active = 1)
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'buyer');
        })->where('active', $active)->get();
    }

    public function vendors($active = 1)
    {
        $q = User::whereHas('roles', function ($q) {
            $q->where('name', 'vendor');
        })->orderBy('name');

        if(!is_null($active)) {
            $q->where('active', $active);
        }
        return $q;
    }

    public function transporters($active = 1)
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'transporter');
        })->where('active', $active)->get();
    }

    public function all_transporters_with_pickups()
    {
        return User::whereHas('roles', function ($q) {
            $q->where('level', '<=', 10);
        })
            ->withAndWhereHas('batch_pickups', function ($q) {
//                $q->where('status', '=', 'transit');
            });
    }

    public function my_pickups()
    {
        return $this->all_transporters_with_pickups()->where('id', Auth::user()->id);
    }

//    public function customers($active=1)
//    {
//        $custs = User::whereHas('roles', function ($q) {
//            $q->where('name', 'customer');
//        })->orderBy('name');
//
//        if(!is_null($active)) {
//            $custs->where('active', $active);
//        }
//
//        return $custs;
//    }

    public function sales_reps($active = 1)
    {
        return User::whereHas('roles', function ($q) {
            $q->where('name', 'salesrep');
        })->where('active', $active)
            ->orderBy('name');
    }

    public function find($id)
    {
        return User::find($id);
    }
}
