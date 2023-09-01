@extends('layout.main')


@section('content')
    <qc-data-transformation
        :project="{{ json_encode($project) }}"
        :samples="{{ json_encode($samples) }}"
        filter-url="{{ route('qc-dt-filter', ['project' => $project->id]) }}"
        filter-url-plots="{{ route('qc-dt-filter-plots', ['project' => $project->id]) }}"
        normalize-url="{{ route('qc-dt-normalize', ['project' => $project->id]) }}"
        normalize-url-plots="{{ route('qc-dt-normalize-plots', ['project' => $project->id]) }}"
        normalized-url-data="{{ route('qc-dt-normalized-data', ['project' => $project->id]) }}"
        pca-url="{{ route('qc-dt-pca', ['project' => $project->id]) }}"
        pca-plots-url="{{ route('qc-dt-pca-plots', ['project' => $project->id]) }}"
        quilt-url="{{ route('qc-dt-quilt', ['project' => $project->id]) }}"
        :color-palettes="{{ json_encode($color_palettes) }}"
    >
    </qc-data-transformation>
@endsection
