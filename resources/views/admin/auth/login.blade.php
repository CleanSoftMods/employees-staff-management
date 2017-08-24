@extends('webed-core::admin._master')

@section('head')

@endsection

@section('js-init')

@endsection

@section('content')
    <!-- BEGIN LOGIN FORM -->
    <?php
    $errors = Session::get('errorMessages');
    if (!$errors) {
        $errors = Session::get('errors');
        if ($errors) {
            $errors = $errors->all();
        }
    }
    ?>
    <div class="login-box">
        <div class="login-logo">
            <a href="/"><b>WebEd</b></a>
        </div>
        <div class="login-box-body">
            <p class="login-box-msg">{{ trans('webed-users::auth.intro_message') }}</p>
            @if($errors) @foreach($errors as $key => $row)
                <div class="note note-danger">
                    <p>{{ $row }}</p>
                </div>
            @endforeach @endif
            {!! form()->open() !!}
            <div class="form-group has-feedback">
                {!! form()->text('email', null, ['class' => 'form-control', 'placeholder' => trans('webed-users::auth.email')]) !!}
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                {!! form()->password('password', ['class' => 'form-control', 'placeholder' => trans('webed-users::auth.password')]) !!}
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    {!! form()->customCheckbox([
                        ['remember', 1, trans('webed-users::auth.remember_me')]
                    ]) !!}
                </div>
                <!-- /.col -->
                <div class="col-xs-4">
                    {!! form()->button(trans('webed-users::auth.sign_in'), ['class' => 'btn btn-primary btn-block btn-flat', 'type' => 'submit']) !!}
                </div>
                <!-- /.col -->
            </div>
            {!! form()->close() !!}
        </div>
    </div>
    <!-- END LOGIN FORM -->
@endsection
