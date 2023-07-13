@extends('layout.main')

@section('content')

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-center">

            <div class="col-xl-9 col-lg-10 col-sm-12 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">help</i>
                        </div>
                        <div class="text-end pt-1">
                            <img src="/images/spatialge-logo.png" class="img-fluid max-height-100">
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-6 text-justify text-dark">


                        <h4 class="my-3 text-center">Frequently Asked Questions</h4>

                        <p><b>I have data from a GeoMx experiment. Can I analyze it with
                                spatialGE?</b></p>

                        <p >Yes, you can. spatialGE can be applied to
                            any spatially-resolved transcriptomics experiment as long as gene counts are
                            associated to x and y coordinates within a tissue slice. Nevertheless, the
                            methods in spatialGE have been designed to analyze spatial data that is densely
                            sampled, and most GeoMx experiments are characterized by ROI-based sampling
                            that often is sparse and targeting tissue compartments. As an example, while
                            creating a gene expression surface (interpolation) based on 20 ROIs across a
                            large tissue section is possible, it is likely not informative.</p>

                        <p><b>Do I have to pay to use spatialGE?</b></p>

                        <p>No, spatialGE is open to the
                            research community. Users are only required to create an account, and to
                            acknowledge its use by citing the research articles that describes the methods
                            in spatialGE (see How to Cite section).</p>










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
