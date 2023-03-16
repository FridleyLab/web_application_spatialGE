@extends('layout.main')

@section('headers')
    <script src='https://www.google.com/recaptcha/api.js' async defer></script>
@endsection

@section('content')
    <sign-up target-url="{{ route('signup') }}" sign-in-url="{{ route('login') }}" :jobs="{{ json_encode($jobs) }}" :industries="{{ json_encode($industries) }}" :areas_of_interest="{{ json_encode($areas_of_interest) }}"></sign-up>
@endsection
