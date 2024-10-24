@extends('layout.main')

@section('content')

    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">help</i>
                        </div>
                        <div class="text-end pt-1">
                            <img src="/images/spatialge-logo.png" class="img-fluid max-height-100">
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-body px-2 px-md-3 px-lg-6 text-justify text-dark">


                        <h4 class="my-3 text-center">Frequently Asked Questions</h4>

                        <p><b>I have data from a GeoMx experiment. Can I analyze it with
                                spatialGE?</b></p>

                        <p>Yes, you can. spatialGE can be applied to any spatially-resolved transcriptomics experiment
                            as long as gene counts are associated with x and y coordinates within a tissue sample. Some of
                            the methods in spatialGE have been designed to analyze spatial data that is densely sampled,
                            and most GeoMx experiments are characterized by ROI-based sampling that often is sparse and
                            targeting tissue compartments. As a result, some methods would not yield insightful results.
                            For example, while creating a gene expression surface (interpolation) based on 20 ROIs
                            across a large tissue section is possible, it would likely not be informative.</p>

                        <p><b>Do I have to pay to use spatialGE?</b></p>

                        <p>No, spatialGE is open to the research community. Development of spatialGE has been funded by
                            NIH grants (T32 CA 233399 and U01 CA 274489). Users are only required to create an account,
                            and to acknowledge its use by citing the research articles that describes the methods in
                            spatialGE:</p>

                        <p class="ms-4 me-10">
                            Ospina, O, E., Manjarres-Betancur, R., Gonzalez-Calderon, G., Soupir, A. C., Smalley, I., Tsai, K., Markowitz, J., Vallebuona, E., Berglund, A., Eschrich, S., Yu, X. Fridley, B. L. 2024. spatialGE: A user-friendly web application to democratize spatial transcriptomics analysis. bioRxiv. <a href="https://www.biorxiv.org/content/10.1101/2024.06.27.601050v1" target="_blank">https://www.biorxiv.org/content/10.1101/2024.06.27.601050v1</a>
                        </p>

                        <p class="ms-4 me-10">
                            Ospina, O. E., Wilson, C. M., Soupir, A. C., Berglund, A., Smalley, I., Tsai, K. Y., Fridley, B. L. 2022. spatialGE: Quantification and visualization of the tumor microenvironment heterogeneity using spatial transcriptomics. Bioinformatics 38: 2645–2647. <a href="https://doi.org/10.1093/bioinformatics/btac145" target="_blank">https://doi.org/10.1093/bioinformatics/btac145</a>
                        </p>


                        <p><b>What does spatialGE do with my data?</b></p>

                        <p>Any sample data you upload to spatialGE can only be accessed using your credentials. <strong>Please, do not upload any PHI</strong>.</p>


                        <p><b>How long is my data stored in spatialGE?</b></p>

                        <p>spatialGE stores data at the project level. Multiple projects can be created and kept within the user’s account. There is a 1-year inactive account policy in place. When a project is not used or accessed for a period of one year, the project is deleted (including all data and files), but users will be notified via email 3 months and 1 month before.</p>


                        <p><b>Is it normal for some methods in spatialGE to take long time to complete?</b></p>

                        <p>Some methods implemented in spatialGE are memory and computing intensive (e.g., spatial
                            differential expression, gene expression surface generation). The methods in spatialGE use R
                            code to conduct analyses (code available <a class="text-info"
                                                                        href="https://github.com/FridleyLab/spatialGE"
                                                                        target="_blank">here</a>), which could be
                            downloaded and installed to
                            use via command-line requiring basic knowledge of R programming. Should the user choose to
                            use the spatialGE web app, when running an analysis, the user is prompted to select to
                            receive an email notifying when an analysis is complete. Once the analysis is started and an
                            email notification selected, the user can close spatialGE and the process will keep
                            running.</p>


                        <p><b>Which spatial transcriptomics formats can I analyze in spatialGE?</b></p>

                        <p>The spatialGE web application currently supports Visium data sets. Many more formats will be
                            available soon (see table below). If you would like to analyze other spatial
                            transcriptiomics data formats, please use the command-line <a class="text-info"
                                                                                          href="https://github.com/FridleyLab/spatialGE"
                                                                                          target="_blank">R package</a>.
                        </p>

                        <div class="text-center">
                            <img class="img-fluid pt-2 pb-4 w-80 w-md-70 w-xl-60 w-xxl-40" src="/images/faq/supported_formats.png">
                        </div>


                        <p><b>What is the maximum number of samples per project?</b></p>

                        <p>While there is not a maximum number of samples per project, the spatialGE web application has
                            been designed as an exploratory tool and projects with more than 10 samples are not
                            recommended. Projects with more than 10 samples might experience long queue and execution
                            times. For analysis of larger projects, the use of the <a class="text-info"
                                                                                      href="https://github.com/FridleyLab/spatialGE"
                                                                                      target="_blank">spatialGE R
                                package</a>
                            is recommended, installed in either a personal laptop or a high-performance computing
                            (HPC/cluster) environment.</p>

                        <p><b>How many analyses can I run at the same time?</b></p>

                        <p>Because spatialGE is a free tool open to the public, you can only run one analysis at a time.
                            A maximum number of users can run analyses concurrently, when that number is reached, any
                            additional analyses will be queued until analyses from other users are completed and enough
                            computing resources for your job are available.</p>


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
