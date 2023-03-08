<template>
    <div class="p-3">
        <h5>Samples in this project</h5>

        <div class="row text-bolder text-center">
            <div class="col-2 text-start">Sample</div>
            <div class="col-2">Expression</div>
            <div class="col-2">Coordinates</div>
            <div class="col-2">Scale factors</div>
            <div class="col-2">Tissue image</div>
        </div>

        <div v-for="sample in samples" class="row">
            <div class="col-2">
                {{ sample.name ?? 'Sample ' + sample.id }}
            </div>

            <div class="col-2 text-center">
                <i v-if="hasExpression" class="material-icons opacity-10 text-success">check</i>
            </div>

            <div class="col-2 text-center">
                <i v-if="hasCoordinates" class="material-icons opacity-10 text-success">check</i>
            </div>

            <div class="col-2 text-center">
                <i v-if="hasScaleFactors" class="material-icons opacity-10 text-success">check</i>
            </div>

            <div class="col-2 text-center">
                <i v-if="hasImage" class="material-icons opacity-10 text-success">check</i>
            </div>

            <hr class="dark horizontal my-0">
        </div>



<!--        <div v-for="sample in samples">-->
<!--            <div class="">-->
<!--                <span class="text-bolder">{{ sample.name ?? 'Sample ' + sample.id }}</span>-->
<!--                <div v-for="file in sample.file_list" class="ps-2 pb-1">-->
<!--                    <span class="text-info">{{ file.filename }}</span>-->
<!--                </div>-->
<!--            </div>-->
<!--            <hr class="dark horizontal my-0">-->
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
        },

        methods: {
            getFileExtension(fileName) {
                return fileName.split('.').pop();
            },

            hasFileType(type) {
                return this.files.filter(file => {return this.getFileExtension(file.name).toLowerCase() === type.toLowerCase()}).length;
            },

            hasExpression() {
                return this.hasFileType('h5');
            },

            hasCoordinates() {
                return this.hasFileType('csv') || this.hasFileType('tsv') || this.hasFileType('txt');
            },

            hasScaleFactors() {
                return this.hasFileType('json');
            },

            hasImage() {
                return this.hasFileType('png') || this.hasFileType('jpg') || this.hasFileType('jpeg');
            },

        },

    }
</script>
