@extends('layout.main')


@section('content')
    <sign-up target-url="{{ route('signup') }}" sign-in-url="{{ route('login') }}"></sign-up>
@endsection
