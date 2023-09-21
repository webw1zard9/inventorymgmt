
<div class="row">
    <div class="col-lg-12">
        <div class="card-box">

            <h4 class="text-dark  header-title m-t-0 m-b-30">Inbound Inventory</h4>

            <div class="table-responsive">
                <table id="user-datatable" class="table">

                    <thead>
                    <tr>
                        <th>Origin</th>
                        <th>Location</th>
                        <th>SKU</th>
                        <th>Batch Name</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Flex</th>
                        <th>Allocation Date</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>

                    @unless($intake_batch_locations->count())
                        <tr>
                            <td colspan="9"><p>No Data</p></td>
                        </tr>
                    @endunless
                    {{--{{ dd($batch_approval) }}--}}
                    @foreach($intake_batch_locations as $batch_location)

                        <tr>
                            <td>
                                    @if($batch_location->parent_batch_location)
                                        {{ $batch_location->parent_batch_location->location->name }}
                                    @else
                                        @if($batch_location->quantity < 0)
                                            {{ $batch_location->location->name }}
                                        @else
                                            The Nest
                                        @endif

                                    @endif


{{--                                {{ $batch_location->parent_batch_location?$batch_location->parent_batch_location->location->name:"The Nest" }}--}}
                            </td>
                            <td>
                                @if($batch_location->child_batch_location)
                                    {{ $batch_location->child_batch_location->location->name }}
                                @else
                                    @if($batch_location->quantity > 0)
                                        {{ $batch_location->location->name }}
                                    @else
                                        The Nest
                                    @endif
                                @endif

                                {{--{{ $batch_location->location->name }}--}}
                            </td>
                            <td>{{ $batch_location->batch->ref_number }}</td>
                            <td><a href="{{ route('batches.show', $batch_location->batch->id) }}">{{ $batch_location->name }}</a></td>
                            <td>{{ $batch_location->quantity }} {{ $batch_location->batch->uom }}</td>
                            <td>{{ display_currency($batch_location->suggested_unit_sale_price) }}</td>
                            <td>{{ display_currency($batch_location->min_flex) }}</td>
                            <td>{{ $batch_location->created_at->format(config('inventorymgmt.date_time_format')) }}</td>
                            <td>
                                {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.update', $batch_location->id)]) }}
                                {{ method_field('PUT') }}
                                {{ Form::hidden('approved', 1) }}
                                <button class="btn btn-success waves-effect waves-light" type="submit">Intake</button>
                                {{--<button type="submit" class="btn btn-primary waves-effect waves-light pull-right m-r-10">Open Order</button>--}}
                                {{ Form::close() }}
                            </td>
                            <td>
                                {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.destroy', $batch_location->id)]) }}
                                {{ method_field('DELETE') }}
                                {{ Form::hidden('redir',route('dashboard')) }}
                                <button class="btn btn-danger waves-effect waves-light" type="submit" onclick="return confirm('Are you sure you want to reject these items?')">Reject</button>
                                {{--<button type="submit" class="btn btn-primary waves-effect waves-light pull-right m-r-10">Open Order</button>--}}
                                {{ Form::close() }}
                            </td>
                        </tr>

                    @endforeach

                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>