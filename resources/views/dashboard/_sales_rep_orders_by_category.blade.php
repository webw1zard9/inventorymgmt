<div class="col-lg-12">

    <div class="card-box">

        <h4 class="text-dark  header-title m-t-0 m-b-30">Sales Rep Sales By Category Before Order Discounts</h4>

        <div class="row">
        @foreach($sales_rep_orders_by_category_with_revenue->groupBy('location') as $location => $sale_orders)

            <div class="col-lg-4">

                <h2>{{ $location }}</h2>

                <div id="accordion-{{$location}}" role="tablist" aria-multiselectable="true" class="m-b-20">

                    @foreach($sale_orders->groupBy('sales_rep') as $sales_rep => $categories)

                        <div class="card">
                            <div class="card-header" role="tab" id="heading-{{ Str::slug($location.$sales_rep) }}">
                                <h5 class="mb-0 mt-0 font-16 pull-left">
                                    <a class="{{ $loop->iteration==1?"":"collapsed" }}" data-toggle="collapse" data-parent="#accordion-{{$location}}" href="#collapse-{{ Str::slug($location.$sales_rep) }}" aria-expanded="true" aria-controls="{{ Str::slug($location.$sales_rep) }}">
                                        {{ $sales_rep }} <small><i>{{ display_date_range_sales_rep($from, $to) }}</i></small>
                                    </a>
                                </h5>
                                <span class="pull-right"><strong> {{ ($categories->sum('category_revenue')?display_currency($categories->sum('category_revenue')):"") }}</strong></span>
                                <div class="clearfix"></div>
                            </div>

                            <div id="collapse-{{ Str::slug($location.$sales_rep) }}" class="collapse {{ $loop->iteration==1?"show":"" }}" role="tabpanel" aria-labelledby="heading-{{ Str::slug($location.$sales_rep) }}" aria-expanded="true">
                                <div class="card-block">

                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Units</th>
                                            <th>Sales</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($categories as $category)
                                            <tr>
                                                <td>{{ $category->category }}</td>
                                                <td>{{ $category->unit_count }}</td>
                                                <td>{{ display_currency($category->category_revenue) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>

                    @endforeach

                </div>

            </div>

        @endforeach
        </div>

    </div>
    {{--        {{dd($sales_rep_orders_by_category_with_revenue)}}--}}

</div>
