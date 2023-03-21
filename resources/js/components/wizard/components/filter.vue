<template>
<div class="m-4">
    <form>
    <div class="row justify-content-center">
        <div class="w-100 w-md-80 w-lg-70  w-xxl-40 row row-cols-2">
            <div class="col">
                <label for="sampleList1" class="form-label">Available samples:</label>
                <select ref="availableSamples" id="sampleList1" multiple class="form-select w-100 border border-1" @click="addSample" title="Double click to add sample">
    <!--                <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>-->
                </select>
            </div>

            <div class="col">
                <label for="sampleList2" class="form-label">Available samples:</label>
                <select ref="selectedSamples" id="sampleList2" multiple class="form-select w-100 border border-1" @click="removeSample" title="Double click to remove sample">
                    <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="mt-4 border border-1 rounded p-4 gap-1">
        <div class="text-bolder text-lg">Spot/cell</div>
        <div class="row row-cols-2">
            <div class="col">
                <div class="form-check form-switch float-end p-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" checked />
                    <label class="form-check-label" for="flexSwitchCheckChecked"><b>Counts</b></label>
                </div>
                <div class="mt-2">
                    <label for="customRange2" class="form-label">min:</label> <input type="number" v-model="spotMinreads" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" v-model="spotMinreads">
                </div>
                <div>
                    <label for="customRange2" class="form-label">max:</label> <input type="number" v-model="spotMaxreads" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange2" v-model="spotMaxreads">
                </div>
            </div>
            <div class="col">
                <div class="mt-4">
                    <label for="customRange1" class="form-label"><b>Genes</b> min:</label> <input type="number" v-model="spotMinGenes" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange1" v-model="spotMinGenes">
                </div>
                <div>
                    <label for="customRange2" class="form-label"><b>Genes</b> max:</label> <input type="number" v-model="spotMaxGenes" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange2" v-model="spotMaxGenes">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 p-2 border border-1 rounded">
        <div class="text-bolder text-lg">Genes</div>
        <div class="row row-cols-2">
            <div class="col">
                <div class="mt-4">
                    <label for="customRange1" class="form-label"><b>Counts</b> min:</label> <input type="number" v-model="genesMinreads" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange1" v-model="genesMinreads">
                </div>
                <div>
                    <label for="customRange2" class="form-label"><b>Counts</b> max:</label> <input type="number" v-model="genesMaxreads" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange2" v-model="genesMaxreads">
                </div>
            </div>
            <div class="col">
                <div class="mt-4">
                    <label for="customRange1" class="form-label"><b>Spots</b> min:</label> <input type="number" v-model="genesMinSpots" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange1" v-model="genesMinSpots">
                </div>
                <div>
                    <label for="customRange2" class="form-label"><b>Spots</b> max:</label> <input type="number" v-model="genesMaxSpots" class="text-end text-sm border border-1 rounded w-40 w-md-35 w-xxl-20">
                    <input type="range" min="0" max="30000" step="500" class="form-range" id="customRange2" v-model="genesMaxSpots">
                </div>
            </div>
        </div>
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
    </form>
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
                spotMinreads: 5750,
                spotMaxreads: 25000,

                spotMinGenes: 2500,
                spotMaxGenes: 7500,

                genesMinreads: 5750,
                genesMaxreads: 25000,

                genesMinSpots: 2500,
                genesMaxSpots: 7500,

                processing: false,

                samplesToProcess: [],

                textOutput: ''
            }
        },

        watch: {
            spotMinreads(newValue, oldValue) {
                if(Number(this.spotMinreads) > Number(this.spotMaxreads))
                    this.spotMinreads = (Number(this.spotMaxreads) - 500) > 0 ? Number(this.spotMaxreads) - 500 : 0;
            },
            spotMaxreads(newValue, oldValue) {
                if(Number(this.spotMaxreads) < Number(this.spotMinreads))
                    this.spotMaxreads = Number(this.spotMinreads) + 500;
            }
        },


        methods: {

            removeSample(e) {
                console.log(e);
                if(e.target.index>=0)
                    this.$refs.availableSamples.add(e.target);
                this.$refs.availableSamples.selectedIndex = -1;
            },

            addSample(e) {
                console.log(e);
                if(e.target.index>=0)
                    this.$refs.selectedSamples.add(e.target);
                this.$refs.selectedSamples.selectedIndex = -1;
            },

            selectedSamples(e) {

                /*this.samplesToProcess = Array.from(e.target.selectedOptions);

                console.log(e.target.selectedOptions);

                Array.from(e.target.selectedOptions).forEach(function (element) {
                    console.log(element.text)
                });*/
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
