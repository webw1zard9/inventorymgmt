@if($category_price_ranges->count())
<div class="row">

    <div class="col-12">
        <h4 class="text-dark  header-title m-t-0 m-b-30">Category Price Ranges</h4>


        @foreach($category_price_ranges as $location_name =>$all_category_price_ranges )

            <h3>{{ $location_name }}</h3>

            <div class="row">

                @foreach($all_category_price_ranges->groupBy('category_name') as $category_name=>$price_ranges)

                <div class="col-xl-6 col-lg-3 col-md-4 col-6">
                    <div class="card-box">
                        <h4 class="text-dark header-title m-t-0 m-b-30">
                            <span class="pull-left">{{ $category_name }}</span>
                            <span class="pull-right">{{ display_currency($price_ranges->sum('inv_value'), 0) }}</span>
                            <span class="clearfix"></span>
                        </h4>

                        <table class="table no-footer">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Range</th>
                                    <th>Items</th>
                                    <th>Inventory</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($price_ranges as $price_range)
                                <tr>
                                    <td>{{ ($price_range->price_range_name) }}</td>
                                    <td>{{ display_category_price_ranges($price_range->min_price, $price_range->max_price) }}</td>
                                    <td>{{ ($price_range->batches_count) }}</td>
                                    <td>{{ ($price_range->inventory) }} {{ $price_range->uom }}</td>
                                    <td>{{ display_currency($price_range->inv_value, 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>

                                        @if($price_ranges->groupBy('uom')->count() > 1)
                                            @foreach($price_ranges->groupBy('uom') as $uom=>$batches)
                                                {{ $uom }}: {{ $batches->sum('batches_count') }}<br>
                                            @endforeach
                                        @else
                                            {{ $price_ranges->sum('batches_count') }}
                                        @endif

                                    </th>
                                    <th>

                                        @if($price_ranges->groupBy('uom')->count() > 1)
                                            @foreach($price_ranges->groupBy('uom') as $uom=>$batches)
                                                {{ $batches->sum('inventory') }} {{ $uom }}<br>
                                            @endforeach
                                        @else
                                            {{ $price_ranges->sum('inventory') }} {{ $price_ranges->first()->uom }}
                                        @endif

                                    </th>
                                    <th>

                                        @if($price_ranges->groupBy('uom')->count() > 1)
                                            @foreach($price_ranges->groupBy('uom') as $uom=>$batches)
                                                {{ $uom }}: {{ display_currency($batches->sum('inv_value'), 0) }}<br>
                                            @endforeach
                                        @else
                                            {{ display_currency($price_ranges->sum('inv_value'), 0) }}
                                        @endif

                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                    @endforeach
            </div>


        @endforeach
    </div>

</div>
@endif