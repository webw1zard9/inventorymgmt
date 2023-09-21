
<div class="row">

    <div class="col-md-4">

        <div class="form-group">
            {{ Form::label('description', 'Name') }}
            {{ Form::text('description', old('description'), array('class' => 'form-control', 'placeholder' => 'Name', 'required'=>'required')) }}
        </div>

        <div class="form-group">
            {{ Form::label('name', 'Permission Key') }}
            {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Permission Key', 'required'=>'required')) }}
        </div>

    </div>

</div>



