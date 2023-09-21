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
                @foreach(config('inventorymgmt.vendor_payment_methods') as $payment_method=>$payment_method_details)
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
        <div class="form-group">
        <label for="payment"><span class="payment_type">Payment</span> <i class="text-danger">*</i></label>
        <div class="input-group mb-2">
            <span class="input-group-addon">$</span>
            <input type="number" step="0.01" class="form-control" name="payment" id="payment" value="" required>
        </div>

        </div>
    </div>

</div>

<div class="row">

    <div class="col-xl-12">
        <div class="form-group">
            <label for="memo">Memo</label>
            <textarea class="form-control" id="memo" name="memo" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary waves-effect waves-light">Save</button>
    </div>
</div>


@section('js')

    @parent

    <script type="text/javascript">

        $(document).ready(function() {

            $('#payment_type').change(function() {

                if($(this).find(':selected').val() == 'refund') {
                    $('.payment_type').text('Refund');
                } else {
                    refund_txn_fee=true;
                    $('.payment_type').text('Payment');
                    update_fee_total(this);
                }
            });

        });

    </script>
@endsection