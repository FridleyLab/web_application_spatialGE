@extends('layout.main')

@section('content')
    <contact-us url="{{ route('contact-us') }}"
                user-first-name="{{ !auth()->guest() ? auth()->user()->first_name : '' }}"
                user-last-name="{{ !auth()->guest() ? auth()->user()->last_name : '' }}"
                user-email="{{ !auth()->guest() ? auth()->user()->email : '' }}">
    </contact-us>
@endsection
