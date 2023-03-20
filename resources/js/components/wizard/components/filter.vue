<template>
<div class="m-4">

    <div class="row row-cols-3">
        <div class="w-40">
            <label for="sampleList" class="form-label">Select a sample:</label>
            <select id="sampleList" multiple class="form-select w-100 w-md-40 border border-1" @change="selectedSamples">
                <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>
            </select>
        </div>

<!--        <div class="w-20"></div>-->

<!--        <div class="w-40 text-center">-->
<!--            <select multiple class="w-100 w-md-40">-->
<!--                <option v-for="sample in samples" value="sample.id">{{ sample.name }}</option>-->
<!--            </select>-->
<!--        </div>-->
    </div>

    <div class="mt-4">
        <label for="customRange1" class="form-label">Spot/cell Min: {{ spotMinreads }}</label>
        <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange1" v-model="spotMinreads">
    </div>
    <div>
        <label for="customRange2" class="form-label">Spot/cell Max: {{ spotMaxreads }}</label>
        <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange2" v-model="spotMaxreads">
    </div>

    <img id="imgResult">

    <div>
        <pre>
        {{ textOutput }}
        </pre>
    </div>

    <div>
        <div class="text-center w-100 w-md-40 w-lg-30 w-xl-20">
            <button type="button" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0" @click="startProcess" :disabled="processing">{{ processing ? 'Please wait...' : 'Apply Filter' }}</button>
        </div>
    </div>
</div>
</template>
<script>
    export default {
        name: 'qcDtFilter',

        props: {
            samples: Object,
            filterUrl: String
        },

        data() {
            return {
                spotMinreads: 4000,
                spotMaxreads: 25000,

                processing: false,

                samplesToProcess: [],

                textOutput: ''
            }
        },


        methods: {
            selectedSamples(e) {

                this.samplesToProcess = Array.from(e.target.selectedOptions);

                console.log(e.target.selectedOptions);

                Array.from(e.target.selectedOptions).forEach(function (element) {
                    console.log(element.text)
                });
            },

            startProcess() {

                this.processing = true;



                let data = {
                    'sample_id': this.samplesToProcess.length  ? this.samplesToProcess[0].value : '',
                    'spot_minreads': this.spotMinreads,
                    'spot_maxreads': this.spotMaxreads
                };

                axios.post(this.filterUrl, data)
                    .then((response) => {
                        document.getElementById("imgResult").src = 'data:image/png;base64,' + response.data.image;

                        this.textOutput = response.data.output;

                        console.log(response.data);

                        this.processing = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                    })


            },
        },

    }
</script>
