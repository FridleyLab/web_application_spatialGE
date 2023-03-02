<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">

            <div class="col-xl-10 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_1</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">Importing data</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5>Adding sample to the project: <span class="text-secondary">{{ project.name }}</span></h5>
                    </div>

                    <hr class="dark horizontal my-0">
                    <div>
                        <file-upload ref="h5" info="Please select the .h5 file to be imported" file-types=".h5" :show-upload-button="false"></file-upload>
                    </div>

                    <hr class="dark horizontal my-0">
                    <div>
                        <file-upload ref="h5" info="Please select the .csv file to be imported" file-types=".csv" :show-upload-button="false" @validated="statusH5"></file-upload>
                    </div>

                    <label class="text-center">
                        <input type="checkbox" v-model="havePng">&nbsp;Do you have PNG and JSON files for this sample?
                    </label>
                    <div v-if="havePng">
                        <hr class="dark horizontal my-0">
                        <div>
                            <file-upload ref="h5" info="Please select the .png file to be imported" file-types=".png" :show-upload-button="false" :is-required="false"></file-upload>
                        </div>

                        <hr class="dark horizontal my-0">
                        <div>
                            <file-upload ref="h5" info="Please select the .json file to be imported" file-types=".json" :show-upload-button="false" :is-required="false"></file-upload>
                        </div>
                    </div>

                    <button @click="$refs['h5'].sayHello()" class="btn bg-gradient-success w-25 mb-0 toast-btn">
                        Upload
                    </button>
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
        name: 'importData',

        props: {
            project: Object,
        },

        data() {
            return {
                validH5: false,
                validCsv: false,
                havePng: false,
                validPng: false,
                validJson: false,
            };
        },

        methods: {
            statusH5: function(isValid) { this.validH5 = isValid; console.log('message: ' + isValid); },
        }

    }
</script>
