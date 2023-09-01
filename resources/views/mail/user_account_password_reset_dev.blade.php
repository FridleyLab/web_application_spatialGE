@extends('layout.main')

@section('content')
    @include('mail.user_account_password_reset', ['user' => $user])
@endsection
