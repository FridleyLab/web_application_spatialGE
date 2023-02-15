@extends('layout.main')


@section('content')
    <sign-in target-url="{{ route('login') }}" sign-up-url="{{ route('signup') }}"></sign-in>
@endsection
