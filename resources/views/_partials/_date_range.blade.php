<div class="row">
    <div class="col-12 col-xl-2 col-lg-4">
        @include('_partials._preset_date')
    </div>
        <div id="custom_date_range" class="col-12 col-xl-10 col-lg-8" style="display: {{ ($date_preset == 'Custom' ? "block" : "none") }}">
        <div class="row">
            <div class="col-6 col-md-4 col-xl-3">
                @include('_partials._from_date')
            </div>

            <div class="col-6 col-md-4 col-xl-3">
                @include('_partials._to_date')
            </div>

            <div class="col-md-4 col-xl-3">
                <button type="submit" id="submit_date" class="btn btn-primary waves-effect waves-light" style="margin-top: 1.65rem!important;">Run Report</button>
            </div>
        </div>
    </div>

</div>

@section('js')

    @parent

    <script type="text/javascript">
        $(document).ready(function() {

            $('#date_preset').change(function (e) {

                if ($('#date_preset option:selected').val() === 'Custom') {
                    $('#custom_date_range').show();
                } else {
                    $('#custom_date_range').hide();
                    var from = $('#date_preset option:selected').data('date-from');
                    var to = $('#date_preset option:selected').data('date-to');
                    sendRequest(from, to, $('#date_preset option:selected').val());
                }
            });

            $('#submit_date').click(function (e) {
                var from = $('#from').val();
                var to = $('#to').val();
                sendRequest(from, to, $('#date_preset option:selected').val());
            });
        });

    </script>
@endsection
