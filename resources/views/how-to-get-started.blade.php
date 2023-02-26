@extends('layout.main')

@section('content')

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-center">

            <div class="col-xl-9 col-lg-10 col-sm-12 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">flight</i>
                        </div>
                        <div class="text-end pt-1">
                            <h2 class="mb-0 text-info">spatial<span class="moffitt-text-blue">GE</span></h2>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-6 text-justify text-dark">






                        <h4 class="my-3 text-center">How to Get Started</h4>

                        <p>spatialGE provides a set of methods for the visualization
                            and spatial analysis of gene expression from spatially-resolved transcriptomic
                            experiments, such as those generated by the Visium, SMI, or MERFISH platforms.
                            Users are not limited to those platforms: if gene counts and the spatial
                            coordinates of each of those counts are available, they can be analyzed with
                            spatialGE.
                        </p>

                        <p >To use spatialGE, users provide data in one of the following
                            formats:</p>

                        <ol type="1">
                        <li>
                            <p>From
                            comma/tab separated files:</p>

                            <ol type="a">
                                <li>
                                <p>Raw (untransformed) gene counts contained in comma- or tab-delimited files. One for
                                each sample/tissue slice. The first column of each file contains gene names.
                                Subsequent columns contain data for each spot or cell in the sample. If duplicate
                                gene names are present, they will be added an appendix number (1, 2, etc).</p>

                                <p class="text-center"><img src="{{ asset('/images/how-to/how-to-1.png') }}"></p>
                                </li>
                                <li>
                                <p>Coordinates (x, y) for each spot or cell in the sample contained in comma- or tab-delimited
                                    files. One per sample/tissue slice. The files must contain three columns:
                                    cell/spot IDs, y coordinates, and x coordinates. The cell/spot IDs must match
                                    the column names for each cell/spot (columns) in the RNA count files.
                                </p>

                                <p class="text-center"><img src="{{ asset('/images/how-to/how-to-2.png') }}"></p>
                                </li>
                            </ol>
                        </li>
                        <li>

                            <p>From space ranger outputs generated for the Visium platform:</p>

                            <ol type="a">
                                <li>
                                <p>Gene counts in the form of .h5 files, one per sample/tissue slice
                                    (filtered_feature_bc_matrix.h5 in the figure below).</p>
                                </li>
                                <li>
                                <p>Coordinate data as provided by space ranger (tissue_positions_list.csv in the figure below).</p>

                                <p class="text-center"><img src="{{ asset('/images/how-to/how-to-3.jpg') }}"></p>
                                </li>
                            </ol>

                        </li>


                        </ol>


                        <p>If the user has sample-level associated metadata (e.g.,
                            patient of origin, therapy, overall survival), spatialGE will take this
                            information and match it with each sample/tissue slice. This information is
                            required for multi-sample comparison analytical modules.</p>












                    </div>
                    <div class="card-footer p-3">
                        <p class="mb-0"><span class="text-success text-sm font-weight-bolder">footer</span></p>
                    </div>
                </div>
            </div>

        </div>




        <footer class="footer py-4  ">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-lg-between">
                    <div class="col-lg-6 mb-lg-0 mb-4">
                        <div class="copyright text-center text-sm text-muted text-lg-start">
                            © 2023
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>


@endsection