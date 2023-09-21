@extends('layouts.app')

@section('content')

    <h1 class="header-title">Batch ID: <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a> - <a href="{{ route('batches.transfer-log', $batch->id) }}">Log</a></h1>

    <h5>Available Inventory: {{ $batch->inventory }} <small>{{ $batch->uom }}</small><br>
    Cost Per Unit: {{ display_currency($batch->unit_price) }}<br>
    Strain: {{ $batch->name }}<br>
    Fund: {{ $batch->fund->name }}
    </h5>

    {{ Form::model($batch, ['class'=>'form-horizontal', 'url'=>route('batches.transfer', $batch->id)]) }}



    <div class="row">

        <div class="col-md-2">
            <label class="col-form-label">Pre-Packer Name</label>
            {{ Form::text('packer_name', old('packer_name'), ['class'=>'form-control']) }}
        </div>
    </div>

    <div class="row">

    <div class="col-md-2">
        <label class="col-form-label">Product Name</label>
        {{ Form::text('name', old('name'), ['class'=>'form-control']) }}
    </div>

        <div class="col-md-2">
            <label class="col-form-label">Short Name</label>
            {{ Form::text('description', old('description'), ['class'=>'form-control']) }}
        </div>

    </div>

    <div class="row">
        @if($batch->wt_grams > 0)

            <div class="col-md-2">
                <label class="col-form-label">Used Weight</label>
                <div class="input-group bootstrap-touchspin">
                    <input id="used_weight" type="text" name="used_weight" value="{{ old('used_weight') }}" class="form-control col-lg-6" required>
                    <span class="input-group-addon bootstrap-touchspin-postfix">g</span>
                </div>
                @if($batch->wt_grams > 0)
                    <p>Available: {{ $batch->wt_grams }} grams</p>
                @endif
            </div>

        @elseif(in_array($batch->uom, ['g','lb']))

            <div class="col-md-2">
                <div class="row">
                    <div class="col-md-6">
                        <label class="col-form-label">Used Weight</label>
                        <div class="input-group bootstrap-touchspin">
                            <input id="start_weight" type="text" name="used_weight" class="form-control" placeholder="0" value="{{ old('used_weight') }}" required>
                            {{--<span class="input-group-addon bootstrap-touchspin-postfix">g</span>--}}
                        </div>
                        @if($batch->wt_grams > 0)
                            <p>Available: {{ $batch->wt_grams }} grams</p>
                        @endif
                        <p>Available Inventory: {{ $batch->inventory }} <small>{{ $batch->uom }}</small></p>
                    </div>
                    <div class="col-md-6">
                        <label class="col-form-label">Used Weight UOM</label>
                        {{ Form::select("used_weight_uom", ['lb'=>'lb','g'=>'g'], old("used_weight_upm"), ['class'=>'form-control', 'placeholder'=>'-- Select Uom --', 'required']) }}
                    </div>

                </div>

            </div>

            {{--<div class="col-md-2">--}}
                {{----}}
                {{--<label class="col-form-label">Remaining Weight</label>--}}
                {{--<div class="input-group bootstrap-touchspin">--}}
                    {{--<input id="remaining_weight" type="text" name="remaining_weight" class="form-control col-lg-6" value="{{ old('remaining_weight') }}" required>--}}
                    {{--<span class="input-group-addon bootstrap-touchspin-postfix">g</span>--}}
                {{--</div>--}}
            {{--</div>--}}

        @else

            <div class="col-md-2">
                <label class="col-form-label">Convert</label>
                <div class="input-group bootstrap-touchspin">
                    <input id="transfer_qty" type="text" name="transfer_qty" class="form-control col-lg-6 " value="{{ old('transfer_qty') }}" required>
                    <span class="input-group-addon bootstrap-touchspin-postfix"> of {{ $batch->inventory }} {{ $batch->uom }}</span>
                </div>
            </div>

            {{--<div class="col-md-2">--}}
                {{--<label class="col-form-label">Remaining Weight</label>--}}
                {{--<div class="input-group bootstrap-touchspin">--}}
                    {{--<input id="remaining_weight" type="text" name="remaining_weight" class="form-control col-lg-6" required>--}}
                    {{--<span class="input-group-addon bootstrap-touchspin-postfix">g</span>--}}
                {{--</div>--}}
            {{--</div>--}}

        @endif


    </div>



    <hr>

    <h5>Produced</h5>

    {{--@for($i=0; $i<1; $i++)--}}

    <div class="batch_items">

    <div class="row form-group batch_row">



        <div class="col-2">
            <label class="col-form-label">&nbsp;</label>
            {{ Form::text("rows[ref_number][]", old("[ref_number][]"), ['class'=>'form-control','placeholder'=>'Metrc ID/UID', 'required'=>'required']) }}
        </div>

        <div class="col-2">
            <label class="col-form-label">&nbsp;</label>
            {{ Form::select("rows[category_id][]", $categories->pluck('name','id')->toArray(), (old("[category_id][]")?:$batch->category_id), ['class'=>'form-control', 'placeholder'=>'-- Select Category --', 'required'=>'required']) }}
        </div>
        <div class="col-2">
            <label class="col-form-label">&nbsp;</label>
            {{ Form::select("rows[brand_id][]", $brands->pluck('name','id')->toArray(), old("[brand_id][]"), ['class'=>'form-control', 'placeholder' => '-- Select Brand --']) }}
        </div>
        <div class="col-1">
            {{ Form::label('amount', 'Amount', ['class'=>'col-form-label']) }}
            {{ Form::number("rows[amount][]", old("[amount][]"), ['class'=>'form-control','placeholder'=>'0','step'=>'0.0001', 'required'=>'required']) }}
            {{ Form::label('increment_uid', 'Increment UID', ['class'=>'']) }}
            <input type="checkbox" name="rows[increment_uid][]" />

            <span class="hint"><br><i>If checked, will also created weight based item</i></span>
        </div>

        <div class="col-2">
            <label class="col-form-label">&nbsp;</label>
            {{ Form::select("rows[uom][]", array_combine((config('inventorymgmt.uom')), (config('inventorymgmt.uom'))), old("[uom][]"), ['class'=>'form-control', 'placeholder'=>'-- Select UOM --', 'required'=>'required']) }}
        </div>
        <div class="col-2">
            {{ Form::label('package_date', 'Package Date', ['class'=>'col-form-label']) }}
            {{ Form::date("rows[packed_date][]", old("[packed_date][]", \Carbon\Carbon::now()), ['class'=>'form-control']) }}
        </div>
        <div class="col-1">
            <label class="col-form-label">Funding</label>
            {{ Form::select("rows[fund_id][]", $funds, (old("[fund_id][]")?:$batch->fund_id), ['class'=>'form-control', 'placeholder'=>'-- Select Fund --']) }}
            <a href="javascript:void(0);" class="d-none delete_batch btn btn-danger waves-effect waves-light pull-right"><i class="ion-trash-a"></i></a>
        </div>
    </div>

    </div>

    <a href="javascript:void(0)" class="btn btn-primary waves-effect waves-light" id="add_batch" type="submit">Add Batch</a>

    <hr>

    <button class="btn btn-primary waves-effect waves-light" type="submit">Convert</button>

    {{ Form::close() }}


@endsection

@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            var new_row = $('.batch_row:first').clone();
            console.log(new_row);

            $('.batch_items').on('click', '.delete_batch', function() {
                // console.log('delete batch');
                $(this).parents('.batch_row').remove();
                return;
            });

            $('#add_batch').click(function() {
                var new_row2 = $(new_row).clone();

                // reset_values($(new_row).find(':input'));

                $(new_row2).find('.delete_batch').removeClass('d-none');

                $('.batch_row:last').after(new_row2);
            });


        });

        // var reset_values = function(elems)
        // {
        //     $(elems).each(function() {
        //         if($(this).is("select")) {
        //             $(this, 'option:first').attr('selected','selected');
        //         } else {
        //             $(this).val('');
        //         }
        //     });
        // }

        var update_checkboxes = function (name, elems)
        {
            $(elems).each(function() {
                $(this).val(1).prop('checked',true);
                if($(this).is(':checkbox')) {
                    $(this).attr('id', name+'_'+($('.batch_row').length+1));
                }
                if($(this).is('label')) {
                    $(this).attr('for', name+'_'+($('.batch_row').length+1));
                }
            });
        }

    </script>

@endsection