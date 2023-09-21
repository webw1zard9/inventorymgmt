<?php

namespace App\Http\Controllers;

use App\Permission;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Gate::denies('manage.roles')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Settings / Roles');

        $roles = Role::with(['permissions', 'users'])
            ->orderBy('description')->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        view()->share('title', 'Settings / Create Role');

        $permissions = Permission::orderBy('description')->get();

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|min:2',
            'description' => 'required|min:2',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect(route('roles.create'))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            try {
                $role = Role::create([
                    'name' => $request->get('name'),
                    'description' => $request->get('description'),
                ]);

                $role->permissions()->sync($request->get('permissions'));

                flash()->success('Successfully created role: '.$role->description);

                return redirect(route('roles.index'));
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('roles.create'))
                    ->withInput($request->all());
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        view()->share('title', 'Settings / Edit Role');

        $permissions = Permission::orderBy('description')->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $rules = [
            'name' => 'required|min:2',
            'description' => 'required|min:2',
        ];

        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect(route('permissions.edit', $role->id))
                    ->withErrors($validator)
                    ->withInput($request->all());
            } else {
                $role->name = $request->get('name');
                $role->description = $request->get('description');
                $role->save();

                $role->permissions()->sync($request->get('permissions'));

                flash()->success('Successfully updated role');

                DB::commit();

                return redirect(route('roles.index'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect(route('roles.edit'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $role_name = $role->description;

        try {
            $role->delete();
            flash()->success($role_name.' Successfully Deleted!');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return redirect(route('roles.index'));
    }
}
