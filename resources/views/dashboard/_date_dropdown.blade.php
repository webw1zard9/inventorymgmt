<div class="row">
        <div class="col-12 col-xl-2 col-lg-4">
                @include('_partials._preset_date')
        </div>

        <div id="custom_date_range" class="col-12 col-xl-10 col-lg-8" style="display: {{ ($date_preset == 'custom' ? "block" : "none") }}">
                <div class="row">
                        <div class="col-6 col-md-4 col-xl-3">
                                @include('_partials._from_date')
                        </div>

                        <div class="col-6 col-md-4 col-xl-3">
                                @include('_partials._to_date')
                        </div>

                        <div class="col-md-4 col-xl-3">
                                <button type="submit" id="submit_date" class="btn btn-primary waves-effect waves-light" style="margin-top: 1.65rem!important;">Submit</button>
                        </div>
                </div>
        </div>


</div>