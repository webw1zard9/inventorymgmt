@if(Auth::user()->active_locations->count() > 1 && empty($saleOrder))
    <div class="row">
        <div class="col-4">
            <div class="form-group">
                <label for="pay_from_location">Pay from Location</label>
                <select id="pay_from_location" name="location_id" class="form-control" required="required">
                    <option value="">-- Select --</option>
                    @foreach(Auth::user()->active_locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="txn_date">Date</label>
            <input type="date" class="form-control" id="txn_date" name="txn_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="payment_type">Type</label>
            <select class="form-control" name="payment_type" id="payment_type">
                <option value="payment">Payment</option>
                <option value="refund">Refund</option>
            </select>
        </div>

    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="payment_method">Method</label>
            <select class="form-control" name="payment_method" id="payment_method">
                @foreach(config('inventorymgmt.payment_methods') as $payment_method=>$payment_method_details)
                    <option value="{{ $payment_method }}"
                            data-crypto="{{ $payment_method_details['crypto'] }}"
                            data-crypto_fee="{{ $payment_method_details['fee'] }}"
                            data-crypto_fee_label="{{ $payment_method_details['fee_label'] }}"
                            data-currency-identifier="{{ $payment_method }}"
                    >{{ $payment_method_details['label'] }}</option>
                @endforeach
            </select>
        </div>

    </div>

</div>

<div class="row">

    <div class="col-md-4">
        <label for="payment"><span class="payment_type">Payment</span> <i class="text-danger">*</i></label>
        <div class="input-group mb-2">
            <span class="input-group-addon">$</span>
            <input type="number" step="0.01" class="form-control" name="payment" id="payment" value="" required>
        </div>

    </div>

    <div class="col-md-4">

        <div class="form-group crypto_fields" style="display: none;">
            <label for="ref_number">Transaction Fee <span class="txn_fee_label"></span></label>

            <div class="input-group mb-2">
                <span class="input-group-addon">$</span>
                <input type="number" step="0.000000001" class="form-control txn_fee" name="txn_fee" disabled="disabled">
                <input type="hidden" class="txn_fee" name="txn_fee" value="">
            </div>
            <div class="checkbox checkbox-primary refund_txn_fee" style="display: none";>
                <input id="refund_txn_fee" name="refund_txn_fee" type="checkbox" checked>
                <label for="refund_txn_fee">Refund Fee?</label>
            </div>


        </div>
    </div>

    <div class="col-md-4">

        <div class="form-group crypto_fields" style="display: none;">

            <label for="total_amount">Total <span class="payment_type">Payment</span> <i class="text-danger">*</i></label>
            <div class="input-group mb-2">
                <span class="input-group-addon">$</span>
                <input type="number" step="0.01" class="form-control" id="total_amount" value="">
            </div>
{{--            --}}
{{--            <label for="ref_number">Total Amount</label>--}}
{{--            <p><strong id="total_amount" class="font-16"></strong></p>--}}
        </div>

    </div>

</div>

<div class="row">

    <div class="col-md-4">

        <div class="form-group crypto_fields" style="display: none;">
            <label for="ref_number">Crypto Amount <i class="text-danger">*</i></label>
            <div class="input-group">
                <input type="number" step="0.000000001" class="form-control" id="crypto_amount" name="ref_number">
                <span class="input-group-addon crypto_type"></span>
            </div>
            <small class="hint">Actual crypto amount received/refunded</small>
        </div>


    </div>

    <div class="col-md-4">
        <div class="form-group crypto_fields" style="display: none;">
            <label for="ref_number"><span class="token_name"></span> Token Value</label>
            <div><strong id="token_value" class="font-16">$0</strong></div>
            <div class="checkbox checkbox-primary">
                <input id="token_value_validate" type="checkbox">
                <label for="token_value_validate">Token value correct? <i class="text-danger">*</i></label>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group crypto_fields" style="display: none;">
            <label for="ref_number">Current <span class="token_name"></span> Market Token Value</label>
            <div><strong id="current_token_value" class="font-16">$0</strong></div>
        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-12">
        <div class="form-group">
            <label for="memo">Memo</label>
            <textarea class="form-control" id="memo" name="memo" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary waves-effect waves-light">Save Payment</button>
    </div>
</div>


@section('js')

    @parent

    <script type="text/javascript">

        $(document).ready(function() {

            txn_fee=0;
            refund_txn_fee=true;

            $('#payment_type').change(function() {

                if($(this).find(':selected').val() == 'refund') {
                    $('.refund_txn_fee').show();
                    $('.payment_type').text('Refund');
                } else {
                    refund_txn_fee=true;
                    $('.payment_type').text('Payment');
                    $('#refund_txn_fee').prop('checked',true);
                    $('.refund_txn_fee').hide();
                    update_fee_total(this);
                }
            });

            $('#payment_method').change(function() {

                txn_fee_rate = $(this).find(':selected').data('crypto_fee');
                txn_fee_label = $(this).find(':selected').data('crypto_fee_label');
//
                $('.crypto_type').text($(this).find(':selected').val());
                $('.token_name').text($(this).find(':selected').val());

                if($(this).find(':selected').data('crypto')) {
                    $('.txn_fee_label').text(txn_fee_label);
                    $('.crypto_fields').show();
                    $('#crypto_amount').prop('required',true);
                    $('#total_amount').prop('required',true);
                } else {
                    reset();
                }

                $.ajax({
                    url: "{{ config('inventorymgmt.coingate_api') }}/" + $(this).find(':selected').val() + "/USD",
                    type: 'GET',
                    accept: 'application/json',
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    // data: JSON.stringify($('.fullfill-item').serializeArray()), // access in body
                    error: function () {
                        // alert('There were some invalid inputs. Those items did not fulfill!');
                        // location.reload();
                    },
                    dataType: 'json',
                    success: function (coingate_resp) {

                        $('#current_token_value').text("$" + parseFloat(coingate_resp.coin_value).toLocaleString());

                    },
                    //
                });
                update_fee_total(this);
            });

            $('#payment, #crypto_amount, #total_amount').bind('change blur', (function() {
                update_fee_total(this);
            }));

            $('#refund_txn_fee').click(function() {
                refund_txn_fee = $(this).is(':checked');
                update_fee_total(this);
            });

        });

        var update_fee_total = function (cell) {

            txn_fee = (refund_txn_fee?txn_fee_rate:0);

            if($('#payment_method').find(':selected').data('crypto')) {

                if ($(cell).attr('id') == 'refund_txn_fee' || $(cell).attr('id') == 'payment_type') {
                    if($('#payment').val()) {
                        add_fee();
                    } else if($('#total_amount').val()) {
                        remove_fee();
                    }
                }

                if (($(cell).attr('id') == 'payment_method' || $(cell).attr('id') == 'payment') && $('#payment').val()) {
                    add_fee();
                }

                if ($(cell).attr('id') == 'total_amount' && $('#total_amount').val()) {
                    remove_fee();
                }

                if ($('#payment').val() && $('#crypto_amount').val()) {
                    var token_value = (parseFloat($('#total_amount').val()) / parseFloat($('#crypto_amount').val())).toFixed(2);
                    $('.token_value').show();
                    $('.total_amount_w_fee').show();
                    $('#token_value_validate').prop('required', true);
                    $('#token_value').text("~$" + parseFloat(token_value).toLocaleString());
                }

            }
        }

        var add_fee = function () {
            var txn_fee_amount = $('#payment').val() * txn_fee;
            var total_amount = (parseFloat($('#payment').val()) + parseFloat(txn_fee_amount)).toFixed(2);
            $('.txn_fee').val(txn_fee_amount.toFixed(2));
            $('.total_amount_w_fee').show();
            $('#total_amount').val(total_amount);
        }

        var remove_fee = function () {
            var payment_less_fee = $('#total_amount').val() / (1 + txn_fee);
            var txn_fee_amount = (parseFloat($('#total_amount').val()) - parseFloat(payment_less_fee)).toFixed(2);
            $('.txn_fee').val(txn_fee_amount);
            $('#payment').val(payment_less_fee.toFixed(2));
        }

        var reset = function() {
            $('.crypto_fields').hide();
            $('.token_value').hide();
            $('.total_amount_w_fee').hide();

            $('#token_value').text("");
            $('.txn_fee').val("");
            $('#crypto_amount').val("");
            $('#total_amount').val("");

            $('#crypto_amount').prop('required',false);
            $('#token_value_validate').prop('required', false);
            $('#total_amount').prop('required',false);
        }


    </script>
@endsection