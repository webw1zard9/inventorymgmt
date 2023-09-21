@if($category_location_inventory->count())
<div class="row">

    <div class="col-12">
        <h4 class="text-dark  header-title m-t-0 m-b-30">By Location & Category</h4>
        <div class="row">

            @foreach($category_location_inventory->groupBy('location_name') as $location_name=>$location_batches)

                <div class="col-xl-3 col-md-6">
                    <div class="card-box">
                        <h4 class="text-dark  header-title m-t-0 m-b-30">
                            <span class="pull-left">{{ $location_name }}</span>
                            <span class="pull-right">{{ display_currency($location_batches->sum('location_value'), 0) }}</span>
                            <span class="clearfix"></span>
                        </h4>
                        <dl class="row">
                            @foreach($location_batches->sortBy('category.name')->groupBy('category.name') as $category_name=>$batches)
                                @continue(!$batches->sum('location_value'))
                                <dt class="col-6 text-right">{{ $category_name }}:</dt>
                                <dd class="col-6">{{ display_currency($batches->sum('location_value'), 0) }}</dd>

                            @endforeach
                        </dl>
                    </div>
                </div>

            @endforeach


        </div>
    </div>

</div>
@endif