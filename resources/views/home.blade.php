@extends('layout.main')


@section('content')
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">biotech</i>
                        </div>
                        <div class="text-end pt-1">
{{--                            <h2 class="mb-0 text-info">spatial<span class="moffitt-text-blue">GE</span></h2>--}}
                            <img src="/images/spatialge-logo.png" class="img-fluid max-height-100">
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-2 px-md-3 px-lg-6 text-justify text-dark">
{{--                        <h2 class="m-4">spatialGE</h2>--}}

                        <h2 class="my-3 text-center">Methods for the study of the tissue microenvironment using spatial statistics</h2>

                        <p class="fs-5">Welcome to the spatialGE web application, a user friendly, point-and-click implementation of the <a href="https://github.com/FridleyLab/spatialGE" target="_blank" class="text-info">spatialGE R package</a>. This application contains a collection of methods for visualization and spatial statistics analysis of the tissue microenvironment and heterogeneity using spatial transcriptomics (ST) experiments. For a technical description of the methods, please see our publications at the bottom of this page.</p>

                        <p class="fs-5">For tutorials and guides on how to use spatialGE, please refer to the “How to get started” section, or click <a href="{{ route('login') }}" class="text-info">here</a> to log in your account.</p>


                        <div id="carouselAboutSpatialGE" class="py-4 carousel carousel-dark slide" data-bs-ride="carousel">
                            <div class="carousel-indicators">
                                <button type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide-to="2" aria-label="Slide 3"></button>
                                <button type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide-to="3" aria-label="Slide 4"></button>
                                <button type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide-to="4" aria-label="Slide 5"></button>
                            </div>
                            <div class="carousel-inner height-700">

                                <div class="carousel-item active text-center">
                                    <div>
                                        <img class="p-4 my-4 img-fluid" src="{{ asset('/images/landing/landing-01.png') }}">
                                    </div>
                                </div>

                                <div class="carousel-item text-center">
                                    <div>
                                        <img class="p-4 my-4 img-fluid" src="{{ asset('/images/landing/landing-02.png') }}">
                                    </div>
                                </div>

                                <div class="carousel-item text-center">
                                    <div>
                                        <img class="p-4 my-4 img-fluid" src="{{ asset('/images/landing/landing-03.png') }}">
                                    </div>
                                </div>

                                <div class="carousel-item text-center">
                                    <div>
                                        <img class="p-4 my-4 img-fluid" src="{{ asset('/images/landing/landing-04.png') }}">
                                    </div>
                                </div>

                                <div class="carousel-item text-center">
                                    <div>
                                        <img class="p-4 my-4 img-fluid" src="{{ asset('/images/landing/landing-05.png') }}">
                                    </div>
                                </div>

{{--                                <div class="carousel-item active">--}}
{{--                                    <div class="px-8">--}}

{{--                                        <h2>spatialGE</h2>--}}
{{--                                        <p>spatialGE takes data sets from several ST technologies including Visium, GeoMx, SMI, and MERFISH. In general, any ST data set can be analyzed with spatialGE if these two inputs are provided:</p>--}}
{{--                                        <ol type="1">--}}
{{--                                            <li>RNAseq counts for the whole transcriptome or a targeted panel</li>--}}
{{--                                            <li>Spatial 2D coordinates of the places where those counts come within a tissue sample</li>--}}

{{--                                        </ol>--}}
{{--                                        <p>spatialGE takes data sets from several ST technologies including Visium, GeoMx, SMI, and MERFISH. In general, any ST data set can be analyzed with spatialGE if these two inputs are provided:</p>--}}
{{--                                        <ol type="1">--}}
{{--                                            <li>RNAseq counts for the whole transcriptome or a targeted panel</li>--}}
{{--                                            <li>Spatial 2D coordinates of the places where those counts come within a tissue sample</li>--}}

{{--                                        </ol>--}}

{{--                                    </div>--}}
{{--                                </div>--}}

{{--                                <div class="carousel-item">--}}
{{--                                    <div class="px-8">--}}
{{--                                        <p>The methods in spatialGE apply spatial statistics to perform analyses within tissues or comparative analyses across tissue samples:</p>--}}

{{--                                        <ul>--}}
{{--                                            <li>Detection of tissue niches inferred from gene expression (STclust)</li>--}}
{{--                                            <li>Test of differentially expressed genes between tissue niches (STde)</li>--}}
{{--                                            <li>Discovery of spatial patterns for gene sets/pathways (STenrich)</li>--}}
{{--                                            <li>Assessment of gene expression gradients at the interface of tissue niches (STgradient)</li>--}}
{{--                                            <li>Options for gene expression visualization within tissue (STplot)</li>--}}
{{--                                            <li>Comparison of gene expression patterns across samples and potential associations with patient-level data (SThet)</li>--}}
{{--                                        </ul>--}}

{{--                                        <p>Other methods under development include:</p>--}}
{{--                                        <ul>--}}
{{--                                            <li>Cell type annotation (phenotyping/deconvolution) using deep neural networks (STwdl)</li>--}}
{{--                                            <li>Prediction of ligand-receptor interactions (STcorr)</li>--}}
{{--                                        </ul>--}}

{{--                                    </div>--}}
{{--                                </div>--}}
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselAboutSpatialGE" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>







                        <h3>Publications</h3>

                        <p class="fs-5">If you use spatialGE to generate figures or conduct analysis for your publications, please cite the following papers:</p>

                        <ul class="fs-5">
                            <li>Ospina, O. E., Wilson, C. M., Soupir, A. C., Berglund, A., Smalley, I., Tsai, K. Y., Fridley, B. L. 2022. spatialGE: Quantification and visualization of the tumor microenvironment heterogeneity using spatial transcriptomics. Bioinformatics 38: 2645–2647. <a href="https://doi.org/10.1093/bioinformatics/btac145" target="_blank">https://doi.org/10.1093/bioinformatics/btac145</a></li>
                            <li>Ospina, O. E., Fridley, B. L. 2023. A spatially-informed framework to differential gene expression analysis for spatial transcriptomics experiments. (In Prep)</li>
                        </ul>

                        <p class="fs-5">Some scientific articles using spatialGE methods:</p>
                        <ul class="fs-5">
                            <li>Ospina, O., Soupir, A., Fridley, B.L. 2023. A primer on preprocessing, visualization, clustering, and phenotyping of barcode-based spatial transcriptomics data. In: Fridley, B. L., Wang, X. (eds) Statistical Genomics. Methods in Molecular Biology, vol 2629. New York, NY, USA. <a href="https://doi.org/10.1007/978-1-0716-2986-4_7" target="_blank">https://doi.org/10.1007/978-1-0716-2986-4_7</a></li>
                            <li>Alhaddad, H., Ospina, O. E., Khaled, M., Fridley, B. L., Smalley, I. Spatial multi-omics identifies unique tumor-stroma interactions mediating therapy resistance in leptomeningeal melanoma metastasis. (In Prep)</li>
                        </ul>


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
