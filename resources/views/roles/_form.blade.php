
<div class="row">

    <div class="col-xl-3 col-lg-4">


        <div class="form-group">
            {{ Form::label('description', 'Name') }}
            @if(!$role->users->count())
                {{ Form::text('description', old('description'), array('class' => 'form-control', 'placeholder' => 'Name', 'required'=>'required')) }}
            @else
                {{ Form::text('description', old('description'), array('class' => 'form-control', 'disabled'=>'disabled')) }}
                {{ Form::hidden('description', old('description')) }}
            @endif
        </div>

        <div class="form-group">
            {{ Form::label('name', 'Permission Key') }}
            @if(!$role->users->count())
                {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Permission Key', 'required'=>'required')) }}
            @else
                {{ Form::text('name', old('name'), array('class' => 'form-control', 'disabled'=>'disabled')) }}
                {{ Form::hidden('name', old('name')) }}
            @endif
        </div>

    </div>

    @if($role->name != 'admin')
    <hr>
    <div class="col-xl-9 col-lg-8">
        <h2 class="header-title">{{ (!empty($role)?$role->description:"") }} Permissions</h2>

        <div class="form-group">

            <div class="row">
                @foreach($permissions as $permission)
{{--                    @continue($active_role->permissions->pluck('name')->contains($permission->name))--}}
                    <div class="checkbox checkbox-primary col-xl-4 col-lg-6">
                        {{ Form::checkbox('permissions[]', $permission->id, (!empty($role) && $role->permissions->contains($permission->id)?true:false), array('id'=>'perm-checkbox-'.$permission->id, 'data-parsley-multiple'=>'checkbox-'.$permission->id, 'data-parsley-id'=>$permission->id)) }}
                        {{ Form::label('perm-checkbox-'.$permission->id, $permission->description) }}
                    </div>
                @endforeach
            </div>

        </div>
    </div>
    @endif

</div>



