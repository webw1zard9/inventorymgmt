@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <h4>Report Period: {{ Carbon\Carbon::parse($from)->format(config('inventorymgmt.date_format')) }} - {{ Carbon\Carbon::parse($to)->format(config('inventorymgmt.date_format')) }}</h4>

            <div class="card-box">

                {{ Form::open(['url' => url()->current(), 'method' => 'get']) }}

                <div class="row">

                    <div class="col-12 col-lg-4 col-xl-2">
                        @include('_partials._preset_date')
                    </div>

                    <div class="col-6 col-md-4 col-xl-2 custom_date_range">
                        @include('_partials._from_date')
                    </div>

                    <div class="col-6 col-md-4 col-xl-2 custom_date_range">
                        @include('_partials._to_date')
                    </div>

                    <div class="col-12 col-lg-4 col-xl-2">
                        <div class="form-group">
                            <label for="from">Vendor:</label>
                            <select id="vendor" name="filters[vendor]" class="form-control">
                                <option value="">- Select -</option>
                                @foreach($all_vendors as $vendor)
                                    <option value="{{ $vendor->id }}"{{ (isset($filters['vendor']) ? ($vendor->id == $filters['vendor'] ? 'selected' : '' ) : '') }}>{{$vendor->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 col-xl-2">
                        <div class="form-group">
                            <label for="from">Pickup Status:</label>
                            <select id="pickup_status" name="filters[pickup_status]" class="form-control">
                                <option value="">- Select -</option>
                                <option value="picked_up"{{ (isset($filters['pickup_status']) ? ($filters['pickup_status']=='picked_up' ? 'selected' : '' ) : '') }}>Picked Up</option>
                                <option value="pending"{{ (isset($filters['pickup_status']) ? ($filters['pickup_status']=='pending' ? 'selected' : '' ) : '') }}>Pending</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="row">
                <div class="col-md-2 col-xl-3">
                    <button type="submit" class="btn btn-primary waves-effect waves-light">Run Report</button>
                </div>
                </div>

                {{ Form::close() }}

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">

            <h4>Total Net Amount: {{ display_currency($transactions_total_amount) }}</h4>

            <div class="card-box">

                <p>Total Transactions: {{ $transactions_count }}</p>

                <table id="txns-datatable" class="table datatable">
                    <thead class="">
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Vendor</th>
                        <th>Total Amount</th>
                        <th># Transactions</th>
                    </tr>
                    </thead>
                    <tbody>

                        @foreach($vendors as $vendor)

                            <tr>
                                <td><a href="javascript:void(0)" data-toggle="collapse" data-target=".target-{{$vendor->id}}" class="accordion-toggle"><i class="font-14 ion-plus-round"></i> </a></td>
                                <td><a href="{{ route('vendors.show', $vendor) }}"><strong>{{ $vendor->name }}</strong></a> </td>
                                <td>{{ display_currency($vendor->vendor_transactions->sum('amount')) }}</td>
                                <td>{{ $vendor->vendor_transactions->count() }}</td>
                            </tr>

                            <tr class="accordion-body collapse target-{{ $vendor->id }}" style="border: solid 2px #ddd">
                                <td colspan="4">
                                    <table  class="table datatable">
                                        <thead class="">
                                        <tr>
                                            <th>Location</th>
                                            <th>PO</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Date</th>
                                            <th>By</th>
                                            <th>Pickup</th>
                                        </tr>
                                        </thead>
                                        @foreach($vendor->vendor_transactions as $transaction)
                                            <tr>
                                                <td>{{ ($transaction->location?$transaction->location->name:"Nest") }}</td>
                                                <td>
                                                    @if($transaction->purchase_order)

                                                        <a href="{{ route('purchase-orders.show', $transaction->purchase_order->id) }}">{{ $transaction->purchase_order->ref_number }}</a>

                                                    @elseif($transaction->children->count() == 1 && $transaction->children->first()->purchase_order)

                                                        <a href="{{ route('purchase-orders.show', $transaction->children->first()->purchase_order->id) }}">{{ $transaction->children->first()->purchase_order->ref_number }}</a>

                                                    @elseif($transaction->children->count() == 1 || $transaction->children->count() > 1)

{{--                                                        <a href="javascript:void(0)"><i class=" mdi mdi-link-variant"></i> Multiple ({{ $transaction->children->count() }})</a>--}}
                                                        <a href="javascript:void(0)" data-toggle="modal" data-target=".vendor-payment-{{ $transaction->id }}"><i class=" mdi mdi-link-variant"></i> Details ({{ $transaction->children->count() }})</a>

                                                        @include('_partials/_vendor_payment_details_modal', ['transaction'=>$transaction])

                                                    @endif
                                                </td>
                                                <td>{{ display_currency($transaction->amount) }}</td>
                                                <td>{{ $transaction->payment_method }}</td>
                                                <td>{{ $transaction->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                                <td>{{ $transaction->user->name }}</td>
                                                <td>
                                                    @if($transaction->signature)
                                                        <a href="{{ route('vendors.transactions.paid-signature', [$vendor, $transaction]) }}" class="text-light btn btn-success btn-sm">View</a>
{{--                                                    @elseif($transaction->payment_method!='Cash')--}}
{{--                                                        <p>--</p>--}}
{{--                                                    @else--}}
{{--                                                        <a href="{{ route('accounting.transactions-paid-signature', $transaction->id) }}" class="text-light btn btn-secondary">Pick Up</a>--}}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>
        </div>
    </div>


@endsection

@section('js')

    <script type="text/javascript">

        $(document).ready(function() {

            $('#date_preset').change(function (e) {

                var from = $('#date_preset option:selected').data('date-from');
                var to = $('#date_preset option:selected').data('date-to');

                if(from) $('#from').val(from);
                if(to) $('#to').val(to);

            });
        });

    </script>

@endsection
