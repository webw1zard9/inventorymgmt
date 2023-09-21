<?php

namespace App\Http\Controllers;

use App\License;
use App\LicenseType;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function create(User $user)
    {
        //dd($user);
        if (Gate::denies('users.create')) {
            flash('Access Denied!')->error();

            return back();
        }

        $license_types = LicenseType::orderBy('name')->pluck('name', 'id');

        return view('licenses.create', compact('user', 'license_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        if (Gate::denies('users.create')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'license_type_id' => 'required',
            'number' => 'required',
        ];

        $messages = [
            'license_type_id.required' => 'License type is required.',
            'number.required' => 'License number is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // process the login
        if ($validator->fails()) {
            return redirect(route('users.licenses.create', $user->id))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            $license = License::create($request->input());

            $user->licenses()->save($license);

            flash()->success('Successfully added license for '.$user->name);

            return redirect(route('users.edit', $user->id));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\License  $license
     * @return \Illuminate\Http\Response
     */
    public function show(License $license)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\License  $license
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user, License $license)
    {
        $license_types = LicenseType::orderBy('name')->pluck('name', 'id');

        return view('licenses.edit', compact('user', 'license', 'license_types'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @param  \App\license  $license
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user, License $license)
    {
        if (Gate::denies('users.edit')) {
            flash('Access Denied!')->error();

            return back();
        }

        $rules = [
            'license_type_id' => 'required',
            'number' => 'required',
        ];

        $messages = [
            'license_type_id.required' => 'License type is required.',
            'number.required' => 'License number is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect(route('users.licenses.edit', [$user->id, $license->id]))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            $license->update($request->input());

            flash()->success('Successfully updated license for '.$user->name);

            return redirect(route('users.edit', $user->id));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\license  $license
     * @return \Illuminate\Http\Response
     */
    public function destroy(License $license)
    {
        //
    }
}
