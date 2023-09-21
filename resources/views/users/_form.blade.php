
    @isset($redirect_to)
{{--    {{ Form::hidden('role', $active_role) }}--}}
    {{ Form::hidden('redirect_to', $redirect_to) }}
    @endisset

    @if($can_change_role)

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {{ Form::label('role_id', 'Role') }}
                {{ Form::select("role_id", $roles->pluck('description','id'), $active_role->id, ['class'=>'form-control', 'id'=>'role_id']) }}
            </div>
        </div>
    </div>

    @else
        {{ Form::hidden('role_id', $active_role->id) }}
    @endif


    <div class="row">

        <div class="col-md-4">

            <div class="form-group">
                {{ Form::label('name', 'Full Name') }}
                {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Full Name')) }}
            </div>

            <div class="form-group">
                {{ Form::label('email', 'E-mail') }}
                {{ Form::text('email', old('email'), array('class' => 'form-control', 'placeholder' => 'E-mail')) }}
            </div>

            <div class="form-group">
                {{ Form::label('phone', 'Phone') }}
                {{ Form::text('phone', old('phone'), array('class' => 'form-control', 'placeholder' => 'Phone')) }}
                <small>Ex: 123-456-7890</small>
            </div>

        </div>

        <div class="col-md-4">

            <div class="form-group">
                {{ Form::label('address', 'Street Address') }}
                {{ Form::text('details[address]', old('address'), array('class' => 'form-control', 'placeholder' => 'Address')) }}
            </div>
            <div class="form-group">
                {{ Form::label('address2', 'City, State Zip') }}
                {{ Form::text('details[address2]', old('address2'), array('class' => 'form-control', 'placeholder' => 'City, State Zip')) }}
            </div>


            @if(!env('APP_DEMO'))
                @role('admin')

                <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                        {{ Form::label('password', 'Password') }}
                        {{ Form::password('password', array('class' => 'form-control', 'placeholder' => 'Min. 6 characters')) }}
                    </div>
                    </div>
                    <div class="col-md-6">
                    <div class="form-group">
                        {{ Form::label('password_confirmation', 'Confirm Password') }}
                        {{ Form::password('password_confirmation', array('class' => 'form-control', 'placeholder' => 'Min. 6 characters')) }}
                    </div>
                    </div>
                </div>
                @endrole
            @endif

        </div>

        <div class="col-md-4">

            <div class="form-group">
                {{ Form::label('terms', 'Terms') }}
                {{ Form::select("details[terms]", [''=>'-- Select --'] + config('inventorymgmt.payment_terms'), null, ['class'=>'form-control']) }}
            </div>

            <div class="form-group">
                {{ Form::label('active', 'Active') }}
                {{ Form::select("active", ['1'=>'Yes', '0'=>'No'], old('active'), ['class'=>'form-control']) }}
            </div>

            @role('admin')
            <div class="form-group">
                {{ Form::label('pin', 'Vault Log PIN') }}
                {{ Form::password('pin', array('class' => 'form-control', 'placeholder' => 'PIN')) }}
            </div>
            @endrole
        </div>

    </div>

    <hr>

    <div class="row">

        @if($active_role && in_array($active_role->description, ["Location Manager","Sales Rep","Sauce"]))

            <div class="col-2">
                <h2 class="header-title">Locations</h2>

            @if(Auth::user()->hasLocation())
                {{ Form::hidden('locations[]', Auth::user()->current_location->id) }}
                <p>To edit associated locations, select the "Nest" location.</p>
            @else

                <div class="form-group">

                    @foreach($locations as $location)
                        <div class="checkbox checkbox-primary">
                            {{ Form::checkbox('locations[]', $location->id, (!empty($user) && $user->locations->contains($location->id)?true:false), array('id'=>'location-checkbox-'.$location->id, 'data-parsley-multiple'=>'checkbox-'.$location->id, 'data-parsley-id'=>$location->id)) }}
                            {{ Form::label('location-checkbox-'.$location->id, $location->name) }}
                        </div>
                    @endforeach

                </div>
            @endif
            </div>
        @endif

        @role('admin')

        <div class="col-3">

            <h4 class="header-title">{{ $active_role->description }} Permissions</h4>

            @unless($active_role->permissions->count())
                <p>No specific permissions for this role.</p>
            @endunless

            <div class="form-group">
                <ul>
                    @foreach($active_role->permissions->sortBy('description') as $permission)
                        <li>{{ $permission->description }}</li>
                    @endforeach
                </ul>
            </div>

        </div>

        <div class="col-7">
            <h2 class="header-title">Additional User Permissions</h2>

            <div class="form-group">

                <div class="row">
                    @foreach($permissions as $permission)
                        @continue($active_role->permissions->pluck('name')->contains($permission->name))
                        <div class="checkbox checkbox-primary col-6">
                            {{ Form::checkbox('permissions[]', $permission->id, (!empty($user) && $user->userPermissions->contains($permission->id)?true:false), array('id'=>'perm-checkbox-'.$permission->id, 'data-parsley-multiple'=>'checkbox-'.$permission->id, 'data-parsley-id'=>$permission->id)) }}
                            {{ Form::label('perm-checkbox-'.$permission->id, $permission->description) }}
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
        @endrole

    </div>


@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            $("#role_id").change(function() {
                var el=$("#role_id")[0];  //used [0] is to get HTML DOM not jquery Object
                window.location = window.location.origin + window.location.pathname + "?role_id=" + el.value;
            });
        });

    </script>

@endsection