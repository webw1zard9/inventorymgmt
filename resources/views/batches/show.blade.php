@php use Illuminate\Support\Facades\Auth; @endphp
@extends('layouts.app')

@section('content')

    <a href="{{ route('batches.index') }}" class="btn btn-primary waves-effect waves-light m-r-5"><i class="ti-arrow-left"></i> All Batches</a>

    @level(60)
    <a href="{{ route('batches.activity-log', $batch->id) }}" class="btn btn-secondary waves-effect waves-light pull-right">Activity Log <i class="ion-ios7-timer-outline"></i></a>
    @endlevel

    @if($batch->transfer_logs_reconcile->count() > 0)
        <a href="{{ route('batches.reconcile-log', ['batch'=>$batch, 'filters'=>['date_preset'=>'all']]) }}" class="btn btn-secondary pull-right mr-2">Reconcile Log <i class="ion-ios7-timer-outline"></i></a>
    @endif

    @if(Auth::user()->hasMultiLocations())
        <a href="{{ route('batches.reconcile-list', $batch->id) }}" class="btn btn-primary pull-right mr-2">Reconcile <i class="mdi mdi-logout"></i></a>
    @endif



    <div class="row">
        <div class="col-lg-12">

            <h2>{{ $batch->category->name }}: {!! $batch->present()->branded_name !!}
                @if(Auth::user()->isAdmin() && $batch->original_name)<small>({{ $batch->original_name }})</small>@endif
            </h2>

            <div class="row">

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <div class="widget-simple text-center card-box">
                        <h3 class="text-success counter font-bold mt-0">

                            @if($batch->track_inventory)
                                {{ floatval($batch->total_available_inventory) }}
                                {{ $batch->uom }}
                            @else
                                Unlimited
                            @endif
                        </h3>
                        <p class="text-muted mb-0">Total Available
                        </p>
                    </div>

                </div>

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="widget-simple text-center card-box">
                        <h3 class="text-warning counter font-bold mt-0">{{ $total_hold }} {{ $batch->uom }}</h3>
                        <p class="text-muted mb-0">On Hold</p>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="widget-simple text-center card-box">
                            <h3 class="text-default counter font-bold mt-0">{{ $total_ready_to_pack }} {{ $batch->uom }}</h3>
                        <p class="text-muted mb-0">Ready To Pack</p>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="widget-simple text-center card-box">
                        <h3 class="text-info counter font-bold mt-0">{{ $total_ready_for_delivery }} {{ $batch->uom }}</h3>
                        <p class="text-muted mb-0">Fulfilled</p>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="widget-simple text-center card-box">
                        <h3 class="text-danger counter font-bold mt-0">{{ $total_delivered }} {{ $batch->uom }}</h3>
                        <p class="text-muted mb-0">Sold</p>
                    </div>
                </div>

                @if($batch->track_inventory)
                <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                    <div class="widget-simple text-center card-box">
                        <h3 class="text-secondary counter font-bold mt-0">{{ ($batch->transfer_logs_reconcile->sum('quantity_transferred') * -1) }} {{ $batch->uom }}</h3>
                        <p class="text-muted mb-0">Reconciled
{{--                            @if(Auth::user()->hasMultiLocations())--}}
{{--                            <br><a href="{{ route('batches.reconcile-list', $batch->id) }}" class="btn btn-secondary btn-sm mt-2">Reconcile <i class="mdi mdi-logout"></i></a>--}}
{{--                            @endif--}}

                        </p>
                    </div>
                </div>
                @endif

            </div>

            <div class="row">

                @level(60)
                <div class="col-md-6">
                    <div class="card-box">

                        @include('batches._batch_info')

                    </div>
                </div>
                @endlevel

                <div class="col-md-6">
                    <div class="card-box">
                        @can('batches.edit')
                            <div class="pull-right">
                                <a href="{{ route('batches.edit', $batch->id) }}" class="btn btn-secondary btn-sm waves-effect waves-light m-b-10"><i class="mdi mdi-lead-pencil"></i> Edit Primary Batch</a>
                            </div>
                            <div class="clearfix"></div>
                        @endcan

                        <dl class="row">

                            <dt class="col-5 text-xl-right"><h4>SKU:</h4></dt>
                            <dd class="col-5"><h4>{{ $batch->ref_number }}</h4></dd>

                            <dt class="col-5 text-xl-right"><h4>Sugg. Sale Price:</h4></dt>
                            <dd class="col-7"><h4 class="text-success">{{ display_currency($batch->suggested_unit_sale_price) }}</h4></dd>

                            @level(60)
                            @if($batch->min_flex)
                                <dt class="col-5 text-xl-right">Flex:</dt>
                                <dd class="col-7 text-danger">-{{ display_currency($batch->min_flex) }}</dd>

                                <dt class="col-5 text-xl-right"><h4>Flex Range:</h4></dt>
                                <dd class="col-7"><h4>{{ display_currency($batch->min_flex_price) }} - {{ display_currency($batch->max_flex_price) }}</h4></dd>
                            @endif
                            @endlevel

                        </dl>
                    </div>
                </div>

            </div>

        </div>
    </div>


    @if($batch->allocated_inventory->count())

        <div class="row">

            <div class="col-lg-12">

                    <h4>Allocated Inventory</h4>

                    @foreach($batch->locations_aggregate as $location_aggregate)

                    <div class="card-box">
                        <div class="row">
                            <div class="col-6">
                                <h4 class="header-title {{ ($location_aggregate->trashed()?"text-danger":"") }}">{{ $location_aggregate->name }} {{ ($location_aggregate->trashed() ? "(Deleted)" : "" ) }}</h4>
                            </div>
                            <div class="col-6">
                                @if( ! $location_aggregate->trashed())

                                    @can('batches.reconcile')
                                    <a class="ml-2 btn btn-primary pull-right" href="{{ route('batches.reconcile-list', ['batch'=>$batch->id, 'location'=>$location_aggregate->id]) }}">Reconcile <i class="mdi mdi-logout"></i> </a>
                                    @endcan

                                    @if($location_aggregate->batch_location_aggregate->available_inventory && Auth::user()->active_locations->count() > 1)
                                    <a class="ml-2 btn btn-primary pull-right" href="{{ route('batches.allocate', ['batch'=>$batch->id, 'location'=>$location_aggregate->id]) }}">Allocate <i class="mdi mdi-logout"></i> </a>
                                    @endif

                                    @can('batches.edit')
                                    <a class="btn btn-secondary pull-right" href="{{ route('batch-location-aggregate.edit', $location_aggregate->batch_location_aggregate->id) }}"><i class="mdi mdi-lead-pencil"></i> Edit Allocation</a>
                                    @endcan

                                @endif

                            </div>
                        </div>

                        <h3 class="">{{ $location_aggregate->batch_location_aggregate->location_batch_name }}</h3>

                    <div class="row">
                        <div class="col-12 m-b-20 p-t-10 p-b-10">
                            <div class="row">
                                @can('batches.show.cost')
                                    <div class="col-2 text-muted">
                                        <strong>Current Cost:</strong>
                                        <h4 class=" d-inline ml-2 text-muted">{{ display_currency($location_aggregate->batch_location_aggregate->location_unit_price) }}</h4>
{{--                                        <a href="" class="btn btn-sm btn-secondary">Change Cost</a>--}}
                                    </div>
                                @endcan
                                <div class="col-2"><strong>Sugg. Sale Price:</strong> <h4 class=" d-inline ml-2 text-success">{{ display_currency($location_aggregate->batch_location_aggregate->suggested_unit_sale_price) }}</h4></div>
                                <div class="col-2"><strong>Flex:</strong> <h4 class=" d-inline ml-2">{{ display_currency($location_aggregate->batch_location_aggregate->min_flex) }}</h4></div>
                                <div class="col-2"><strong>Available:</strong>
                                    @if($batch->track_inventory)
                                        <h4 class="text-{{ floatval($location_aggregate->batch_location_aggregate->available_inventory) == 0 ? "danger" : "success" }} d-inline ml-2">{{ floatval($location_aggregate->batch_location_aggregate->available_inventory) }} {{ $batch->uom }}</h4>
                                    @else
                                        <i>Unlimited</i>
                                    @endif
                                </div>
                                @if(Auth::user()->active_locations->count() > 1)
                                <div class="col-2"><strong>Waiting Approval:</strong>
                                    @if($batch->track_inventory)
                                        <h4 class="text-{{ floatval($location_aggregate->batch_location_aggregate->waiting_approval_inventory) == 0 ? "danger" : "warning" }} d-inline ml-2">{{ floatval($location_aggregate->batch_location_aggregate->waiting_approval_inventory) }} {{ $batch->uom }}</h4>
                                    @else
                                        <i>Unlimited</i>
                                    @endif
                                </div>
                                @endif

                                @if($batch->track_inventory)
                                    <div class="col-2"><strong>Reconciled:</strong> <h4 class=" d-inline ml-2">{{ !empty($batch->reconciled_inventory->groupBy('id')[$location_aggregate->id]) ? $batch->reconciled_inventory->groupBy('id')[$location_aggregate->id]->sum('batch_location.quantity') : "0" }} {{ $batch->uom }}</h4></div>
                                @endif

                            </div>

                        </div>

                        @if($batch->track_inventory)
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="user-datatable" class="table">

                                    <thead>
                                    <tr>
                                        <th style="width: 200px">Status</th>
                                        <th style="width: 100px">Allocated</th>
                                        @can('batches.show.cost')
                                        <th style="width: 100px">Cost</th>
                                        @endcan
                                        <th style="width: 200px">Origin</th>
                                        <th style="width: 200px">Destination</th>
                                        <th style="width: 200px">Allocated</th>
                                        <th style="width: 200px">Accepted</th>
                                    </tr>
                                    </thead>

                                    <tbody>

                                        @foreach($batch->allocated_inventory->groupBy('name')[$location_aggregate->name]->groupBy('batch_location.approved') as $approved => $batch_location2)

                                            @foreach($batch_location2 as $bl)

                                                <tr class="{{ ($bl->batch_location->return_item?"text-danger":"") }}">

                                                    <td class="text-{{ ($approved?"success":"danger") }}">
                                                        @if(!$bl->batch_location->transfer_log_id)
                                                        {{ ($approved?"Approved":"Waiting Approval") }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $bl->batch_location->quantity }} {{ $batch->uom }}
                                                        @if($bl->batch_location->transfer_log_id)
                                                            <em>Reconciled</em>
                                                        @endif
                                                    </td>
                                                    @can('batches.show.cost')
                                                    <td>
                                                       {{ display_currency($bl->batch_location->unit_price) }}
                                                    </td>
                                                    @endcan
                                                    <td>
                                                        @if($bl->batch_location->cost_change)
                                                            <i>Cost Change</i>
                                                        @elseif($bl->batch_location->return_item)
                                                            <i>Returned</i>
                                                        @else

                                                            @if($bl->batch_location->transfer_log_id)
                                                                --
                                                            @else
                                                                @if($bl->batch_location->parent_batch_location)
                                                                    {{ $bl->batch_location->parent_batch_location->location->name }}
                                                                @else
                                                                    @if($bl->batch_location->quantity < 0)
                                                                        {{ $location_aggregate->name }}
                                                                    @else
                                                                        The Nest
                                                                    @endif

                                                                @endif
                                                            @endif

                                                        @endif
                                                    </td>
                                                    <td>

                                                        @if($bl->batch_location->cost_change || $bl->batch_location->return_item)
                                                            --
                                                        @else

                                                            @if($bl->batch_location->transfer_log_id)
                                                                --
                                                            @else
                                                                @if($bl->batch_location->child_batch_location)
                                                                    {{ $bl->batch_location->child_batch_location->location->name }}
                                                                @else
                                                                    @if($bl->batch_location->quantity > 0)
                                                                        {{ $bl->batch_location->location->name }}
                                                                    @else
                                                                        The Nest
                                                                    @endif
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td>{{ $bl->batch_location->created_at->format(config('inventorymgmt.date_time_format')) }}</td>
                                                    <td>

                                                        @if($approved)
                                                            @if($bl->batch_location->approved_at)
                                                            {{ $bl->batch_location->approved_at->format(config('inventorymgmt.date_time_format')) }}
                                                            @endif

                                                            @if($bl->batch_location->intake_activity)
                                                                by {{ $bl->batch_location->intake_activity->causer->name }}
                                                            @endif
                                                        @else

                                                            {{ Form::open(['class'=>'form-horizontal pull-left mr-2', 'url'=>route('batch-location.update', $bl->batch_location->id)]) }}
                                                            {{ method_field('PUT') }}
                                                            {{ Form::hidden('approved', 1) }}
                                                            <button class="btn btn-success waves-effect waves-light" type="submit">Accept</button>
                                                            {{ Form::close() }}

                                                            {{ Form::open(['class'=>'form-horizontal pull-left', 'url'=>route('batch-location.destroy', $bl->batch_location->id)]) }}
                                                            {{ method_field('DELETE') }}
                                                            {{ Form::hidden('redir',route('batches.show', $batch->id)) }}
                                                            <button class="btn btn-danger waves-effect waves-light" type="submit" onclick="return confirm('Are you sure you want to delete allocation?')">Reject</button>
                                                            {{ Form::close() }}
                                                        @endif

                                                    </td>

                                                </tr>

                                            @endforeach
                                        @endforeach

                                    </tbody>

                                </table>

                            </div>
                        </div>
                        @endif
                        {{--<hr>--}}
                    </div>
                    </div>
                    @endforeach

            </div>
        </div>
    @endif


    @if($all_orders_by_status->count())

        <h4>Sale Orders</h4>

        @foreach(config('inventorymgmt.order_statuses') as $status)
            @continue(empty($all_orders_by_status[$status]))

            <div class="row">
                <div class="col-lg-12">
                    <div class="card-box">

                        <h4>{{ Str::ucfirst($status) }} <span class="badge badge-pill badge-primary }}" style="font-size: 14px">{{ $all_orders_by_status[$status]->count() }}</span></h4>

                        @include('batches._orders_list', ['orders'=>$all_orders_by_status[$status]])

                    </div>

                </div>

            </div>

        @endforeach
    @endif

@endsection

@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            $('#destination_user_id').change(function () {

                $('#customer-loading').addClass('d-block');

                var el=$("#destination_user_id")[0];  //used [0] is to get HTML DOM not jquery Object
                var dl=$("#destination_user_id_list")[0];
                if(el.value.trim() != '') {
                    var opSelected = dl.querySelector(`[value="${el.value}"]`);

                    // window.location = window.location.href + '/' + opSelected.getAttribute('id');
                    window.location = '{{ route('batches.show', $batch->id) }}/customer/' + opSelected.getAttribute('id');
                }

                // window.location = window.location.href + '/customer/' + this.value;

            });

        });
    </script>

@endsection