@extends('layout.main')

@section('content')

    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">map</i>
                        </div>
                        <div class="text-end pt-1">
                            <img src="/images/spatialge-logo.png" class="img-fluid max-height-100">
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-2 px-md-3 px-lg-6 text-justify text-dark">


                        <h4 class="my-3 text-center">How to Get Started</h4>

                        <div class="my-6">
                            <h5>Using our Demo project is easy!</h5>
                            <p>Description about the sample STlist provided with the Demo Project</p>
                            @auth
                                @if(Auth::user()->hasDemoProject())
                                    You already have a Demo Project, go to it <a href="{{ Auth::user()->getDemoProject()->url }}" class="text-info text-decoration-underline">here</a>
                                @else
                                    <a href="{{ route('clone-demo-project') }}" class="text-info text-decoration-underline">Create demo project</a>
                                @endif
                            @endauth
                            @guest
                                You need to <a href="{{ route('clone-demo-project') }}" class="text-info text-decoration-underline">log in</a> first to create your demo project!
                            @endguest

                        </div>

                        <p>
                            spatialGE provides a collection of methods for the visualization and spatial analysis of
                            gene expression using spatial transcriptomic experiments (e.g., Visium, SMI, or MERFISH).
                            Users are not limited to those platforms: if gene counts and spatial coordinates of each of
                            those counts are available, they can be analyzed in spatialGE (see “Upload data in generic
                            format”).
                        </p>

                        <h5>Uploading Visium experiments:</h5>
                        <p>Many researchers have used Visium for spatial transcriptomics experiments. In spatialGE,
                            users can directly upload Space Ranger outputs for analysis. The required files are:</p>

                        <ol type="1">
                            <li>
                                <p>Gene expression data in the form of .h5 files, one per tissue sample. The Space
                                    Ranger workflow outputs two .h5 files, however the file used in spatialGE is named
                                    filtered_feature_bc_matrix.h5 (see 1a in figure below).</p>

                                <ul class="mb-3">
                                    <li><span class="text-secondary">(To be supported soon) </span>If the filtered_feature_bc_matrix.h5 is not available, users can upload the
                                        expression data in the Matrix Exchange (MEX) format provided by Space Ranger
                                        (see 1b in figure below).
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <p>Spot coordinate data. The file can be found within the directory “spatial” from the
                                    Space Ranger output, and it is named tissue_positions_list.csv (see 2 in figure
                                    below).</p>
                            </li>

                            <li>
                                <p>Optionally users can upload the tissue image and its accompanying scaling factor file
                                    (see 2 in figure below).
                                </p>
                            </li>


                        </ol>

                        <div class="text-center">
                            <img src="/images/how-to/visium_directory_image.png" class="img-fluid max-width-400 my-4">
                        </div>


                        <p>
                            If the user has data associated with each sample (e.g., therapy, overall survival, type of
                            tissue, age of tissue donor, etc), this information can be uploaded in the form of an Excel
                            file or a comma/tab separated file. This file should have the sample names/IDs in the first
                            column, and sample-level information in the subsequent columns (see figure below). This
                            information is required for some of the multi-sample comparison analytical modules.
                        </p>

                        <div class="text-center">
                            <img src="/images/how-to/metadata_figure.png" class="img-fluid max-height-250 my-4">
                        </div>

                        <h5>Uploading spatial transcriptomics experiments using the generic format:</h5>

                        <p>If spatial gene expression data is available in table format, it can also be analyzed in
                            spatialGE regardless of the platform that generated the data. The tables containing gene
                            counts and spatial coordinates should follow the formats described below:</p>

                        <ol type="1">
                            <li><p>Raw (untransformed) gene counts contained in comma- or tab-delimited files. One
                                    for file for each tissue sample. The first column of each file contains gene names.
                                    Subsequent columns contain data for each ROI/spot/cell in the sample. Gene-level is
                                    information is preferred (as opposed to isoform level). If duplicate gene names are
                                    present, they will be appended a number (“.1”, “.2”, etc.).
                                </p>

                                <div class="text-center">
                                    <img src="/images/how-to/generic_format_1.png" class="img-fluid max-height-400 my-4">
                                </div>

                            </li>

                            <li><p>Coordinates (Y positions and X positions) for each ROI/spot/cell in the sample,
                                    one file for each tissue sample. The files should be comma- or tab-delimited. The
                                    first column of each file contains the ROI/spot/cell IDs matching the column names
                                    for each ROI/spot/cell (columns) in the gene count files. The second and third
                                    column are the Y positions and X positions of each ROI/spot/cell.
                                </p>

                                <div class="text-center">
                                    <img src="/images/how-to/generic_format_2.png" class="img-fluid max-height-400 my-4">
                                </div>

                            </li>

                        </ol>



                    </div>
{{--                    <div class="card-footer p-3">--}}
{{--                        <p class="mb-0"><span class="text-success text-sm font-weight-bolder">footer</span></p>--}}
{{--                    </div>--}}
                </div>
            </div>

        </div>


        @include('layout.partials.footer')

    </div>


@endsection
