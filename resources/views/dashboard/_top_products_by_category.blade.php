
<div class="col-lg-7">

    <div class="card-box">
        <h4 class="text-dark  header-title m-t-0 m-b-30">Top 15 Products By Category</h4>

        <div class="row">
            <div class="col-12">

                <div id="accordion" role="tablist" aria-multiselectable="true" class="m-b-20">

                    @foreach($top_products_by_category->groupBy('name') as $category_name => $items)

                    <div class="card">
                        <div class="card-header" role="tab" id="heading-{{ Str::slug($category_name) }}">
                            <h5 class="mb-0 mt-0 font-16">
                                <a class="{{ $loop->iteration==1?"":"collapsed" }}" data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ Str::slug($category_name) }}" aria-expanded="true" aria-controls="{{ Str::slug($category_name) }}" class="">
                                    {{ $category_name }}
                                </a>
                            </h5>
                        </div>

                        <div id="collapse-{{ Str::slug($category_name) }}" class="collapse {{ $loop->iteration==1?"show":"" }}" role="tabpanel" aria-labelledby="heading-{{ Str::slug($category_name) }}" aria-expanded="true" style="">
                            <div class="card-block">
                                <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Sold As Name</th>
                                        <th>Units</th>
                                        <th>Avg Unit Price</th>
                                        <th>Sales</th>
                                        <th>Vendor</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                        @foreach($items->slice(0,15) as $item)
                                            @continue(is_null($item->batch_id))
                                            <tr>
                                                <td>
                                                    <a href="{{ route('batches.show', $item->batch_id) }}">{{ $item->sold_as_name }}</a>
                                                    @if($item->sold_as_name!= $item->batch_og_name)
                                                        <br><span class="hint"><small>Original: {{ $item->batch_og_name }}</small></span>
                                                    @endif
                                                </td>
                                                <td>{{ $item->count }}</td>
                                                <td>{{ display_currency($item->avg_price) }}</td>
                                                <td>{{ display_currency($item->sales) }}</td>
                                                <td>{{ $item->vendor_name }}</td>
                                            </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endforeach

                </div>
            </div>
        </div>

    </div>

</div>
