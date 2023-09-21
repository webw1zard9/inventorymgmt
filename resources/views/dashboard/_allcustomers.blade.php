{{--@can('dashboard.allcustomers')--}}

    <div class="col-lg-6">
        <div class="card-box">
            <h4 class="text-dark  header-title m-t-0">All Customers ({{ $customers->count() }})</h4>

            <p class="text-muted m-b-25 font-13">List of customers with number of days since last order.</p>

            <ul class="nav nav-tabs">

                @foreach($customers_by_days as $segment=>$segment_data)
                    <li class="nav-item">
                        <a href="#panel-{{ clean_string($segment) }}" data-toggle="tab" aria-expanded="true" class="nav-link {{ ($loop->iteration==1?'active':'') }}">
                            {{ $segment_data['label'] }} <span class="badge badge-{{ badge_color($segment) }}">{{ count($segment_data['customers']) }}</span>
                        </a>
                    </li>
                @endforeach

            </ul>
            <div class="tab-content">

                @foreach($customers_by_days as $segment=>$segment_data)
                    <div class="tab-pane fade {{ ($loop->iteration==1?'active show':'') }}" id="panel-{{ clean_string($segment) }}" aria-expanded="true">

                        <div class="table-responsive" style="height: 350px;overflow-y: auto;">
                            <table class="table mb-0">
                                <thead>
                                <tr>
                                    <th>Days</th>
                                    <th>Name</th>
                                    <th>Orders</th>
                                    <th>Order Value</th>
                                </tr>
                                </thead>
                                <tbody style="height: 300px; overflow-y: auto;">
                                @foreach($segment_data['customers'] as $customer)
                                    <tr>
                                        <td><span class="badge badge-{{ badge_color($customer->days_last_order) }}">{{ $customer->days_last_order }}</span></td>
                                        <td><a href="{{ route('users.show', $customer->id) }}">{{ $customer->name }}</a></td>
                                        {{--                                                    <td>{{ $customer->first_order->format(config('inventorymgmt.date_format')) }}</td>--}}
                                        {{--                                                    <td>{{ $customer->last_order->format(config('inventorymgmt.date_format')) }}</td>--}}
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