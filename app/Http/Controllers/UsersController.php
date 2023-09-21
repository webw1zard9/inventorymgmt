<?php

namespace App\Http\Controllers;

use App\Events\UserCreated;
use App\Filters\PurchaseOrderFilters;
use App\Location;
use App\Permission;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UsersController extends Controller
{
//    protected $roles = null;
    protected $permissions = null;

    protected $active_role = null;

    public function __construct()
    {
        parent::__construct();

        $this->permissions = Permission::orderBy('name')->get();

        $this->can_change_role = (request('role') != 'Admin' && ! request('role_id') ? false : true);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  UserRepositoryInterface  $userRepositoryInterface
     * @return \Illuminate\Http\Response
     */
    public function index(UserRepositoryInterface $userRepositoryInterface)
    {
        if (Gate::denies('users.index')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'All Users');

        $users = $userRepositoryInterface->all();

        $role = 'Admin';

        return view('users.index', compact('users', 'role'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            if (! $this->hasAccess('create')) {
                return redirect('/');
            }

            $roles = $this->getAvailableRoles()->reject(fn ($role) => (! Auth::user()->isSuperAdmin() && $role->name == 'admin'));
            $this->locations = Location::orderBy('name')->get();

            return view('users.create')
                ->with('roles', $roles)
                ->with('locations', $this->locations)
                ->with('permissions', $this->permissions)
                ->with('active_role', $this->active_role)
                ->with('can_change_role', $this->can_change_role);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, UserRepositoryInterface $userRepositoryInterface)
    {
        if (! $this->hasAccess('create')) {
            return redirect('/');
        }

        try {
            $rules = [
                'name' => 'required|min:2',
                'role_id' => 'required',
                'email' => 'sometimes|nullable|email',
            ];

            $messages = [
                'name.required' => 'Full name is required',
                'role_id.required' => 'A Role is requried',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            // process the login
            if ($validator->fails()) {
                return redirect(route('users.create', $request->getQueryString()))
                    ->withErrors($validator)
                    ->withInput($request->except('password'));
            } else {
                // store

                DB::beginTransaction();

                $role = $request->get('role');
                if ($role == 'Admin') {
                    $role = 'users';
                }

                if (! $role) {
                    $role = Role::find($request->get('role_id'))->name;
                }
//                    dump($role);
//                dd($request->all());
                $request_data = request(['name', 'email', 'phone', 'password', 'details', 'pin']);
                if (empty($request_data['phone'])) {
                    $request_data['phone'] = '0000000000';
                }
                if (empty($request_data['email'])) {
                    $request_data['email'] = str_random(4).'@'.str_random(4).'.la';
                }
                if (empty($request_data['password'])) {
                    $request_data['password'] = str_random(16);
                }

                if (! isset($request_data['details'])) {
                    $request_data['details'] = ['address' => null, 'address2' => null, 'terms' => null];
                }

                if (Auth::user()->hasRole('salesrep') && Auth::user()->sales_rep_customers()->where('name', $request_data['name'])->count()) {
                    throw new \Exception('Customer with this name already exists. Try again.');
                } elseif ($role == 'Customer' && User::customers()->where('name', $request_data['name'])->count()) {
                    throw new \Exception('Customer with this name already exists. Try again.');
                }

                //            dd($request->all());
                $locations = request('locations');
                if (in_array($role, ['Location Manager', 'Sales Rep', 'Sauce']) && empty($locations)) {
                    throw new \Exception('Select a location');
                }

                $user = $userRepositoryInterface->create(
                        $request_data,
                        [$request->get('role_id')],
                        $locations,
                        $request->get('permissions')
                    );

                event(new UserCreated($user));

                flash()->success('Successfully created user: '.$user->name);

                $route_redir = Str::plural(Str::lower(str_replace(' ', '', $role))).'.index';

                if ($request->has(['route'])) {
                    $params = [];
                    if ($request->has('batch_id')) {
                        $params['batch'] = $request->get('batch_id');
                        $params['user'] = $user;
                    } elseif ($request->get('route') == 'purchase-orders.create') {
                        $params[] = $user;
                    }

                    $redir = route($request->get('route'), $params);
                } else {
                    $redir = route($route_redir);
                }

                DB::commit();

                return redirect($redir);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect(route('users.create', $request->getQueryString()))
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function show(User $user, PurchaseOrderFilters $purchaseOrderFilters)
    {
//        dump(Gate::denies('users.view'));
//        dd(Gate::denies('users.view.customer'));
        if (Gate::denies('users.view') && Gate::denies('users.view.customer')) {
            flash('Access Denied!')->error();

            return back();
        }

        $user->load([
            //            'journal.transactions.location',
            //            'journal.transactions.user',
            'journal.transactions' => function ($q) {
                $q->orderBy('post_date', 'desc');
            },
            'sale_orders' => function ($q) {
                $q->orderBy('txn_date', 'desc')->withTrashed();
            },
            'sale_orders.transactions.user',
            'sale_orders.transactions.sale_order' => function ($q) {
                $q->withTrashed();
            },
            'sale_orders.transactions.location',
            'sale_orders.journal',
            'sale_orders.location',
            'sale_orders.sales_rep',
            'sales_rep_orders' => function ($q) {
                $q->orderBy('ref_number', 'desc')->withTrashed();
            },
            'customer_sales_reps',
            'sales_rep_customers' => function ($q) {
                $q->orderBy('name');
            },
        ]);

        $sale_orders = collect();
        if ($user->hasRole(['customer'])) {
            $sale_orders = $user->sale_orders;
        }

        return view('users.show', ['user' => $user, 'sale_orders' => $sale_orders]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function edit(User $user)
    {
        $this->active_role = $user->roles->first();
        $all_permissions = $this->permissions->sortBy('description');

        if (! $this->hasAccess('edit')) {
            return redirect('/');
        }

        if (! $user->can_edit_super_user) {
            flash('Access Denied!')->error();

            return redirect(route('users.index'));
        }

        $this->locations = Location::orderBy('name')->withTrashed()->get();
        $roles = $this->getAvailableRoles();

        return view('users.edit', compact('user'))
            ->with('roles', $roles)
            ->with('locations', $this->locations)
            ->with('permissions', $all_permissions)
            ->with('active_role', $this->active_role)
            ->with('can_change_role', $this->can_change_role);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User  $user
     * @return \Illuminate\Http\Response
     *
     * @internal param int $id
     */
    public function update(Request $request, User $user)
    {
        try {
            $this->active_role = $user->roles->first();

            if (! $this->hasAccess('edit')) {
                return redirect('/');
            }

            $rules = [
                'name' => 'required|min:2',
                'email' => 'required|email|unique:users,email,'.$user->id,
                'phone' => 'required|regex:/^(\d{3})-(\d{3})-(\d{4})$/',
                'password' => 'sometimes|nullable|min:6|confirmed',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect(route('users.edit', $user->id))
                    ->withErrors($validator)
                    ->withInput($request->except(['password', 'password_confirmation']));
            } else {
                $user->name = $request->get('name');
                $user->email = $request->get('email');
                $user->phone = $request->get('phone');
                $user->details = $request->get('details');
                $user->active = $request->get('active');
                $user->pin = $request->get('pin');
                if ($password = $request->get('password')) {
                    $user->password = $password;
                }
                //dd($user);
                if (Auth::user()->isAdmin()) {
                    $user->roles()->sync([$request->get('role_id')]);
                    $user->locations()->sync($request->get('locations'));
                    $user->userPermissions()->sync($request->get('permissions'));
                }

                $user->save();

                $role = ($this->active_role->name != 'admin') ? $this->active_role->name : 'users';
                flash()->success('Successfully updated user');

                $route_redir = Str::plural(Str::lower(str_replace(' ', '', $role))).'.index';

                return redirect(route($route_redir));
                //            return (Session::has('redirect_to') ? redirect(Session::get('redirect_to')) : back() );
            }
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return redirect(route('users.edit', $user->id))
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if (Gate::denies('users.delete.customer')) {
            flash('Access Denied!')->error();

            return back();
        }

        try {
            DB::beginTransaction();
            if ($user->journal) {
                $user->journal->delete();
            }
            $user->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Error deleting user.');
        }

        return back();
    }

    public function forceLogin(User $user)
    {
        Auth::logout();
        Session::flush();
        Auth::login($user);

        return redirect('/');
    }

    public function getAvailableRoles()
    {
        $roles_qry = Role::orderBy('level', 'desc');
        if (Auth::user()->hasRole('salesrep')) {
            $roles_qry->where('name', 'customer');
        }

        return $roles_qry->get();
    }

    public function hasAccess($action = 'index')
    {
        if (! $this->active_role) {
            if (request('role')) {
                $this->active_role = Role::where('description', request('role'))->first();
            } else {
                $this->active_role = Role::find(request('role_id'));
            }
        }

        $ability = 'users.'.$action.'.'.Str::slug($this->active_role->name, '');
        //dd($ability);
        if (! Auth::user()->isAdmin() && ! Auth::user()->hasPermission($ability)) { //customer
            flash('Access Denied!')->error();

            return false;
        }

        return true;
    }
}
