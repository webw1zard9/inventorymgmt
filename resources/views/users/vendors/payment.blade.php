@extends('layouts.app')

@section('content')

    <a class="btn btn-primary mb-2" href="{{ route('vendors.show', $vendor->id) }}">Vendor Profile</a>

    <div class="card-box">

        {{ Form::open(['url'=>route('vendors.payments.store', $vendor), 'class'=>'prevent_double_click']) }}

        <div class="row">
            <div class="col-6">
                <h1><a href="{{ route('vendors.show', $vendor) }}">{{ $vendor->name }}</a></h1>

            </div>
            <div class="col-6">
                <dl class="row">
                    <dt class="col-9 text-right"><h4 class="m-0">Total Balance:</h4></dt>
                    <dd class="col-3 text-left"><h4 class="m-0">{{ display_currency($vendor->balance) }}</h4></dd>
                    <dt class="col-9 text-right"><h4 class="m-0">Available Credit:</h4></dt>
                    <dd class="col-3 text-left"><h4 class="m-0"><span class="badge badge-success" style="font-size: 16px">{{ display_currency($vendor->vendor_credit_balance) }}</span></h4></dd>
                </dl>
            </div>
        </div>

        <hr>

        <div class="row">

            <div class="col-xl-4 col-md-4">

                <h4>Due Now</h4>

                @if(!empty($vendor_payable_data[$vendor->id]) && $vendor_payable_data[$vendor->id]['payables']->count())
                    <dl class="row">
                        @foreach($vendor_payable_data[$vendor->id]['payables'] as $pay_location_name => $payable_amount)
                            @if(!$payable_amount) @continue @endif

                            <dt class="col-3"><h4>{{ $pay_location_name }}:</h4></dt>
                            <dd class="col-9">
                                <h4>{{ display_currency($payable_amount) }}
                                    @if($payable_amount>0)
                                    <a href="javascript:void(0);" data-location_name="{{ $pay_location_name }}" data-pay_amount="{{ number_format($payable_amount, 2, ".", "") }}" class="btn btn-sm btn-primary ml-3 location_payables_easy_link">Pay Full Amount</a>
                                    @endif
                                </h4></dd>
                        @endforeach
                    </dl>
                @else
                    <h4>$0</h4>
                @endif
            </div>

            <div class="col-xl-4 col-md-4 offset-md-4 offset-xl-4 text-right">

                <div class="row">
                    <div class="col-xl-6">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Transaction Date</label>
                            <input class="form-control" type="date" name="txn_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required="required">
                        </div>

                        @if(Auth::user()->active_locations->count() > 1)
                            <div class="form-group">
                                <label for="pay_from_location">Pay from Location</label>
                                <select id="pay_from_location" name="location_id" class="form-control" required="required">
                                    <option value="">-- Select --</option>
                                    @foreach(Auth::user()->active_locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="col-xl-6">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Payment Method</label>
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="Cash">Cash</option>
                                <option value="BTC">BTC</option>
                                <option value="ETH">ETH</option>
                                @if($vendor->vendor_credit_balance && $vendor->purchase_orders_with_balance->count())
                                    <option value="Credit">Available Credit: {{ display_currency($vendor->vendor_credit_balance) }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="total_amount">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" id="total_amount" class="form-control" name="total_amount" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <hr>
        @if($vendor->purchase_orders_with_balance->count())
        <div class="row">
            <div class="col-6"><h4>Open Purchase Orders</h4></div>
            <div class="col-6">
                <a href="javascript:void(0)" id="clear_payment" class="btn btn-secondary pull-right">Clear Payment</a>
            </div>
        </div>


        <div class="table-responsive">
            <table class="table">

                <thead>
                <tr>
                    <th><input type="checkbox" id="all_purchase_order_check_boxes" class="form-check form-control" /></th>
                    <th>Date</th>
                    <th>PO #</th>
                    <th>Total</th>
                    <th>Payments</th>
                    <th>Balance</th>
                    <th>Payment</th>
                </tr>

                </thead>

                <tbody>
                @foreach($purchase_orders_with_balance as $i => $purchase_order)

                    <tr>
                        <td>
                            <input
                                    id="purchase_order_check_{{ $purchase_order->id }}"
                                    class="form-check form-control purchase_order_checkbox"
                                    type="checkbox"
                                    name="purchase_orders[{{$purchase_order->id}}][checked]"
                                    {{ ($purchaseOrder && $purchaseOrder->id == $purchase_order->id ? "data-pay=true" : "") }}
                            />
                        </td>
                        <td>{{ $purchase_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                        <td><a href="{{ route('purchase-orders.show', $purchase_order) }}">{{ $purchase_order->ref_number }}</a></td>
                        <td>{{ display_currency($purchase_order->total) }}</td>
                        <td>
                            @if($purchase_order->transactions->count())
                                <span  class="text-danger">({{ display_currency($purchase_order->transactions->sum('amount')) }})</span>
                            @endif
                        </td>
                        <td>{{ display_currency($purchase_order->balance) }}</td>
                        <td>
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" id="po_{{$i}}_amount" name="purchase_orders[{{$purchase_order->id}}][amount]" class="form-control purchase_order_amount_to_pay" data-purchase_order_balance="{{ $purchase_order->balance }}" />
                            </div>
                        </td>
                    </tr>

                @endforeach

                </tbody>
                <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>{{ display_currency($vendor->purchase_orders_with_balance->sum('total')) }}</th>
                    <th><span class="text-danger">({{ display_currency($total_payments) }})</span></th>
                    <th>{{ display_currency($vendor->purchase_orders_with_balance->sum('balance')) }}</th>
                    <th></th>
                </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <div class="row">

            <div class="col-xl-4 col-md-6">

                <div class="form-group">
                    <label for="exampleInputEmail1">Memo</label>
                    <textarea class="form-control" name="memo" rows="6"></textarea>
                </div>

            </div>

            <div class="col-xl-3 col-md-6">

                <div class="form-group">
                    <label for="exampleInputEmail1">Ref #</label>
                    <input class="form-control" type="text" name="ref_number" value="">
                </div>

            </div>

            <div class="col-xl-5 col-md-12 text-right">
                <dl class="amount_summary row">
                    <dt class="col-8 text-right"><h3 class="m-0">Total Payment:</h3></dt>
                    <dd class="col-4 text-left"><h3 class="m-0"><span class="total_payment">$0</span></h3></dd>

                    <dt class="col-8 text-right"><h4 class="text-muted m-0">Amount to Apply:</h4></dt>
                    <dd class="col-4 text-left"><h4 class="text-muted m-0"><span class="amount_to_apply">$0</span></h4></dd>

                    <dt class="col-8 text-right"><h4 class="text-muted m-0">Amount to Credit:</h4></dt>
                    <dd class="col-4 text-left"><h4 class="text-muted m-0"><span class="amount_to_credit">$0</span></h4></dd>
                    <input type="hidden" id="amount_to_credit" name="amount_to_credit" value="" />
                </dl>

{{--                <div class="amount_summary" style="">--}}
{{--                    <h3>Total Payment: <span class="total_payment">$0</span></h3>--}}
{{--                    <h4 class="text-muted">Amount to Apply: <span class="amount_to_apply  text-bold">$0</span> </h4>--}}
{{--                    <h4 class="text-muted">Amount to Credit: <span class="amount_to_credit text-bold">$0</span> </h4>--}}
{{--                    <input type="hidden" id="amount_to_credit" name="amount_to_credit" value="" />--}}
{{--                </div>--}}

                <div class="form-group">
                    <button type="submit" class="btn btn-primary waves-effect waves-light">Save Payment</button>
                </div>

                <p id="vendor_credit_disclaimer" class="hint text-danger" style="display: none">This transaction will create an additional credit in the amount of <span class="amount_to_credit">$0</span></p>

            </div>

        </div>

        {{ Form::close() }}

    </div>

@endsection

@section('js')

    <script type="text/javascript">

        $(document).ready(function() {

            var total_payment = 0;
            var amount_to_apply = 0;
            var amount_to_credit = 0;

            // $('.purchase_order_checkbox').each(function(idx, input) {
            //     if($(input).is(":checked")) {
            //         $(input).trigger('change');
            //     }
            // });

            $('#total_amount').change(function (e) {

                var total_amount = parseFloat($(this).val());
                total_payment = total_amount;

                // if ($('input.purchase_order_checkbox:checked').length > 0) {
                //
                //     var current_total_payment=0;
                //     $('input.purchase_order_checkbox:checked').each(function(idx, input) {
                //         current_total_payment+=parseFloat($(input).parents('tr').find('input.purchase_order_amount_to_pay').val());
                //
                //         console.log($(input).parents('tr').find('input.purchase_order_amount_to_pay').val());
                //
                //     });
                //
                //     amount_to_credit = total_amount - current_total_payment;
                //
                // } else {

                    //reset values and checkboxes
                    amount_to_apply = 0;
                    amount_to_credit = 0;

                    $('input.purchase_order_checkbox').prop('checked', false);
                    $('.purchase_order_amount_to_pay').val('');

                    $('.purchase_order_amount_to_pay').each(function(e) {

                        var po_amount = parseFloat($(this).data('purchase_order_balance'));

                        if(po_amount > total_amount) {
                            amount_to_apply += total_amount;
                            $(this).val(total_amount.toFixed(2));
                            $(this).parents('tr').find('input.purchase_order_checkbox').prop('checked', true);
                            total_amount = 0;
                            return false;
                        } else {
                            amount_to_apply += po_amount;
                            $(this).val(po_amount.toFixed(2));
                            $(this).parents('tr').find('input.purchase_order_checkbox').prop('checked', true);
                        }

                        total_amount -= parseFloat(po_amount);

                    });

                    if(total_amount > 0) {
                        amount_to_credit = total_amount;
                        // console.log('amt to credit')
                        // console.log(total_amount);
                    }

                // }

                update_amount_summary();

            });

            $('input.purchase_order_amount_to_pay').change(function (e) {

                var amt_to_pay = parseFloat($(this).val());
                var po_balance = parseFloat($(this).data('purchase_order_balance'));
                if(amt_to_pay < 0) {
                    $(this).val('');
                    return false;
                }
                if(po_balance <  amt_to_pay) {
                    $(this).val(po_balance);
                } else {
                    $(this).val(amt_to_pay);
                }

                $(this).parents('tr').find('input.purchase_order_checkbox').prop('checked', true);

                recalculate_total();

            });

            $('#all_purchase_order_check_boxes').change(function(e) {
                if($(this).is(':checked')) {
                    $('.purchase_order_checkbox').each(function(idx) {
                        $(this).prop('checked', true).trigger('change');
                    });
                } else {
                    $('.purchase_order_checkbox').each(function(idx) {
                        $(this).prop('checked', false).trigger('change');
                    });
                }
            });

            $('.purchase_order_checkbox').change(function(e) {

                var po_amt_to_pay_input = $(this).parents('tr').find('.purchase_order_amount_to_pay');

                if($(this).is(':checked')) {
                    po_amt_to_pay_input.val(po_amt_to_pay_input.data('purchase_order_balance').toFixed(2));

                    recalculate_total();

                } else {

                    if($('#payment_method').val() != 'Credit') {

                        amount_to_apply -= parseFloat(po_amt_to_pay_input.val());
                        amount_to_credit += parseFloat(po_amt_to_pay_input.val());

                        po_amt_to_pay_input.val('');
                        update_amount_summary();

                    } else {
                        po_amt_to_pay_input.val('');
                        recalculate_total();
                    }
                }
            });

            $('a.location_payables_easy_link').click(function() {

                $('#pay_from_location option:contains(' + $(this).data('location_name') + ')').prop("selected", true);
                $('#total_amount').val($(this).data('pay_amount')).trigger('change');

                $(this).blur();
            });

            $('#clear_payment').click(function(e) {

                $('#all_purchase_order_check_boxes').prop('checked', false).trigger('change');
                $('#total_amount').val('');

                total_payment=0;
                amount_to_apply=0;
                amount_to_credit=0;

                update_amount_summary();
            });

            var recalculate_total = function() {

                var total_amount = 0;
                $('input.purchase_order_amount_to_pay').each(function(idx) {
                    var inputValue = parseFloat($(this).val());

                    if(!isNaN(inputValue)) {
                        total_amount += inputValue;
                    }

                });

                total_payment = (total_amount + amount_to_credit);
                amount_to_apply = total_amount;

                update_amount_summary();

                $('#total_amount').val(total_payment.toFixed(2));
            }

            var update_amount_summary = function() {

                console.log(total_payment);
                console.log(amount_to_apply);
                console.log(amount_to_credit);

                $('.amount_summary').show();

                // if(total_payment > 0) {
                    $('.total_payment').text("$"+total_payment.toLocaleString());
                // }

                // if(amount_to_apply > 0) {
                    $('.amount_to_apply').text("$"+amount_to_apply.toLocaleString());
                // }

                // console.log(amount_to_credit);
                // if(amount_to_credit > 0) {
                $('#amount_to_credit').val(parseFloat(amount_to_credit).toFixed(2));
                $('.amount_to_credit').text("$"+amount_to_credit.toLocaleString());
                    // console.log('vendor credit will be issued: '+amount_to_credit.toFixed(2));
                // }

                if(amount_to_credit) {
                    $('#vendor_credit_disclaimer').show();
                } else {
                    $('#vendor_credit_disclaimer').hide();
                }

            }

            $('input[type="checkbox"][data-pay="true"]').prop('checked', true).trigger('change');

        });
    </script>

@endsection