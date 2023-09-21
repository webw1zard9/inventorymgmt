<dl class="row">
    <dt class="col-5 text-md-right">ID:</dt>
    <dd class="col-7">{{ $batch->id }}</dd>

    <dt class="col-5 text-md-right">{{ ($batch->vendor_id?"Purchase":"Created") }} Date:</dt>
    <dd class="col-7">{{ $batch->created_at->format('m/d/Y') }}</dd>

    @can('po.show')
        @if($batch->purchase_order)
            <dt class="col-5 text-md-right">Purchase Order#:</dt>
            <dd class="col-7"><a href="{{ route('purchase-orders.show', $batch->purchase_order) }}">{{ $batch->purchase_order->ref_number }}</a></dd>
        @endif
    @endcan

    @if(Request::segment(3)=='edit')
        <dt class="col-5 text-md-right">SKU:</dt>
        <dd class="col-7">
            <input type="text" class="form-control" id="ref_number" name="ref_number" value="{{ (old('ref_number')?:$batch->ref_number) }}">
        </dd>
    @endif

    @level(60)
        @if($batch->purchase_order_id && $batch->track_inventory)
        <dt class="col-5 text-md-right">Purchased Qty:</dt>
        <dd class="col-7">{{ $batch->units_purchased }} {{ $batch->uom }}</dd>
       @endif

        <dt class="col-5 text-md-right"><h4>Total Inventory:</h4></dt>
        <dd class="col-7">
            @if($batch->track_inventory)
                <h4>{{ floatval($batch->locations_aggregate->sum('batch_location_aggregate.onhand_inventory')) }} {{ $batch->uom }}</h4>
            @else
                <i>Unlimited</i><br>
                <span class="text-danger">Non-inventory Item</span>
            @endif

        </dd>

        @if(Request::segment(3)=='edit')
        <dt class="col-5 text-md-right">UOM:</dt>
        <dd class="col-7">
            {{ Form::select("uom", array_combine((config('inventorymgmt.uom')), (config('inventorymgmt.uom'))), old('uom'), ['class'=>'form-control']) }}
        </dd>
        @endif
    @endlevel


@can('batches.show.cost')
    <dt class="col-5 text-md-right">Unit Cost:</dt>
    <dd class="col-7">{{ display_currency($batch->unit_price) }}</dd>

    @if(!is_null($batch->avg_unit_price) && $batch->unit_price != $batch->avg_unit_price)
            <dt class="col-5 text-md-right">Average Unit Cost:</dt>
            <dd class="col-7">{{ display_currency($batch->avg_unit_price) }}</dd>
    @endif

@endcan

</dl>