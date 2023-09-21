@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-xl-3 col-lg-4">
            <div class="text-center card-box">
                <div class="member-card">

                    <div class="">
                        <h5 class="m-b-5">{{ $user->name }}</h5>
                    </div>

                    <div class="text-left m-t-40">
                        <p class="text-muted font-13"><strong>Full Name :</strong> <span class="m-l-15">{{ $user->name }}</span></p>

                        <p class="text-muted font-13"><strong>Mobile :</strong><span class="m-l-15">{{ $user->present()->phone_number }}</span></p>

                        <p class="text-muted font-13"><strong>Email :</strong> <span class="m-l-15"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></span></p>

                        <p class="text-muted font-13"><strong>Role:</strong>
                            <ul>
                                @foreach($user->roles as $role)
                                    <li>{{ $role->description }}
                                        <ul>
                                            @foreach($role->permissions->sortBy('description') as $permission)
                                                <li>{{ $permission->description }}</li>
                                            @endforeach

                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        </p>

                        <p class="text-muted font-13"><strong>Additional Permissions:</strong>
                            <span class="m-l-15">
                            <ul>
                                @foreach($user->userPermissions->sortBy('description') as $permission)
                                    <li>{{ $permission->description }}</li>
                                @endforeach
                            </ul>
                            </span>
                        </p>

                    </div>

                </div>

            </div> <!-- end card-box -->


        </div> <!-- end col -->


        <div class="col-lg-8 col-xl-9">
            <div class="">
                <div class="card-box">
                    <ul class="nav nav-tabs tabs-bordered">

                        <li class="nav-item">
                            <a href="#settings" data-toggle="tab" aria-expanded="false" class="nav-link active">
                                INFO
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">

                        <div class="tab-pane active" id="settings">

                            {{ Form::model($user, ['role'=>'form', 'class'=>'form-horizontal', 'url'=>route('profile.update')]) }}

                            {{ method_field('PUT') }}

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
                                </div>

                                @if(!env('APP_DEMO'))
                                    <div class="form-group">
                                        {{ Form::label('password', 'Password') }}
                                        {{ Form::password('password', array('class' => 'form-control', 'placeholder' => 'Min. 6 characters')) }}
                                    </div>

                                    <div class="form-group">
                                        {{ Form::label('password_confirmation', 'Confirm Password') }}
                                        {{ Form::password('password_confirmation', array('class' => 'form-control', 'placeholder' => 'Min. 6 characters')) }}
                                    </div>
                                @endif


                                <div class="form-group">
                                    {{ Form::label('pin', 'PIN') }}
                                    {{ Form::password('pin', array('class' => 'form-control', 'placeholder' => 'PIN')) }}
                                </div>

                                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                            {{ Form::close() }}

                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- end col -->
    </div>
    <!-- end row -->


@endsection
