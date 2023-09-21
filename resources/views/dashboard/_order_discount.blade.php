
<div class="row">
    <div class="col-lg-12">
        <div class="card-box">

            <h4 class="text-dark  header-title m-t-0 m-b-30">Order Discount Approval</h4>

            <div class="table-responsive">
                <table id="user-datatable" class="table">

                    <thead>
                    <tr>
                        <th>Location</th>
                        <th>Order Date</th>
                        <th>Order#</th>
                        <th>Customer</th>
                        <th>Sales Rep</th>
                        <th>Order SubTotal</th>
                        <th>Discount</th>
                        <th>Order Total</th>
                        <th>Discount Description</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>

                    @unless($order_discount_approvals->count())
                        <tr>
                            <td colspan="11"><p>No Data</p></td>
                        </tr>

                    @endunless

                        @foreach($order_discount_approvals as $order_discount_approval)

                            <tr>
                                <td>{{ $order_discount_approval->location->name }}</td>
                                <td>{{ $order_discount_approval->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                <td><a href="{{ route('sale-orders.show', $order_discount_approval->id) }}">{{ $order_discount_approval->ref_number }}</a></td>
                                <td>{{ $order_discount_approval->customer->name }}</td>
                                <td>{{ ($order_discount_approval->sales_rep?$order_discount_approval->sales_rep->name:"--") }}</td>
                                <td>{{ display_currency($order_discount_approval->subtotal) }}</td>
                                <td class="text-danger">{{ display_currency($order_discount_approval->discount*-1) }}</td>
                                <td>{{ display_currency($order_discount_approval->total) }}</td>
                                <td>{{ nl2br($order_discount_approval->discount_description) }}</td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('sale-orders.approve-discount', $order_discount_approval)]) }}
                                    {{ method_field('PUT') }}
                                    {{ Form::hidden('discount_approved', 1) }}
                                    <button class="btn btn-success waves-effect waves-light" type="submit">Approve</button>
                                    {{ Form::close() }}
                                </td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('sale-orders.reject-discount', $order_discount_approval)]) }}
                                    {{ method_field('PUT') }}
                                    {{ Form::hidden('discount_approved', 0) }}
                                    <button class="btn btn-danger waves-effect waves-light" type="submit" onclick="return confirm('Rejection will remove the discount from this order.')">Reject</button>
                                    {{ Form::close() }}
                                </td>
                            </tr>

                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>