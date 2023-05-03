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

        <div v-if="samples.length" class="mt-4">
            <div>Add relevant metadata for the samples</div>
            <table class="table table-responsive table-striped">
                <thead>
                    <tr>
                        <th>Metadata</th>
                        <th v-for="sample in samples">
                            {{ sample.name ?? 'Sample ' + sample.id }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="index in metaDataCount" :key="index">
                        <th>
                            <input type="text" class="border border-info border-1 rounded rounded-2 px-2" />
                        </th>
                        <td v-for="sample in samples">
                            <input type="text" class="border border-1 rounded rounded-2 px-2" />
                        </td>
                        <td>
                            <i v-if="!deletingMeta" class="material-icons opacity-10 text-danger cursor-pointer" title="Delete" @click="deletingMeta = index">delete</i>

                            <input v-if="deletingMeta === index" type="button" class="btn btn-sm btn-outline-success text-xxs" value="Cancel" @click="deletingMeta = 0" title="Cancel deletion attempt" />
                            <input v-if="deletingMeta === index" type="button" class="btn btn-sm btn-outline-danger text-xxs ms-2" value="Delete" title="Confirm deletion of this sample" @click="deleteMeta(index)" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <button class="btn btn-sm btn-outline-secondary" @click="addMetaData">Add</button>
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
        },

        data() {
            return {
                deleting: 0,
                deletingMeta: 0,
                metaDataCount: 0,
            }
        },

        methods: {

            addMetaData() {
                this.metaDataCount++;
            },

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
