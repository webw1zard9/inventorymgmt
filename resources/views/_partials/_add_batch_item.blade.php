
<div class="row">
    <div class="col-xl-6">
        <div class="typeahead__container">
            <div class="typeahead__query">
                <input id="search_query" class="js-typeahead-po-batches form-control"
                       name="_q"
                       autocomplete="off"
                       placeholder="Pre-populate fields below by searching items...">
            </div>
        </div>
    </div>

    @if($location_id)
        <input type="hidden" name="_batches[location_id]" value="{{ $location_id }}" />
    @else

        @if(Auth::user()->active_locations->count() > 1)
            <div class="col-xl-6">
                {{ Form::select('_batches[location_id]', Auth::user()->active_locations->pluck('name','id')->prepend('-- Select Location --',''), null, ['class'=>'form-control', 'required'=>'required']) }}
            </div>
        @endif

    @endif



</div>

<hr />

<div class="row">

        <div class="col-xl-6">

            <div class="row">
                <div class="col-12">
                    <div class=" form-group">
                        <input class="form-control" type="text" name="_batches[ref_number]" value="" placeholder="SKU">
                    </div>
                </div>
            </div>

            <div class="row">

                <div class="col-lg-6 form-group">
                    {{ Form::select('_batches[brand_id]', $brands->pluck('name','id')->prepend('-- Select Brand --',''), null, ['class'=>'form-control']) }}
                </div>

                <div class="col-lg-6 form-group">
                    {{ Form::select('_batches[category_id]', $categories->pluck('name','id')->prepend('-- Select Category --',''), null, ['class'=>'form-control required']) }}
                </div>

            </div>

            <div class="row ">
                <div class="col-lg-6 form-group">
                    <input type="text" class="form-control required" name="_batches[name]" value="" placeholder="Product Name" >
                </div>
                <div class="col-lg-6 form-group">
                    {{ Form::select('_batches[type]', collect(config('inventorymgmt.product_type'))->combine(config('inventorymgmt.product_type'))->prepend('-- Select --',''), null, ['class'=>'form-control']) }}
                </div>

            </div>

            <div class="row ">
                <div class="col-12 form-group">
                    <input type="text" class="form-control" name="_batches[allocated_name]" value="" placeholder="Allocated Name" >
                </div>

            </div>

        </div>

        <div class="col-xl-6">

            @if($show_non_inventory_item)
            <div class="row">
            <div class="col-lg-4 form-group">
                <div class="checkbox checkbox-primary">
                    <input id="non_inventory_item" type="checkbox" name="_batches[track_inventory]" value="0">
                    <label for="non_inventory_item">
                        Non-inventory Item
                    </label>
                </div>
            </div>
            </div>
            @endif

            <div class="row qty_row">
                <div class="col-lg-6 form-group">
                    <input type="text" class="form-control quantity required" name="_batches[quantity]" value="" placeholder="Qty" >
                </div>

                <div class="col-lg-6 form-group">
                    {{ Form::select("_batches[uom]", array_combine((config('inventorymgmt.uom')), (config('inventorymgmt.uom'))), null, ['class'=>'form-control']) }}
                </div>
            </div>

            <div class="row ">

                <div class="input-group bootstrap-touchspin col-lg-6 form-group">
                    <span class="input-group-addon bootstrap-touchspin-prefix">$</span>
                    <input type="text" value="" name="_batches[unit_cost]" class="form-control unit_cost required" style="display: block;" placeholder="Unit Cost" >
                </div>

                <div class="input-group bootstrap-touchspin col-lg-6 form-group total_cost_group">
                    <span class="input-group-addon bootstrap-touchspin-prefix">$</span>
                    <input type="text" value="" name="_batches[total_cost]" class="form-control total_cost required" style="display: block;" placeholder="Total Cost" >
                </div>
            </div>

            <div class="row">

                <div class="input-group bootstrap-touchspin col-lg-6 form-group">
                    <span class="input-group-addon bootstrap-touchspin-prefix">$</span>
                    <input type="text" value="" name="_batches[suggested_unit_sale_price]" class="form-control suggested_unit_sale_price required" style="display: block;" placeholder="Sale Price" >
                </div>

                <div class="input-group bootstrap-touchspin col-lg-6 form-group">
                    <span class="input-group-addon bootstrap-touchspin-prefix">$</span>
                    <input type="text" value="" name="_batches[min_flex]" class="form-control min_flex" style="display: block;" placeholder="Min. Flex" >
                </div>

            </div>

        </div>

</div>