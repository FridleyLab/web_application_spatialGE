<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-sm-10 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_1</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">Importing data</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5>Samples for project: <span class="text-primary">{{ project.name }}</span></h5>
                    </div>

                    <div v-if="samples.length" class="p-3">
                        <input type="button" class="btn" :class="showAddSample ? 'btn-outline-info' : 'btn-info'" @click="showAddSample = !showAddSample" :value=" showAddSample ? 'Hide sample form' : 'Add sample'" />
                    </div>

                    <template v-if="!samples.length || showAddSample">
                        <h6 class="text-center">Please select or drop your Expression and Coordinates files (image is optional)</h6>
                        <hr class="dark horizontal my-0">

                        <div class="container" style="max-width: 600px">
                            <div class="my-2 row">
                                <div class="col-3">
                                    <file-upload-drag-drop code="expression" :project="project" caption="Expression" :required="true" @fileSelected="expressionFileAdded" @fileRemoved="expressionFileRemoved"></file-upload-drag-drop>
                                </div>
                                <div class="col-3">
                                    <file-upload-drag-drop code="coordinates" :project="project" caption="Coordinates" :required="true" @fileSelected="coordinatesFileAdded" @fileRemoved="coordinatesFileRemoved"></file-upload-drag-drop>
                                </div>
                                <div class="col-3">
                                    <file-upload-drag-drop code="image" :project="project" caption="Image" @fileSelected="imageFileAdded" @fileRemoved="imageFileRemoved"></file-upload-drag-drop>
                                </div>
                                <div class="col-3">
                                    <file-upload-drag-drop code="scale" :project="project" caption="Scale factor" @fileSelected="scaleFileAdded" @fileRemoved="scaleFileRemoved"></file-upload-drag-drop>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-center">
                        <div class="w-30 mt-1" v-show="!uploading">
                            <div class="input-group input-group-outline focused is-focused">
                                <label class="form-label">Sample name (optional)</label>
                                <input type="text" class="form-control" name="sample_name" v-model="sample_name">
                            </div>
                        </div>
                        </div>


                        <progress v-show="uploading" max="100" :value.prop="uploadPercentage"></progress>

                        <div class="mt-3 w-100 text-center">
                            <button @click="importSample" class="btn bg-gradient-success w-25 mb-0 toast-btn" :disabled="!canStartImportProcess">
                                Import sample
                            </button>
                        </div>
                    </template>

                    <project-samples v-if="!showAddSample" :samples="samples"></project-samples>

                    <div v-if="samples.length" class="p-3 text-end">
                        <input v-if="!changingStep" type="button" class="btn btn-outline-success" @click="changingStep = true" value="Next step: QC & data transformation" />

                        <input v-if="changingStep" type="button" class="btn btn-outline-warning me-2" @click="nextStep" value="Finished importing data, proceed" />
                        <input v-if="changingStep" type="button" class="btn btn-outline-danger" @click="changingStep = false" value="Cancel" />

                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'importData',

        props: {
            project: Object,
            samples: Object,
            nexturl: String,
        },

        data() {
            return {

                showAddSample: false,

                changingStep: false,

                expressionFile: null,
                coordinatesFile: null,
                imageFile: null,
                scaleFile: null,

                sample_name: '',
                uploading: false,
                uploadPercentage: 0,

            };
        },

        computed: {

            canStartImportProcess() {
                return this.expressionFile && this.coordinatesFile;
            },

        },

        methods: {

            importSample(){
                /*
                    Initialize the form data
                */
                let formData = new FormData();


                /*
                    Iterate over any file sent over appending the files
                    to the form data.
                */
                let i = 0;
                if(this.expressionFile) {
                    formData.append('files[' + i + ']', this.expressionFile);
                    formData.append('expressionFile', this.expressionFile.name);
                    i++;
                }
                if(this.coordinatesFile) {
                    formData.append('files[' + i + ']', this.coordinatesFile);
                    formData.append('coordinatesFile', this.coordinatesFile.name);
                    i++;
                }
                if(this.imageFile) {
                    formData.append('files[' + i + ']', this.imageFile);
                    formData.append('imageFile', this.imageFile.name);
                    i++;
                }
                if(this.scaleFile) {
                    formData.append('files[' + i + ']', this.scaleFile);
                    formData.append('scaleFile', this.scaleFile.name);
                    i++;
                }


                formData.append('project_id', this.project.id);
                formData.append('sample_name', this.sample_name.trim());

                this.uploading = true;

                /*
                    Make the request to the POST /file-drag-drop URL
                */
                axios.post( '/samples',
                    formData,
                    {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: function( progressEvent ) {
                            this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ) );
                        }.bind(this)
                    }
                ).then((response) => {
                    console.log('SUCCESS!!');
                    console.log(response.data);
                    location.reload();
                })
                    .catch((error) => {
                        console.log('FAILURE!!');
                        this.uploading = false;
                    });
            },

            expressionFileAdded(file) {
                this.expressionFile = file;
                console.log(file.name);
            },

            expressionFileRemoved() {
                this.expressionFile = null;
                console.log('Removed Expr');
            },

            coordinatesFileAdded(file) {
                this.coordinatesFile = file;
                console.log(file.name);
            },

            coordinatesFileRemoved() {
                this.coordinatesFile = null;
                console.log('Removed Expr');
            },

            imageFileAdded(file) {
                this.imageFile = file;
                console.log(file.name);
            },

            imageFileRemoved() {
                this.imageFile = null;
                console.log('Removed Expr');
            },

            scaleFileAdded(file) {
                this.scaleFile = file;
                console.log(file.name);
            },

            scaleFileRemoved() {
                this.scaleFile = null;
                console.log('Removed Expr');
            },

            nextStep() {

                axios.get(this.nexturl)
                    .then((response) => {
                        location.href = response.data;
                    })

            }
        }

    }
</script>
