@extends('layouts.app')

@section('content')

    <div class="row">

        <div class="col-lg-12">

            <h4 class="m-t-0 header-title"><b>Create Purchase Order</b></h4>

            {{ Form::open(['id'=>'po-create', 'class'=>'form-horizontal', 'files'=>'true', 'url'=>route('purchase-orders.store')]) }}

                <div class="card-box">

                        <div class="form-group row">
                            <label class="col-lg-2 col-form-label">Purchase Date</label>
                            <div class="col-lg-3">
                                <input class="form-control" type="date" name="txn_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>
                        </div>


                        @if($locations->count() > 1)
                        <div class="form-group row">
                            <label class="col-lg-2 col-form-label">Location</label>
                            <div class="col-lg-3">
                                <select name="location_id" class="form-control" required="required">
                                    <option value="">-- Location --</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif

                        <div class="form-group row">
                            <label class="col-lg-2 col-form-label">Vendor</label>
                            <div class="col-lg-3">

                                {{--{{ dump($vendors) }}--}}
                                <input id="vendor_id" type="text" list="originating_entity" class="form-control" value="" placeholder="Search Vendor" autocomplete="off">
                                <datalist id="originating_entity">
                                    @foreach($vendors->prepend('-- Select --','') as $vendor_id=>$vendor_name )
                                        <option value="{{ $vendor_name }}" id="{{ $vendor_id }}">{{ $vendor_name }}
                                    @endforeach
                                </datalist>
                                {{ Form::Input('hidden', 'vendor_id', null) }}
                                <h3 id="vendor-loading" class="d-none">Loading...</h3>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-primary waves-effect waves-light" data-toggle="modal" data-target="#con-close-modal">Add Vendor</button>
                            </div>

                        </div>

                    {{ Form::Input('hidden', 'fund_id', 1) }}

                </div>

            <button id="submit_form" type="submit" class="btn btn-primary waves-effect waves-light">Create <i class="ion-arrow-right-c"></i></button>

            {{ Form::close() }}

        </div>

    </div>

    <div id="con-close-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">

        <div class="modal-dialog">
            {{ Form::open(['class'=>'form-horizontal', 'url'=>route('users.store', ['role'=>'Vendor','route'=>Route::currentRouteName()])]) }}
            {{ Form::hidden('role_id', $vendor_role_id) }}
            {{ Form::hidden('active', 1) }}
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title">Add New Vendor</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="field-1" class="control-label">Vendor Name</label>
                                <input type="text" class="form-control" id="field-1" placeholder="" name="name">
                            </div>
                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary waves-effect waves-light">Save</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>

    </div><!-- /.modal -->

@endsection

@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            $("#vendor_id").change(function() {

                var el=$("#vendor_id")[0];  //used [0] is to get HTML DOM not jquery Object
                var dl=$("#originating_entity")[0];
                if(el.value.trim() != '') {
                    var opSelected = dl.querySelector(`[value="${el.value}"]`);
                    console.log(opSelected.getAttribute('id'));
                    console.log($("input[name='vendor_id']").val(opSelected.getAttribute('id')));
                    // $('#vendor-loading').addClass('d-block');
                    // window.location = window.location.href + '/' + opSelected.getAttribute('id');
                }

            }).keypress(function(e) {
                if(e.which == 13) {
                    e.preventDefault();
                    return false;
                }
            });

            $('#submit_form').click(function (e) {

                // console.log($('.tab-pane:is(.active)').find('.required'));
                $('.tab-pane:is(.active)').find('.required').each(function (idx, elem) {
                    $(elem).attr('required', true);
                    $(elem).attr('my-data', true);
                    // console.log(idx);
                    // console.log(elem);
                });

                return true;

            });

            //'.accordion-collapse.collapse:not(.show) input'

            // $('#vendor_id').change(function () {

            // });

            $('.batch_items').on('click', '.delete_batch', function() {
                // console.log('delete batch');
                $(this).parents('.batch_row').remove();
                set_batch_row_name();
                return;
            });

            $('#add_batch').click(function() {
                var new_row = $('.batch_row:first').clone();

                reset_values($(new_row).find(':input'));

                $(new_row).find('.delete_batch').removeClass('d-none');

                $('.batch_row:last').after(new_row);
                set_batch_row_name();
            });

            $('.batch_items').on('blur', '.quantity', function() {

                var form_grp = $(this).parents('.batch_row');

                if($(form_grp).find('.unit_cost').val()) {
                    var qty = $(this).val();
                    var unit_cost = $(form_grp).find('.unit_cost').val();
                    $(form_grp).find('.total_cost').val((qty * unit_cost).toFixed(2));
                }
            });

            $('.batch_items').on('blur', '.unit_cost', function() {
                var form_grp = $(this).parents('.batch_row');
                var qty = $(form_grp).find('.quantity').val();
                var unit_cost = $(this).val();

                $(form_grp).find('.total_cost').val((qty * unit_cost).toFixed(2));
            });

            $('.batch_items').on('blur', '.total_cost', function() {

                var form_grp = $(this).parents('.batch_row');
                var qty = $(form_grp).find('.quantity').val();
                var total_cost = $(this).val();

                if(qty) $(form_grp).find('.unit_cost').val((total_cost / qty).toFixed(2));
            });

        } );

        var set_batch_row_name = function()
        {
            $('.batch_number span').each(function(index) {
                $(this).text('Batch '+(index+1))
            });

        }

        var reset_values = function(elems)
        {
            $(elems).each(function() {
                if($(this).is("select")) {
                    $(this, 'option:first').attr('selected','selected');
                } else {
                    $(this).val('');
                }
            });
        }

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