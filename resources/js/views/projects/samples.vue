<template>
    <div class="px-3 pb-3">
<!--        <h5>Samples in this project</h5>-->

        <div v-if="samples.length" class="row text-bolder text-center">
            <div class="col-2 text-start">Sample</div>
            <div class="col-2">Expression</div>
            <div class="col-2">Coordinates</div>
            <div class="col-2">Scale factors</div>
            <div class="col-2">Tissue image</div>
            <div class="col-2"></div>
        </div>

        <div v-for="sample in samples" class="row">
            <div class="col-2">
                {{ sample.name ?? 'Sample ' + sample.id }}
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
                <i v-if="!deleting" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deleting = sample.id">delete</i>

                <input v-if="deleting === sample.id" type="button" class="btn btn-sm btn-outline-success text-xxs" value="Cancel" @click="deleting = 0" title="Cancel deletion attempt" />
                <input v-if="deleting === sample.id" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2" value="Delete" title="Confirm deletion of this sample" @click="deleteSample(sample)" />

            </div>

            <hr class="dark horizontal my-0">
        </div>


        <div v-if="samples.length" class="mt-5">
            <div class="text-info text-bolder">Add relevant metadata for the samples</div>
            <table class="table table-responsive table-striped">
                <thead>
                <tr>
                    <td></td>
                    <td v-for="index in metadata.length" class="text-center">
                        <i v-if="!deletingMetadata" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deletingMetadata = index">delete</i>
                        <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-success text-xxs p-1" value="Cancel" @click="deletingMetadata = 0" title="Cancel deletion attempt" />
                        <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2 p-1" value="Delete" title="Confirm deletion of this sample" @click="deleteMetadata(index -1)" />
                    </td>
                </tr>
                <tr>
                    <th>Sample/Metadata</th>
                    <td v-for="index in metadata.length" class="text-center">
                        <input type="text" class="border border-info border-1 rounded rounded-2 px-2" :style="'width:' + (activeColumn===index ? '120' : '60') + 'px'" :value="('name' in metadata[index-1]) ? metadata[index-1].name : ''" @input="setMetadataName($event, index - 1 )" @focus="activeColumn = index" @focusout="activeColumn = -1" />
<!--                        <a v-if="activeColumnHeader !== index" class="btn btn-outline-info" @click="activeColumnHeader = index">{{ metadata[index-1].name }}</a>-->
                    </td>
                </tr>
                </thead>
                <tbody>
                <tr v-for="sample in samples" :key="sample.id">
                    <th>
                        {{ sample.name ?? 'Sample ' + sample.id }}
<!--                        <input type="text" class="border border-info border-1 rounded rounded-2 px-2" :value="sample.name ?? 'Sample ' + sample.id" @input="" />-->
                    </th>
                    <td v-for="index in metadata.length" class="text-center">
                        <input type="text" class="border border-1 rounded rounded-2 px-2" :style="'width:' + (activeColumn===index ? '120' : '60') + 'px'" :value="(('values' in metadata[index-1]) && sample.name in metadata[index-1].values) ? metadata[index-1].values[sample.name] : ''" @input="setMetadataValue($event, index - 1, sample.name)" :disabled="metadata.length<index || !metadata[index-1].name.trim().length" @focus="activeColumn = index" @focusout="activeColumn = -1" />
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="btn btn-sm btn-outline-secondary" @click="addMetadata">Add</button>
        </div>


<!--        <div v-if="samples.length" class="mt-5">-->
<!--            <div class="text-info text-bolder">Add relevant metadata for the samples</div>-->
<!--            <table class="table table-responsive table-striped">-->
<!--                <thead>-->
<!--                    <tr>-->
<!--                        <th>Metadata</th>-->
<!--                        <th v-for="sample in samples">-->
<!--                            {{ sample.name ?? 'Sample ' + sample.id }}-->
<!--                        </th>-->
<!--                    </tr>-->
<!--                </thead>-->
<!--                <tbody>-->
<!--                    <tr v-for="index in metadata.length" :key="index">-->
<!--                        <th>-->
<!--                            <input type="text" class="border border-info border-1 rounded rounded-2 px-2" :value="('name' in metadata[index-1]) ? metadata[index-1].name : ''" @input="setMetadataName($event, index - 1 )" />-->
<!--                        </th>-->
<!--                        <td v-for="sample in samples">-->
<!--                            <input type="text" class="border border-1 rounded rounded-2 px-2" :value="(('values' in metadata[index-1]) && sample.name in metadata[index-1].values) ? metadata[index-1].values[sample.name] : ''" @input="setMetadataValue($event, index - 1, sample.name)" :disabled="metadata.length<index || !metadata[index-1].name.trim().length" />-->
<!--                        </td>-->
<!--                        <td>-->
<!--                            <i v-if="!deletingMetadata" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deletingMetadata = index">delete</i>-->

<!--                            <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-success text-xxs" value="Cancel" @click="deletingMetadata = 0" title="Cancel deletion attempt" />-->
<!--                            <input v-if="deletingMetadata === index" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2" value="Delete" title="Confirm deletion of this sample" @click="deleteMetadata(index -1)" />-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                </tbody>-->
<!--            </table>-->
<!--            <button class="btn btn-sm btn-outline-secondary" @click="addMetadata">Add</button>-->
<!--        </div>-->

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
            project: Object
        },

        data() {
            return {
                deleting: 0,
                deletingMetadata: 0,
                //metadataCount: 0,
                metadata: ('metadata' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.metadata) :  [],

                activeColumn: -1,
                //activeColumnHeader: -1,
            }
        },

        methods: {

            deleteMetadata(index) {
                this.metadata.splice(index,1);
                this.deletingMetadata = 0;
                this.saveMetadata();
            },

            setMetadataName(event, index) {

                this.metadata[index] = {'name' : event.target.value /*, 'values' : {}*/};
                if(!('values' in this.metadata[index])) this.metadata[index].values = {};

                this.saveMetadata();

                //console.log(this.metadata, index);
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
                    .then((response) => {console.log(response.data);})
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

        },

    }
</script>
