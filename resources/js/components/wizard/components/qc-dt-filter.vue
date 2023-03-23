<template>
<div class="m-4">
    <form>
        <div class="accordion" id="accordionFilterTab">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSelectSamples">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                        Apply filter to samples
                    </button>
                </h2>
                <div id="collapseSelectSamples" class="accordion-collapse collapse" aria-labelledby="headingSelectSamples" data-bs-parent="#accordionFilterTab">

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70  w-xxl-40 row row-cols-2">
                            <div class="col">
                                <label for="sampleList2" class="form-label text-lg">Selected samples:</label>
                                <select ref="selectedSamples" id="sampleList2" multiple class="p-2 form-select w-100 border border-1" @click="removeSample" title="Click to remove sample">
                                    <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>
                                </select>
                            </div>
                            <div class="col">
                                <label for="sampleList1" class="form-label text-lg">Excluded samples:</label>
                                <select ref="availableSamples" id="sampleList1" multiple class="p-2 form-select w-100 border border-1" @click="addSample" title="Click to add sample">
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRemoveGenes">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRemoveGenes" aria-expanded="false" aria-controls="collapseRemoveGenes">
                        Remove genes by name
                    </button>
                </h2>
                <div id="collapseRemoveGenes" class="accordion-collapse collapse" aria-labelledby="headingRemoveGenes" data-bs-parent="#accordionFilterTab">

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-40 row row-cols-2">
                            <div class="col">
                                <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm" placeholder="Search genes here...">
                                <select ref="geneFilter" id="sampleList2" multiple class="p-2 form-select w-100 border border-1" @click="removeSample" title="Click to remove sample">
                                    <!--                        <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>-->
                                </select>
                            </div>
                            <div class="col align">
                                <label for="genesExcluded" class="form-label text-lg">Excluded genes:</label>
                                <select ref="genesExcluded" id="genesExcluded" multiple class="p-2 form-select w-100 border border-1" @click="addSample" title="Click to add sample">
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingRemoveGenesByRegex">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRemoveGenesByRegex" aria-expanded="false" aria-controls="collapseRemoveGenesByRegex">
                        Remove genes by token
                    </button>
                </h2>
                <div id="collapseRemoveGenesByRegex" class="accordion-collapse collapse" aria-labelledby="headingRemoveGenesByRegex" data-bs-parent="#accordionFilterTab">

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-40">
                            <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm" placeholder="RegEx here... e.g. ^MT-">
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSpotCell">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSpotCell" aria-expanded="false" aria-controls="collapseSpotCell">
                        Spot/Cell
                    </button>
                </h2>
                <div id="collapseSpotCell" class="accordion-collapse collapse" aria-labelledby="headingSpotCell" data-bs-parent="#accordionFilterTab">

                    <div class="m-4 gap-1">
                        <div class="row row-cols-2">
                            <div class="col border border-2 border-start-0 border-top-0 border-bottom-0">
                                <numeric-range title="Counts" :start-min="0" :start-max="10000" :start-step="200" :start-default="params.spot_minreads" start-label="min" :end-min="0" :end-max="10000" :end-step="200" :end-default="params.spot_maxreads" end-label="max" @updated="(min,max) => {params.spot_minreads = min; params.spot_maxreads = max}"></numeric-range>
                            </div>
                            <div class="col">
                                <numeric-range title="Genes" :start-min="0" :start-max="40000" :start-step="500" :start-default="params.spot_mingenes" start-label="min" :end-min="0" :end-max="40000" :end-step="500" :end-default="params.spot_maxgenes" end-label="max" @updated="(min,max) => {params.spot_mingenes = min; params.spot_maxgenes = max}"></numeric-range>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingGeneCountSpots">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneCountSpots" aria-expanded="false" aria-controls="collapseGeneCountSpots">
                        Gene
                    </button>
                </h2>
                <div id="collapseGeneCountSpots" class="accordion-collapse collapse" aria-labelledby="headingGeneCountSpots" data-bs-parent="#accordionFilterTab">

                    <div class="m-4 gap-1">
                        <div class="row row-cols-2">
                            <div class="col border border-2 border-start-0 border-top-0 border-bottom-0">
                                <numeric-range title="Counts" :start-min="0" :start-max="10000" :start-step="500" :start-default="params.gene_minreads" start-label="min" :end-min="0" :end-max="10000" :end-step="500" :end-default="params.gene_maxreads" end-label="max" @updated="(min,max) => {params.gene_minreads = min; params.gene_maxreads = max}"></numeric-range>
                            </div>
                            <div class="col">
                                <numeric-range title="Spots" :start-min="0" :start-max="6000" :start-step="100" :start-default="params.gene_minspots" start-label="min" :end-min="0" :end-max="6000" :end-step="100" :end-default="params.gene_maxspots" end-label="max" @updated="(min,max) => {params.gene_minspots = min; params.gene_maxspots = max}"></numeric-range>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingGenePct">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGenePct" aria-expanded="false" aria-controls="collapseGenePct">
                        Spot/Cell and Genes by percentage
                    </button>
                </h2>
                <div id="collapseGenePct" class="accordion-collapse collapse" aria-labelledby="headingGenePct" data-bs-parent="#accordionFilterTab">

                    <div class="m-4 gap-1">
                        <div class="row row-cols-2">

                            <div class="col border border-2 border-start-0 border-top-0 border-bottom-0">
                                <div>
                                    Filter spots/cells by percentage of gene <input type="text" placeholder="^MT-">
                                </div>
                                <numeric-range title="" :start-min="0" :start-max="100" :start-step="1" :start-default="params.spot_minpct" start-label="min" :end-min="0" :end-max="100" :end-step="1" :end-default="params.spot_maxpct" end-label="max" @updated="(min,max) => {params.spot_minpct = min; params.spot_maxpct = max}"></numeric-range>
                            </div>
                            <div class="col">
                                <numeric-range title="Filter genes by percentage of zero spots" :start-min="0" :start-max="100" :start-step="1" :start-default="params.gene_minpct" start-label="min" :end-min="0" :end-max="100" :end-step="1" :end-default="params.gene_maxpct" end-label="max" @updated="(min,max) => {params.gene_minpct = min; params.gene_maxpct = max}"></numeric-range>
                            </div>
                            <div class="col">

                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>







<!--        <img id="imgResult">-->

<!--        <div>-->
<!--            <pre>-->
<!--            {{ textOutput }}-->
<!--            </pre>-->
<!--        </div>-->

        <div>
            <div class="text-center w-100 w-md-40 w-lg-30 w-xl-20">
                <button type="button" class="btn btn-lg bg-gradient-info btn-lg w-100 mt-4 mb-0" @click="startProcess" :disabled="processing">{{ processing ? 'Please wait...' : 'Apply Filter' }}</button>
            </div>
        </div>

        <div class="mt-4">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="boxplot-tab" data-bs-toggle="tab" data-bs-target="#boxplot" type="button" role="tab" aria-controls="boxplot" aria-selected="true">Boxplot</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="violinplot-tab" data-bs-toggle="tab" data-bs-target="#violinplot" type="button" role="tab" aria-controls="violinplot" aria-selected="false">Violin plots</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="histograms-tab" data-bs-toggle="tab" data-bs-target="#histograms" type="button" role="tab" aria-controls="histograms" aria-selected="false">Histograms</button>
                </li>

            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="boxplot" role="tabpanel" aria-labelledby="boxplot-tab">
                    <div class="m-4">
                        Color palette:
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Blue-Red
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Yellow-Orange
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Rainbow
                        </label>
                    </div>
                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
                    </div>
                </div>
                <div class="tab-pane fade" id="violinplot" role="tabpanel" aria-labelledby="violinplot-tab">
                    <div class="m-4">
                        Color palette:
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Blue-Red
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Yellow-Orange
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Rainbow
                        </label>
                    </div>
                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
                    </div>
                </div>
                <div class="tab-pane fade" id="histograms" role="tabpanel" aria-labelledby="histograms-tab">
                    <div class="m-4">
                        Color palette:
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Blue-Red
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Yellow-Orange
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Rainbow
                        </label>
                    </div>
                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
                    </div>
                </div>
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

                range: [-1,5],

                params: {
                    spot_minreads: 0,
                    spot_maxreads: 10000,

                    spot_mingenes: 0,
                    spot_maxgenes: 40000,

                    gene_minreads: 0,
                    gene_maxreads: 10000,

                    gene_minspots: 0,
                    gene_maxspots: 6000,

                    spot_minpct: 0,
                    spot_maxpct: 100,
                    spot_pct_expr: '',

                    gene_minpct: 0,
                    gene_maxpct: 100,
                },

                processing: false,

                //samplesToProcess: [],

                textOutput: ''
            }
        },

        /*computed: {
            samplesToFilter() {
                console.log(this.$refs.selectedSamples.options)
            },
        },*/


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


                /*let _params = ['spot_minreads', 'spot_maxreads'];
                _params.forEach(
                    (param) => {
                        console.log(this[param]);
                    }
                );*/



                // let data = {
                //     'sample_id': this.samplesToProcess.length  ? this.samplesToProcess[0].value : '',
                //
                // };

                axios.post(this.filterUrl)
                    .then((response) => {
                        //document.getElementById("imgResult").src = 'data:image/png;base64,' + response.data.image;

                        //this.textOutput = response.data.output;

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
