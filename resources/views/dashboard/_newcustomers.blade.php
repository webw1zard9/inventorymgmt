{{--@can('dashboard.newcustomers')--}}
    <div class="col-md-6">
        <div class="card-box">

            <h4 class="text-dark  header-title m-t-0">New Customers</h4>
            <p class="text-muted m-b-25 font-13">New customers by month.</p>

            <ul class="nav nav-tabs">

                @foreach($new_customers as $month=>$months)
                    <li class="nav-item">
                        <a href="#{{ $month }}" data-toggle="tab" aria-expanded="true" class="nav-link {{ ($loop->iteration==1?'active':'') }}">
                            {{ $month }} ({{ $months->count() }})
                        </a>
                    </li>
                @endforeach

            </ul>
            <div class="tab-content">

                @foreach($new_customers as $month=>$months)
                    <div class="tab-pane fade {{ ($loop->iteration==1?'active show':'') }}" id="{{ $month }}" aria-expanded="true">

                        <div class="table-responsive" style="height: 350px;overflow-y: auto;">
                            <table class="table mb-0">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>First Order</th>
                                    <th>Sales Rep</th>
                                    <th>Orders</th>
                                    <th>Order Value</th>
                                </tr>
                                </thead>
                                <tbody style="height: 300px; overflow-y: auto;">
                                @foreach($months as $customer)
                                    <tr>
                                        <td><a href="{{ route('users.show', $customer->id) }}">{{ $customer->name }}</a></td>
                                        <td>{{ $customer->first_order->format(config('inventorymgmt.date_format')) }}</td>
                                        <td>{{ $customer->sales_rep_name }}</td>
                                        <td>{{ $customer->number_of_orders }}</td>
                                        <td>{{ display_currency($customer->total_order_value) }}</td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach

            </div>
        </div>
    </div>

{{--@endcan--}}