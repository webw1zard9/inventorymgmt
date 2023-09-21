
<div class="row">

    <div class="col-md-4">

        <div class="form-group">
            {{ Form::label('name', 'Name') }}
            {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Name')) }}
        </div>

        <div class="form-group">
            <div class="checkbox checkbox-primary">
                {{ Form::checkbox('is_active', '1', old('is_active'), ['id'=>'checkbox']) }}
                <label for="checkbox">
                    Is active?
                </label>
            </div>
        </div>

    </div>

</div>



