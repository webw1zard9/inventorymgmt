
<div class="row">

    <div class="col-md-4">

        <div class="form-group">
            {{ Form::label('name', 'Name') }}
            {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Name')) }}
        </div>

        <div class="form-group">
            {{ Form::label('address', 'Street Address') }}
            {{ Form::text('address', old('address'), array('class' => 'form-control', 'placeholder' => 'Address')) }}
        </div>
        <div class="form-group">
            {{ Form::label('address2', 'City, State Zip') }}
            {{ Form::text('address2', old('address2'), array('class' => 'form-control', 'placeholder' => 'City, State Zip')) }}
        </div>

        <div class="form-group">
            <div class="checkbox checkbox-primary">
                {{ Form::checkbox('active', '1', old('active'), ['id'=>'checkbox1']) }}
                <label for="checkbox1">
                    Is active?
                </label>
            </div>
        </div>

    </div>

</div>



