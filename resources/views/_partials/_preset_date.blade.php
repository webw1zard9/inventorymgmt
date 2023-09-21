<div class="form-group">
    <label for="date_preset">Presets:</label>

    <select id="date_preset" name="date_preset" class="form-control">

        @foreach($date_presets as $preset_name => $date_preset)

            <option
                    value="{{ $preset_name }}"
                    data-date-from="{{ ($date_preset['from']?:null) }}"
                    data-date-to="{{ ($date_preset['to']?:null) }}"

                    {{ ( ($date_preset['from'] && $date_preset['from'] == $from &&
                        $date_preset['to'] && $date_preset['to'] == $to) ? "selected" : "") }}
            >
                {{ $preset_name }}
            </option>

        @endforeach

    </select>
</div>