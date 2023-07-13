@extends('layout.main')


@section('content')
    <sign-in target-url="{{ route('login') }}" sign-up-url="{{ route('signup') }}" reset-password-url="{{ route('send-password-recovery-email-dev') }}"></sign-in>
@endsection

