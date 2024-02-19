@php use Illuminate\Support\Facades\Auth; @endphp

<div class="left side-menu">
    <div class="sidebar-inner slimscrollleft">
        <!--- Divider -->

        <div id="sidebar-menu">
            <ul>
                <li>
                        <a href="{{ route('dashboard') }}" class=""><i class="ti-home"></i><span> Dashboard </span></a>
                </li>

                @can('po.index')
                <li>
                    <a href="{{ route('purchase-orders.index') }}" class=" {{ Request::is('purchase-orders*') ? 'active' : '' }}"><i class="ti-download"></i>
                        <span> Purchase Orders </span></a>
                </li>
                @endcan

                @can('so.index')
                <li>
                    <a href="{{ route('sale-orders.index') }}" class=" {{ Request::is('sale-orders*') ? 'active' : '' }}"><i class="ti-share"></i>
                        <span> Sale Orders </span></a>
                </li>
                @endcan

                @can('batches.index')
                    <li class="has_sub">
                        <a href="javascript:void(0);" class=" {{ Request::is('batches*') ? 'active subdrop' : '' }}"><i class="ti-layers-alt"></i><span> Inventory </span>
                            <span class="menu-arrow"></span></a>

                        <ul class="list-unstyled" style="display: {{ Request::is('batches*') ? 'block' : 'none' }};">
                            @if(Auth::check() && Auth::user()->active_locations->count() > 1)
                            @level(60)
                            <li class=""><a class="" href="{{ route('batches.intake') }}">Intake <span class="badge badge-info">{{ $inventory_count }}</span></a></li>
                            @endlevel
                            @endif
                            <li class=""><a class="" href="{{ route('batches.index') }}">Batches</a></li>

                            @can('batches.reconcile')
                            <li class=""><a class="" href="{{ route('batches.reconcile') }}">Reconcile</a></li>
                            <li class=""><a class="" href="{{ route('batches.reconcile-log') }}">Reconciliation Log</a></li>
                            @endcan
                            <li class=""><a class="" href="{{ route('batches.sold') }}">Archived Batches</a></li>
                        </ul>

                    </li>
                @endcan

                @permission('accounting')

                <li class="has_sub">
                    <a href="javascript:void(0);" class=" {{ Request::is('accounting*') ? 'active subdrop' : '' }} "><i class="ti-money"></i> <span> Accounting </span>
                        <span class="menu-arrow"></span></a>
                    <ul class="list-unstyled" style="display: {{ Request::is('accounting*') ? 'block' : 'none' }};">

                        @permission('accounting.payables')
                            <li class=""><a class="" href="{{ route('accounting.payables_summary') }}">Vendor Payables</a></li>
                        @endpermission

                        @role('admin')
                            <li class=""><a class="{{ Request::is('accounting*') ? 'active' : '' }} " href="{{ route('accounting.transactions-paid') }}">Transactions Paid</a></li>
                        @endrole

                        @can('accounting.dailycloseout')
                            <li class=""><a class="" href="{{ route('accounting.transactions-received') }}">Transactions Received</a></li>
                        @endcan

                         @can('accounting.profitloss')
                            <li class=""><a class="" href="{{ route('accounting.profit_loss') }}">Profit &amp; Loss</a></li>
                        @endcan

                        @can('accounting.inventoryloss')
                        <li class=""><a class="" href="{{ route('accounting.inventory-loss') }}">Inventory Loss</a></li>
                        @endcan
                    </ul>
                </li>

                @endpermission

                @anygate("users.index","users.index.vendor","users.index.customer","users.index.locationmanager","users.index.salesrep","users.index.sauce")

                <li class="has_sub">

                    <a href="javascript:void(0);" class=" {{ Request::is('users*') ? 'active subdrop' : '' }} "><i class="mdi mdi-account-multiple-outline"></i> <span> Users </span>
                        <span class="menu-arrow"></span></a>

                    <ul class="list-unstyled" style="display: {{ Request::is('users*') ? 'block' : 'none' }};">

                        @role('admin')
                        <li class=""><a class="" href="{{ route('users.index') }}">All</a></li>
                        @endrole

                        @can('users.index.vendor')
                            <li class=""><a class="" href="{{ route('vendors.index') }}">Vendors</a></li>
                        @endcan

                        @can('users.index.customer')
                        <li class=""><a class="" href="{{ route('customers.index') }}">Customers</a></li>
                        @endcan

                        @can('users.index.locationmanager')
                            <li class=""><a class="" href="{{ route('locationmanagers.index') }}">Location Managers</a></li>
                        @endcan

                        @can('users.index.salesrep')
                            <li class=""><a class="" href="{{ route('salesreps.index') }}">Sales Reps</a></li>
                        @endcan

                        @can('users.index.sauce')
                            <li class=""><a class="" href="{{ route('sauces.index') }}">Sauce</a></li>
                        @endcan

                    </ul>

                </li>
                @endanygate

                @anygate('locations.index', 'categories.index', 'brands.index', 'manage.roles')
                <li class="has_sub">

                    <a href="javascript:void(0);" class=" {{ Request::is('settings*') ? 'active subdrop' : '' }} "><i class="ion-gear-a"></i> <span> Settings </span>
                        <span class="menu-arrow"></span></a>

                    <ul class="list-unstyled" style="display: {{ Request::is('settings*') ? 'block' : 'none' }};">

                        @can('locations.index')
                            <li>
                                <a href="{{ route('locations.index') }}" class=" {{ Request::is('locations*') ? 'active' : '' }}"><i
                                            class=" mdi mdi-home-variant"></i><span> Locations </span></a>
                            </li>
                        @endcan

                        @can('categories.index')
                            <li>
                                <a href="{{ route('categories.index') }}" class=" {{ Request::is('settings/categories*') ? 'active' : '' }}"><i
                                            class=" ion-folder"></i><span> Categories </span></a>
                            </li>
                        @endcan

                        @can('brands.index')
                            <li>
                                <a href="{{ route('brands.index') }}" class=" {{ Request::is('brands*') ? 'active' : '' }}"><i
                                            class=" ion-flame"></i><span> Brands </span></a>
                            </li>
                        @endcan

                        @superadmin
                        <li>
                            <a href="{{ route('permissions.index') }}" class=" {{ Request::is('permissions*') ? 'active' : '' }}"><i
                                        class="mdi mdi-key"></i><span> Permissions </span></a>
                        </li>
                        @endsuperadmin

                        @can('manage.roles')
                        <li>
                            <a href="{{ route('roles.index') }}" class=" {{ Request::is('roles*') ? 'active' : '' }}"><i
                                        class=" mdi mdi-account-key"></i><span> User Roles </span></a>
                        </li>
                        @endcan

                    </ul>

                </li>
                @endrole
            </ul>

            <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>