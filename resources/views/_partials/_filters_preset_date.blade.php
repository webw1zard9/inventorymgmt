
<div class="form-group">
    <select id="date_preset" name="filters[date_preset]" class="form-control">

            <option value="all">All Dates</option>

        @foreach($date_presets as $preset_name => $date_preset)

            <option
                    value="{{ $preset_name }}"

                    {{ ($preset_name == $filters['date_preset'] ? " selected=selected" : "" ) }}

                    {{ ( ($date_preset['from'] && !empty($filters['from_date']) && ($date_preset['from'] == $filters['from_date']) &&
                        $date_preset['to'] && !empty($filters['to_date']) && ($date_preset['to'] == $filters['to_date'])) ? " selected=selected" : "") }}
            >
                {{ $preset_name }}
            </option>

        @endforeach

    </select>
</div>

<div id="custom_date_range" class="row" style="display: {{ Str::lower($filters['date_preset']) == 'custom'?"flex":"none" }}">
    <div class="col-6">
        <span>From:</span><input class="form-control" type="date" name="filters[from_date]" value="{{ (isset($filters['from_date']) ? $filters['from_date'] : '') }}">
    </div>
    <div class="col-6">
        <span>To:</span><input class="form-control" type="date" name="filters[to_date]" value="{{ (isset($filters['to_date']) ? $filters['to_date'] : '') }}">
    </div>
</div>

@section('js')

    @parent

    <script type="text/javascript">
        $(document).ready(function() {

            $('#date_preset').change(function (e) {

                if ($('#date_preset option:selected').val() == 'Custom') {
                    $('#custom_date_range').show();
                } else {
                    $('#custom_date_range').hide();
                    // var from = $('#date_preset option:selected').data('date-from');
                    // var to = $('#date_preset option:selected').data('date-to');
                    // sendRequest(from, to, false);
                }
            });

            // $('#submit_date').click(function (e) {
            //     var from = $('#from').val();
            //     var to = $('#to').val();
            //     sendRequest(from, to, true);
            // });
        });

    </script>
@endsection