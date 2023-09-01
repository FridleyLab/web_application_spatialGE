@extends('layout.main')

@section('content')
    @include('mail.user_account_activation', ['user' => $user])
@endsection
