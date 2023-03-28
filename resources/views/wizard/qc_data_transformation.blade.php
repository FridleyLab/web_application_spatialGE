@extends('layout.main')


@section('content')
    <qc-data-transformation
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        filter-url="{{ route('qc-dt-filter', ['project' => $project->id]) }}"
        filter-url-plots="{{ route('qc-dt-filter-plots', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </qc-data-transformation>
@endsection
