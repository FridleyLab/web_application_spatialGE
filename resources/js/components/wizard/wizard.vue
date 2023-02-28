<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row text">


            <div class="col-xl-6 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_1</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">Importing data</h6>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="w-100">
                        <file-upload></file-upload>
                    </div>
                </div>
            </div>


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_2</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">QC & data Transformation</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-warning w-100 mb-0 toast-btn" type="button" data-target="successToast">Start process!</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_3</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">STplot - Visualization</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-success w-100 mb-0 toast-btn" type="button" data-target="successToast">View</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_4</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">SThet - Spatial heterogen.</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-success w-100 mb-0 toast-btn" type="button" data-target="successToast">View</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_5</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">STclust - Niche detection</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-success w-100 mb-0 toast-btn" type="button" data-target="successToast">View</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_6</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">STDE - Differential expr.</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-success w-100 mb-0 toast-btn" type="button" data-target="successToast">View</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


<!--            <div class="col-xl-4 col-sm-6 mb-xl-0 mb-4 mt-4">-->
<!--                <div class="card">-->
<!--                    <div class="card-header p-3 pt-2">-->
<!--                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">-->
<!--                            <i class="material-icons opacity-10">filter_7</i>-->
<!--                        </div>-->
<!--                        <div class="text-end pt-1">-->
<!--                            <h6 class="mb-0 text-capitalize">STenrich - Spatial gene set</h6>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <hr class="dark horizontal my-0">-->
<!--                    <div class="w-50 m-2">-->
<!--                        <button class="btn bg-gradient-success w-100 mb-0 toast-btn" type="button" data-target="successToast">View</button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->


        </div>
    </div>
</template>
<script>
    export default {
        name: 'wizard',
    }
</script>
