@extends('layout.main')


@section('content')
    <new-project target-url="{{ route('store-project') }}" :platforms="{{ $platforms }}"></new-project>
@endsection
