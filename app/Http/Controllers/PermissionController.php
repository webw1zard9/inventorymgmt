<?php

namespace App\Http\Controllers;

use App\Permission;
//use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        view()->share('title', 'Settings / Permissions');

        $permissions = Permission::with(['roles', 'users'])
            ->orderBy('description')->get();

        return view('permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        view()->share('title', 'Settings / Create Permission');

        return view('permissions.create');
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
            return redirect(route('permissions.create'))
                ->withErrors($validator)
                ->withInput($request->all());
        } else {
            try {
                $permission = Permission::create([
                    'name' => $request->get('name'),
                    'description' => $request->get('description'),
                ]);

                flash()->success('Successfully created permission: '.$permission->description);

                return redirect(route('permissions.index'));
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('permissions.create'))
                    ->withInput($request->all());
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        view()->share('title', 'Settings / Edit Permission');

        return view('permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $rules = [
            'name' => 'required|min:2',
            'description' => 'required|min:2',
        ];

        try {
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect(route('permissions.edit', $permission->id))
                    ->withErrors($validator)
                    ->withInput($request->all());
            } else {
                $permission->name = $request->get('name');
                $permission->description = $request->get('description');
                $permission->save();

                flash()->success('Successfully updated permission');

                return redirect(route('permissions.index'));
            }
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('permissions.edit'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        $permission_name = $permission->description;

        try {
            $permission->delete();
            flash()->success($permission_name.' Successfully Deleted!');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return redirect(route('permissions.index'));
    }
}
