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

                    <h6 class="text-center">Please import your H5 and CSV files (.png image is optional)</h6>
                    <hr class="dark horizontal my-0">

                    <div class="my-1">
                        <file-upload-drag-drop></file-upload-drag-drop>
                    </div>

                    <div>
                        <file-upload ref="h5" info="Select the .h5 file to be imported" file-types=".h5" :show-upload-button="false" @validated="statusH5" @uploaded="uploadedH5"></file-upload>
                    </div>

                    <hr class="dark horizontal my-0">
                    <div>
                        <file-upload ref="csv" info="Select the .csv file to be imported" file-types=".csv" :show-upload-button="false" @validated="statusCsv" @uploaded="uploadedCsv"></file-upload>
                    </div>

                    <label class="text-center">
                        <input type="checkbox" v-model="havePng">&nbsp;Do you have PNG and JSON files for this sample?
                    </label>
                    <div v-if="havePng">
                        <hr class="dark horizontal my-0">
                        <div>
                            <file-upload ref="png" info="Select the .png file to be imported" file-types=".png" :show-upload-button="false" :is-required="false" @validated="statusPng" @uploaded="uploadedPng"></file-upload>
                        </div>

                        <hr class="dark horizontal my-0">
                        <div>
                            <file-upload ref="json" info="Select the .json file to be imported" file-types=".json" :show-upload-button="false" :is-required="false" @validated="statusJson" @uploaded="uploadedJson"></file-upload>
                        </div>
                    </div>

                    <div class="p-3 w-100 text-end">
                        <button @click="importSample" class="btn bg-gradient-success w-25 mb-0 toast-btn" :disabled="!canStartImportProcess">
                            Import sample
                        </button>
                    </div>

                    <div class="p-3">
                        <h5>Samples in this project</h5>
                        <div v-for="sample in samples">
                            <div class="">
                                <span class="text-bolder">{{ sample.name ?? 'Sample ' + sample.id }}</span>
                                <div v-for="file in sample.file_list" class="ps-2 pb-1">
                                    <span class="text-info">{{ file.filename }}</span>
                                </div>
                            </div>
                            <hr class="dark horizontal my-0">
                        </div>
                        <div v-if="!samples.length">
                            You haven't uploaded any samples yet!
                        </div>
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
        name: 'importData',

        props: {
            project: Object,
            samples: Object,
        },

        data() {
            return {
                validH5: false,
                validCsv: false,
                havePng: false,
                validPng: false,
                validJson: false,

                uploadedH5Id: 0,
                uploadedCsvId: 0,
                uploadedPngId: 0,
                uploadedJsonId: 0,

            };
        },

        computed: {
            canStartImportProcess() {
                return (this.validH5 && this.validCsv) && (!this.havePng || (this.validPng && this.validJson));
            },

            /*uploadProcessFinished() {
                console.log((this.uploadedH5Id && this.uploadedCsvId) + 'uploadProcessFinished --H5id:' + this.uploadedH5Id + '--CSVid:' + this.uploadedCsvId + '--' );
                return (this.uploadedH5Id && this.uploadedCsvId);
            },*/
        },

        methods: {
            statusH5: function(isValid) { this.validH5 = isValid; console.log('H5: ' + isValid); },
            statusCsv: function(isValid) { this.validCsv = isValid; console.log('CSV: ' + isValid); },
            statusPng: function(isValid) { this.validPng = isValid; console.log('PNG: ' + isValid); },
            statusJson: function(isValid) { this.validJson = isValid; console.log('JSON: ' + isValid); },

            uploadedH5: function(file_id) {this.uploadedH5Id = file_id; console.log('H5id: ' + this.uploadedH5Id); this.checkIfFinishedUploading(); },
            uploadedCsv: function(file_id) {this.uploadedCsvId = file_id; console.log('CSVid: ' + this.uploadedCsvId); this.checkIfFinishedUploading();},
            uploadedPng: function(file_id) {this.uploadedPngId = file_id; console.log('PNGid: ' + this.uploadedPngId); this.checkIfFinishedUploading();},
            uploadedJson: function(file_id) {this.uploadedJsonId = file_id; console.log('JSONid: ' + this.uploadedJsonId); this.checkIfFinishedUploading();},

            importSample: function() {
                this.$refs['h5'].submitFile();
                this.$refs['csv'].submitFile();

                if(this.havePng && (this.validPng && this.validJson))
                {
                    this.$refs['png'].submitFile();
                    this.$refs['json'].submitFile();
                }

                console.log('call to importSample ended');
            },

            checkIfFinishedUploading: function() {
                if(this.uploadedH5Id && this.uploadedCsvId && (!this.havePng || (this.validPng && this.validJson))) {
                    console.log((this.uploadedH5Id && this.uploadedCsvId) + 'uploadProcessFinished --H5id:' + this.uploadedH5Id + '--CSVid:' + this.uploadedCsvId + '--');
                    axios.post('/samples', {file_ids: [this.uploadedH5Id, this.uploadedCsvId, this.uploadedPngId, this.uploadedJsonId], project_id: this.project.id})
                        .then((response) => {
                            console.log(response.data);
                            //this.samples.push(response.data);
                            location.reload();
                        })
                        .catch((error) => {
                            console.log('Error creating sample');
                        });
                }
            },
        }

    }
</script>
