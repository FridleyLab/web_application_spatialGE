<template>
    <div class="px-3 pb-3">

        <div>

            <div v-if="samples.length" class="row text-bolder text-center">
                <div class="col-2 text-start">Sample</div>
                <div class="col-2">Expr<span class="d-none d-lg-inline">ession</span></div>
                <div class="col-2">Coord<span class="d-none d-lg-inline">inates</span></div>
                <div class="col-2">Scaling<span class="d-none d-lg-inline"> factors</span></div>
                <div class="col-2">Tissue<span class="d-none d-lg-inline"> image</span></div>
                <div class="col-2"></div>
            </div>

            <div v-for="sample in samples" class="row">
                <div class="col-2 text-truncate" :title="sample.name ?? 'Sample ' + sample.id">
                    <template v-if="project.current_step === 1">
                        <a v-if="!idEditingSampleName && !renamingSample" href="#" @click="idEditingSampleName = sample.id" class="me-1">
                            <i class="material-icons text-secondary fs-5 opacity-20">edit</i>
                        </a>
                        <a v-if="idEditingSampleName === sample.id  && !renamingSample" href="#" @click="idEditingSampleName = 0" class="me-1">
                            <i class="material-icons opacity-10 text-danger">cancel</i>
                        </a>
                        <template v-if="idEditingSampleName !== sample.id">
                            {{ sample.name ?? 'Sample ' + sample.id }}
                        </template>
                        <template v-if="idEditingSampleName === sample.id && renamingSample">
                            Please wait...
                        </template>
                        <template v-if="idEditingSampleName === sample.id  && !renamingSample">
                            <input type="text" v-model="sample.name" class="w-70">
                        </template>
                        <a v-if="idEditingSampleName === sample.id  && !renamingSample" href="#" @click="changeSampleName(sample)" class="ms-1">
                            <i class="material-icons opacity-10 text-success">check</i>
                        </a>
                    </template>
                    <template v-if="project.current_step !== 1">
                        {{ sample.name ?? 'Sample ' + sample.id }}
                    </template>
                </div>

                <div class="col-2 text-center">
                    <i v-if="hasExpression(sample)" class="material-icons opacity-10 text-success">check</i>
                </div>

                <div class="col-2 text-center">
                    <i v-if="hasCoordinates(sample)" class="material-icons opacity-10 text-success">check</i>
                </div>

                <div class="col-2 text-center">
                    <i v-if="hasScaleFactors(sample)" class="material-icons opacity-10 text-success">check</i>
                </div>

                <div class="col-2 text-center">
                    <i v-if="hasImage(sample)" class="material-icons opacity-10 text-success">check</i>
                </div>

                <div class="col-2 text-center">
                    <template v-if="!disabled && project.current_step === 1">
                        <i v-if="!deleting" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deleting = sample.id">delete</i>
                        <input v-if="deleting === sample.id" type="button" class="btn btn-sm btn-outline-success text-xxs" value="Cancel" @click="deleting = 0" title="Cancel deletion" />
                        <input v-if="deleting === sample.id" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2" value="Delete" title="Confirm deletion of this sample" @click="deleteSample(sample)" />
                    </template>
                </div>

                <hr class="dark horizontal my-0" />
            </div>

        </div>


        <div v-if="samples.length" class="table-responsive mt-5">
            <div>
                <h5 v-if="project.current_step !== 1">Sample-level metadata</h5>
                <h5 v-if="project.current_step === 1">Optional: Add sample-level metadata</h5>
                <ul class="mt-3" v-if="project.current_step === 1">
                    <li v-if="!addingMetadataManually">
                        <button v-if="!disabled" class="btn btn-sm" :class="addingMetadataFile ? 'btn-outline-warning' : 'btn-outline-info'" @click="addingMetadataFile = !addingMetadataFile">{{ addingMetadataFile ? 'Cancel metadata file upload' : 'Option 1: Upload metadata file (csv/excel)'}}</button><show-modal tag="importdata_metadata_file"></show-modal>
                        <div v-if="addingMetadataFile" class="mb-5">
                            <div class="max-width-300">
                                <div class="mb-2 text-center">
                                    <span class="text-warning">Warning:</span> Uploading a file will erase any manually added metadada
                                </div>
                                <file-upload-drag-drop code="metadata" :excel-metadata-url="excelMetadataUrl" :number-of-samples="samples.length" :project="project" caption="Metadata CSV" :required="true" @fileSelected="metadataFileAdded" @fileRemoved="metadataFileRemoved" tooltip="A CSV file containing a table with sample metadata"></file-upload-drag-drop>
                            </div>
                        </div>
                    </li>
                    <li v-if="!addingMetadataFile">

                        <button v-if="!disabled && addingMetadataManually" class="btn btn-sm btn-outline-info me-3" @click="addMetadata">Add new metadata column</button>

                        <button v-if="!disabled" class="btn btn-sm" :class="addingMetadataManually ? 'btn-outline-success' : 'btn-outline-info'" @click="addingMetadataManually = !addingMetadataManually">{{ addingMetadataManually ? 'metadata complete' : 'Option 2: Add metadata manually'}}</button><show-modal tag="importdata_metadata_manually"></show-modal>

                    </li>
                </ul>
            </div>




            <table class="table table-striped">
                <thead>
                <tr>
                    <td></td>
                    <td v-for="index in metadata.length" class="text-center">
                        <template v-if="!disabled && project.current_step === 1">
                            <i v-if="!deletingMetadata" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deletingMetadata = index">delete</i>
                            <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-success text-xxs p-1" value="Cancel" @click="deletingMetadata = 0" title="Cancel deletion attempt" />
                            <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2 p-1" value="Delete" title="Confirm deletion of this sample" @click="deleteMetadata(index -1)" />
                        </template>
                    </td>
                </tr>
                <tr>
                    <th title="Sample/Metadata"><span class="d-none d-md-inline">Sample/Metadata</span><span class="d-inline d-md-none">S/M</span></th>
                    <td v-for="index in metadata.length" class="text-center">
                        <input v-if="project.current_step === 1" :disabled="disabled" type="text" class="border border-info border-1 rounded rounded-2 px-2" :style="'width:' + (activeColumn===index ? '180' : '120') + 'px'" :value="('name' in metadata[index-1]) ? metadata[index-1].name : ''" @input="setMetadataName($event, index - 1 )" @focus="activeColumn = index" @focusout="activeColumn = -1" />
                        <template v-if="project.current_step !== 1">{{ metadata[index-1].name }}</template>
<!--                        <a v-if="activeColumnHeader !== index" class="btn btn-outline-info" @click="activeColumnHeader = index">{{ metadata[index-1].name }}</a>-->
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="sample in samples" :key="sample.id">
                    <th class="text-truncate">
                        {{ sample.name ?? 'Sample ' + sample.id }}
<!--                        <input type="text" class="border border-info border-1 rounded rounded-2 px-2" :value="sample.name ?? 'Sample ' + sample.id" @input="" />-->
                    </th>
                    <td v-for="index in metadata.length" class="text-center">
                        <template v-if="project.current_step === 1">
                            <input type="text" class="border border-1 rounded rounded-2 px-2" :style="'width:' + (activeColumn===index ? '180' : '120') + 'px'" :value="(('values' in metadata[index-1]) && sample.name in metadata[index-1].values) ? metadata[index-1].values[sample.name] : ''" @input="setMetadataValue($event, index - 1, sample.name)" :disabled="metadata.length<index || !metadata[index-1].name.trim().length || disabled" @focus="activeColumn = index" @focusout="activeColumn = -1" />
                        </template>
                        <template v-if="project.current_step !== 1">{{ metadata[index-1].values[sample.name] }}</template>
                    </td>
                </tr>
                </tbody>
            </table>

        </div>

        <div v-if="!samples.length">
            You haven't uploaded any samples yet!
        </div>
    </div>
</template>

<script>
    export default {
        name: 'projectSamples',

        props: {
            samples: Object,
            project: Object,
            disabled: {type: Boolean, default: false},
            excelMetadataUrl: String,
        },

        data() {
            return {
                deleting: 0,
                allSamplesUploaded: false,

                deletingMetadata: 0,
                //metadataCount: 0,
                metadata: ('metadata' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.metadata) :  [],
                addingMetadataFile: false,
                addingMetadataManually: false,

                activeColumn: -1,
                //activeColumnHeader: -1,

                idEditingSampleName: 0,
                renamingSample: false,
            }
        },

        methods: {

            deleteMetadata(index) {
                this.metadata.splice(index,1);
                this.deletingMetadata = 0;
                this.saveMetadata();
            },

            setMetadataName(event, index) {

                this.metadata[index].name = event.target.value;
                if(!('values' in this.metadata[index])) this.metadata[index].values = {};

                this.saveMetadata();

            },

            setMetadataValue(event, index, sampleName) {

                if(!('values' in this.metadata[index])) this.metadata[index].values = {};

                this.metadata[index].values[sampleName] = event.target.value;

                this.saveMetadata();

                //console.log(this.metadata, index);
            },

            addMetadata() {
                this.metadata[this.metadata.length] = {'name' : '', 'values' : {}};
            },

            saveMetadata: _.debounce(function() {
                axios.post('/projects/' + this.project.id + '/save-metadata', {metadata: this.metadata})
                    //.then((response) => {console.log(response.data);})
                    .catch((error) => console.log(error));

            }, 1000),

            getFileExtension(fileName) {
                return fileName.split('.').pop();
            },

            hasFileType(sample, type) {
                //return true;
                return sample.file_list.filter(file => {return this.getFileExtension(file.filename).toLowerCase() === type.toLowerCase()}).length > 0;
            },

            hasExpression(sample) {
                return this.hasFileType(sample,'h5');
            },

            hasCoordinates(sample) {
                return this.hasFileType(sample,'csv') || this.hasFileType(sample,'tsv') || this.hasFileType(sample,'txt');
            },

            hasScaleFactors(sample) {
                return this.hasFileType(sample,'json');
            },

            hasImage(sample) {
                return this.hasFileType(sample,'png') || this.hasFileType(sample,'jpg') || this.hasFileType(sample,'jpeg');
            },

            deleteSample(sample) {
                axios.delete('/samples/' + sample.id)
                    .then((response) => {console.log(response.data); location.reload()})
                    .catch((error) => {console.log(error.message)});
            },

            metadataFileAdded(file, metadata) {
                this.metadata = metadata;
                this.saveMetadata();
                this.addingMetadataFile = false;
            },

            metadataFileRemoved() {
                console.log('metadataFileRemoved');
            },

            changeSampleName(sample) {

                this.renamingSample = true;

                axios.post('/samples/' + sample.id + '/rename', {new_name: sample.name})
                    .then((response) => {
                        console.log(response.data);
                        this.idEditingSampleName = 0;
                        this.renamingSample = false;
                    })
                    .catch((error) => console.log(error));

            },

        },

    }
</script>
