@extends('layouts.app')

@section('content')

    <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-primary waves-effect waves-light mb-2">Back</a>

    <div class="row">

        <div class="col-lg-12">
            <div class="card-box">

                <div class="row">

                    <div class="col-lg-4">

                        <h3>Summary</h3>

                        @include('purchase_orders._summary', ['purcahseOrder'=>$purchaseOrder])

                    </div>

                </div>

            </div>
        </div>
    </div>

@if($purchaseOrder->batches->count())

    <hr>

    <h4 class="m-t-0 header-title">Items <span class="badge badge-info">{{ $purchaseOrder->batches->count() }}</span></h4>

    <div class="row">

        <div class="col-lg-12">

            {{ Form::open(['url'=>route('purchase-orders.return-items-store', [$purchaseOrder->id])]) }}


            <div class="card">
                <div class="card-block">

                    <div class="table-responsive">
                        <table id="return-items" class="table m-t-30 table-hover table-striped">
                            <thead>
                            <tr>
                                <th>Name<br>SKU</th>
                                <th>Qty @ Unit Cost</th>
                                <th>Return Inventory</th>
                                <th>Return Value</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($purchaseOrder->batches as $batch)


                            <tr>
                                <td>
                                    @if($batch->brand) <strong>{{ $batch->brand->name }}</strong><br> @endif

                                    <strong>{{ $batch->category->name }}: {{ $batch->present()->branded_name }}</strong><br>
                                        <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a>
                                </td>
                                <td>
                                    <p>{{ $batch->units_purchased }} {{ $batch->uom }} @ {{ display_currency($batch->avg_unit_price) }}</p>
                                </td>
                                <td>
                                    @foreach($batch->allocated_and_sold_inventory->sortBy('name')->groupBy('name') as $location_name => $locations)

                                        <div class="input-group mb-2">
                                            <span class="input-group-addon">{{ $location_name }}</span>

                                            <input type="number"
                                                   class="qty form-control"
                                                   name="batches[{{ $batch->id }}][locations][{{ $locations->first()->id }}][quantity]"
                                                   value=""
                                                   min="1"
                                                   max="{{ ( ! empty($allocated_sold_inventory[$batch->id][$location_name]['Approved']) ? $allocated_sold_inventory[$batch->id][$location_name]['Approved']->sum('batch_location.quantity') : "0" ) }}"
                                                   {{ (empty($allocated_sold_inventory[$batch->id][$location_name]['Approved']) || !$allocated_sold_inventory[$batch->id][$location_name]['Approved']->sum('batch_location.quantity')?"disabled=disabled":"") }}
                                                   data-unit-cost="{{ $batch->cost_by_location[$locations->first()->id] }}">

                                            <span class="input-group-addon">{{ $batch->uom }} @ {{ display_currency($batch->cost_by_location[$locations->first()->id]) }}</span>
                                        </div>

                                        <input type="hidden" name="batches[{{ $batch->id }}][locations][{{ $locations->first()->id }}][unit_cost]" value="{{ $batch->cost_by_location[$locations->first()->id] }}" />

                                        <p>
                                            @if(! empty($allocated_sold_inventory[$batch->id][$location_name]['Approved']))
                                                <span class="">Available: {{ $allocated_sold_inventory[$batch->id][$location_name]['Approved']->sum('batch_location.quantity') }} {{ $batch->uom }}</span>
                                            @endif
                                            @if(count($allocated_sold_inventory[$batch->id][$location_name]) > 1)&bull;@endif
                                            @if(! empty($allocated_sold_inventory[$batch->id][$location_name]['Pending']))
                                                <span class="text-danger"><i>Pending: {{ $allocated_sold_inventory[$batch->id][$location_name]['Pending']->sum('batch_location.quantity') }} {{ $batch->uom }}</i></span>
                                            @endif
                                        </p>

                                    @endforeach

                                </td>

                                <td class="item_line_subtotal" data-line-subtotal="0"><strong>{{ display_currency(0) }}</strong></td>

                            </tr>
                            @endforeach

                            </tbody>

                            <tfoot>
                                <tr>
                                    <td colspan="2"></td>
                                    <td class="text-right"><strong>Total:</strong></td>
                                    <td class="return_total"><strong>{{ display_currency(0) }}</strong></td>

                                </tr>

                            </tfoot>

                        </table>
                    </div>

                    <div class="col-12"><button type="submit" class="btn btn-primary waves-effect waves-light">Return Items</button></div>

                </div>
            </div>

            {{ Form::close() }}
        </div>
    </div>

@endif

@endsection


@section('js')

    <script type="text/javascript">

        $(document).ready(function() {

            $('#return-items input.qty').keyup(function(e) {

                if($(this).val() > parseInt($(this).attr('max'))) {
                    e.preventDefault();
                    $(this).val($(this).val().slice(0,-1));
                    return;
                }

                let batch_row = $(this).parents('tr');
                let row_total = 0;

                $(batch_row).find('input.qty').each(function(idx) {
                    row_total += $(this).val() * $(this).data('unit-cost');
                });

                let money_format = { minimumFractionDigits: 2, maximumFractionDigits: 2 };

                $(batch_row).find('.item_line_subtotal strong').html("$"+row_total.toLocaleString(undefined, money_format));
                $(batch_row).find('.item_line_subtotal').data('line-subtotal', row_total);

                let total_return=0;
                $('#return-items td.item_line_subtotal').each(function(e) {
                    total_return += $(this).data('line-subtotal');
                });

                $('#return-items td.return_total strong').html("$"+total_return.toLocaleString(undefined, money_format));

            });


        } );

    </script>


    @endsection