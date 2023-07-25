@extends('layout.main')


@section('content')
    <sign-in-password-reset target-url="{{ route('change-user-password', ['user' => $user->id]) }}" verification-code="{{ $user->email_verification_code }}"></sign-in-password-reset>
@endsection
