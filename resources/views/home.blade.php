@extends('layout.main')


@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-center">

            <div class="col-xl-9 col-lg-10 col-sm-12 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">psychology</i>
                        </div>
                        <div class="text-end pt-1">
                            <h2 class="mb-0 text-info">spatial<span class="moffitt-text-blue">GE</span></h2>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-6 text-justify text-dark">
{{--                        <h2 class="m-4">spatialGE</h2>--}}

                        <h4 class="my-3 text-center">Methods for the study of the tissue microenvironment using spatial statistics</h4>

                        <p>Welcome to the spatialGE web application, a user friendly, point-and-click implementation of the spatialGE R package. This application contains a collection of methods for visualization and spatial statistics analysis of the tissue microenvironment and heterogeneity using spatial transcriptomics (ST) experiments. For a technical description of the methods, please see our publications at the bottom of this page.</p>

                        <img class="card w-100 p-4 my-4" src="{{ asset('/images/spatialge_diagram.png') }}">

                        <p>spatialGE takes data sets from several ST technologies including Visium, GeoMx, SMI, and MERFISH. In general, any ST data set can be analyzed with spatialGE if these two inputs are provided:</p>
                        <ol type="1">
                            <li>RNAseq counts for the whole transcriptome or a targeted panel</li>
                            <li>Spatial 2D coordinates of the places where those counts come within a tissue sample</li>

                        </ol>

                        <p>The methods in spatialGE apply spatial statistics to perform analyses within tissues or comparative analyses across tissue samples:</p>

                        <ul>
                            <li>Detection of tissue niches inferred from gene expression (STclust)</li>
                            <li>Test of differentially expressed genes between tissue niches (STde)</li>
                            <li>Discovery of spatial patterns for gene sets/pathways (STenrich)</li>
                            <li>Assessment of gene expression gradients at the interface of tissue niches (STgradient)</li>
                            <li>Options for gene expression visualization within tissue (STplot)</li>
                            <li>Comparison of gene expression patterns across samples and potential associations with patient-level data (SThet)</li>
                        </ul>

                        <p>Other methods under development include:</p>
                        <ul>
                            <li>Cell type annotation (phenotyping/deconvolution) using deep neural networks (STwdl)</li>
                            <li>Prediction of ligand-receptor interactions (STcorr)</li>
                        </ul>

                        <h4>Publications</h4>

                        <p>If you use spatialGE to generate figures or conduct analysis for your publications, please cite the following papers:</p>

                        <ul>
                            <li>Oscar E Ospina, Christopher M Wilson, Alex C Soupir, Anders Berglund, Inna Smalley, Kenneth Y Tsai, Brooke L Fridley. 2022. spatialGE: Quantification and visualization of the tumor microenvironment heterogeneity using spatial transcriptomics. Bioinformatics 38: 2645–2647. https://doi.org/10.1093/bioinformatics/btac145</li>
                            <li>Oscar E Ospina, Brooke L Fridley. 2022. A spatially-informed framework to differential gene expression analysis for spatial transcriptomics experiments. (In Prep)</li>
                        </ul>

                        <p>Some scientific articles using sptialGE methods:</p>
                        <ul>
                            <li>Hasan Alhaddad, Oscar E Ospina, Mariam Khaled, Inna Smalley. Spatial multi-omics identifies unique tumor-stroma interactions mediating therapy resistance in leptomeningeal melanoma metastasis. (In Prep)</li>
                        </ul>


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
