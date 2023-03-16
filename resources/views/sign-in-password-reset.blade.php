@extends('layout.main')


@section('content')
    <sign-in-password-reset target-url="{{ route('login') }}" verification-code="{{ $user->email_verification_code }}"></sign-in-password-reset>
@endsection
