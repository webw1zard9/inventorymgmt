@if($vendor_location_inventory->count())
<div class="row">

    <div class="col-12">
        <h4 class="text-dark  header-title m-t-0 m-b-30">By Vendor & Location</h4>
        <div class="row">
            @foreach($vendor_location_inventory->sortBy('vendor_name')->groupBy('vendor_name') as $vendor=>$location_inventory)

                @continue(!$location_inventory->sum('inventory_value'))
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="card-box">
                        <h4 class="text-dark header-title m-t-0 m-b-30">
                            <span class="pull-left">
                                @if($vendor)
                                <a href="{{ route('vendors.show', $location_inventory->first()->vendor_id) }}">{{ $vendor }}</a>
                                @else
                                    --
                                @endif
                            </span>
                            <span class="pull-right">{{ display_currency($location_inventory->sum('inventory_value'), 0) }}</span>
                            <span class="clearfix"></span>
                        </h4>
                        <dl class="row">
                            @foreach($location_inventory->groupBy('location') as $location=>$inventory)
                                @continue(!$inventory->sum('inventory_value'))
                                <dt class="col-5 text-right">{{ $location }}:</dt>
                                <dd class="col-7">{{ display_currency($inventory->sum('inventory_value'), 0) }}</dd>

                            @endforeach

                        </dl>
                    </div>
                </div>

            @endforeach

        </div>
    </div>
</div>
@endif