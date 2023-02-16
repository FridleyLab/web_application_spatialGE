@extends('layout.main')

@section('headers')
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
@endsection

@section('content')
    <sign-up target-url="{{ route('signup') }}" sign-in-url="{{ route('login') }}"></sign-up>
@endsection
