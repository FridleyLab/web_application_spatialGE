<template>
<div class="m-4">
    <form>

<!--        <div class="w-30 m-4">-->
<!--            <multiselect></multiselect>-->
<!--        </div>-->

        <div class="accordion" id="accordionFilterTab">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSelectSamples">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                        Select samples to apply this filter
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
                        Filter genes
                    </button>
                </h2>
                <div id="collapseRemoveGenes" class="accordion-collapse collapse" aria-labelledby="headingRemoveGenes" data-bs-parent="#accordionFilterTab">

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-40 row row-cols-2">
                            <div class="col">
                                <input type="text" class="form-control form-control-plaintext border border-1 py-1 px-2 text-sm" placeholder="Search genes here...">
                                <select ref="geneFilter" id="sampleList2" multiple class="p-2 form-select w-100 border border-1" @click="removeSample" title="Click to remove sample">
                                    <!--                        <option v-for="sample in samples" :value="sample.id">{{ sample.name }}</option>-->
                                </select>
                            </div>
                            <div class="col align">
                                <label for="genesExcluded" class="form-label">Excluded genes:</label>
                                <select ref="genesExcluded" id="genesExcluded" multiple class="p-2 form-select w-100 border border-1" @click="addSample" title="Click to add sample">
                                </select>
                            </div>
                        </div>


                        <div class="my-4 d-flex justify-content-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="chkFilterGeneRemoveMT">
                                <label class="form-check-label" for="chkFilterGeneRemoveMT">
                                    Remove mitochondrial genes (^MT-)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="chkFilterGeneRemoveRP">
                                <label class="form-check-label" for="chkFilterGeneRemoveRP">
                                    Remove ribosomal genes (^RP[L|S])
                                </label>
                            </div>
                        </div>

                        <div class="accordion w-100 w-xxl-60" id="accordionRegExFilterGenes">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingRegExFilterGenes">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegExFilterGenes" aria-expanded="false" aria-controls="collapseRegExFilterGenes">
                                        Remove genes using regular expression (advanced users)
                                    </button>
                                </h2>
                                <div id="collapseRegExFilterGenes" class="accordion-collapse collapse" aria-labelledby="headingRegExFilterGenes" data-bs-parent="#accordionRegExFilterGenes">

                                    <div class="row justify-content-center text-center m-3">
                                        <div class="row justify-content-center text-center m-3">
                                            <div class="w-100 w-md-80 w-lg-70 w-xxl-60">
                                                <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm w-100" placeholder="RegEx here... e.g. ^MT-">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="m-4 gap-1">
                            <div class="row">
                                <div class="pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range title="Keep genes with counts between:" title-class="text-bold" :min="0" :max="project.project_parameters.max_gene_counts" :step="500" @updated="(min,max) => {params.gene_minreads = min; params.gene_maxreads = max}"></numeric-range>
                                </div>
                                <div class="mt-4">
                                    <div class="text-start text-bold">Keep genes expressed in:</div>
                                    <div class="mt-2">
                                        <numeric-range title="Number of spots:" :min="0" :max="6000" :step="100" @updated="(min,max) => {params.gene_minspots = min; params.gene_maxspots = max}"></numeric-range>
                                    </div>
                                    <div class="mt-4">
                                        <numeric-range title="Percentage of spots" :min="0" :max="100" :step="1" @updated="(min,max) => {params.gene_minpct = min; params.gene_maxpct = max}"></numeric-range>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSpotCell">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSpotCell" aria-expanded="false" aria-controls="collapseSpotCell">
                        Filter Spots/Cells
                    </button>
                </h2>
                <div id="collapseSpotCell" class="accordion-collapse collapse" aria-labelledby="headingSpotCell" data-bs-parent="#accordionFilterTab">

                    <div class="m-4 gap-1">

                        <div class="m-4 gap-1">
                            <div class="row">

                                <div class="mt-2 pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range title="Keep spots/cells with this number of counts:" title-class="text-bold" :min="0" :max="10000" :step="500" @updated="(min,max) => {params.spot_minreads = min; params.spot_maxreads = max}"></numeric-range>
                                </div>

                                <div class="mt-2 pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range title="Keep spots/cells with this number of expressed genes:" title-class="text-bold" :min="0" :max="project.project_parameters.total_genes" :step="500" @updated="(min,max) => {params.spot_mingenes = min; params.spot_maxgenes = max}"></numeric-range>
                                </div>

                                <div class="mt-4">




                                    <div class="text-start text-bold mt-4">Keep spots/cells by percentage of specific genes:</div>
                                    <div class="mt-4 justify-content-center row row-cols-2">
                                        <div class=" col my-4 justify-content-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="chkFilterSpotRemoveMT">
                                                <label class="form-check-label" for="chkFilterSpotRemoveMT">
                                                    Remove mitochondrial genes (^MT-)
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="chkFilterSpotRemoveRP">
                                                <label class="form-check-label" for="chkFilterSpotRemoveRP">
                                                    Remove ribosomal genes (^RP[L|S])
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col mt-3">
                                        <div class="accordion w-100 w-xl-70" id="accordionRegExFilterSpots">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingRegExFilterSpots">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegExFilterSpots" aria-expanded="false" aria-controls="collapseRegExFilterSpots">
                                                        Regular expression (advanced users)
                                                    </button>
                                                </h2>
                                                <div id="collapseRegExFilterSpots" class="accordion-collapse collapse" aria-labelledby="headingRegExFilterSpots" data-bs-parent="#accordionRegExFilterSpots">

                                                    <div class="row justify-content-center text-center m-3">
                                                        <div class="row justify-content-center text-center m-3">
                                                            <div class="w-100 w-md-80 w-lg-70 w-xxl-60">
                                                                <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm w-100" placeholder="RegEx here... e.g. ^MT-">
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <numeric-range title="" :min="0" :max="100" :step="1" @updated="(min,max) => {params.spot_minpct = min; params.spot_maxpct = max}"></numeric-range>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>



        </div>


        <div class="row">
            <div class="w-100 my-3">
                <label>
                    Name this filter: <input type="text" class="border border-1 rounded p-1">
                </label>
                <div>
                <input type="button" class="btn btn-sm btn-outline-info" value="Save filter">
                </div>
            </div>
        </div>




<!--        <img id="imgResult">-->

<!--        <div>-->
<!--            <pre>-->
<!--            {{ textOutput }}-->
<!--            </pre>-->
<!--        </div>-->




        <div class="row">
            <div class="w-100">
                <div class="text-center w-100 w-md-40 w-lg-30 w-xl-20 float-end">
                    <button type="button" class="btn btn-lg bg-gradient-info w-100 mt-4 mb-0" @click="startProcess" :disabled="processing">{{ processing ? 'Please wait...' : 'Apply Filters' }}</button>
                </div>
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
<!--                <li class="nav-item" role="presentation">-->
<!--                    <button class="nav-link" id="histograms-tab" data-bs-toggle="tab" data-bs-target="#histograms" type="button" role="tab" aria-controls="histograms" aria-selected="false">Histograms</button>-->
<!--                </li>-->

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
<!--                <div class="tab-pane fade" id="histograms" role="tabpanel" aria-labelledby="histograms-tab">-->
<!--                    <div class="m-4">-->
<!--                        Color palette:-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Blue-Red-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Yellow-Orange-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Rainbow-->
<!--                        </label>-->
<!--                    </div>-->
<!--                    <div class="text-center">-->
<!--                        <pre>-->



<!--                        Under development (spatialGE library)-->



<!--                        </pre>-->
<!--                    </div>-->
<!--                </div>-->
            </div>
        </div>

    </form>
</div>
</template>
<script>
    export default {
        name: 'qcDtFilter',

        props: {
            project: Object,
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
