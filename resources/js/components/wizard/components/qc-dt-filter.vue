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
                        <div class="w-100 w-md-80 w-lg-70  w-xxl-55 row row-cols-2">
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
                                <input type="text" class="form-control form-control-plaintext border border-1 py-1 px-2 text-sm" placeholder="Search genes here..." @input="searchGenes">
                                <select ref="geneFilter" id="sampleList2" multiple class="p-2 form-select w-100 border border-1" @click="addFilterGene" title="Click to remove sample">
                                    <option v-for="gene in filter_genes" :value="gene">{{ gene }}</option>
                                </select>
                            </div>
                            <div class="col align">
                                <label for="genesExcluded" class="form-label">Excluded genes:</label>
                                <select ref="genesExcluded" id="genesExcluded" multiple class="p-2 form-select w-100 border border-1" @click="removeFilterGene" title="Click to add sample">
                                    <option v-for="gene in filter_genes_selected" :value="gene">{{ gene }}</option>
                                </select>
                            </div>
                        </div>


                        <div class="my-4 d-flex justify-content-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chkFilterGeneRemoveMT" v-model="filter_genes_regexp_remove_mt">
                                <label class="form-check-label" for="chkFilterGeneRemoveMT">
                                    Remove mitochondrial genes (^MT-)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="chkFilterGeneRemoveRP" v-model="filter_genes_regexp_remove_rp">
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
                                            <div class="w-100 w-md-80 w-lg-70 w-xxl-60 d-flex">
                                                <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm w-100" placeholder="RegEx here... e.g. ^MT-" v-model="params.rm_genes_expr" @input="filterGenesRegexp">
                                                <a class="ms-3 link-info text-lg float-end" href="https://towardsdatascience.com/regular-expressions-clearly-explained-with-examples-822d76b037b4" target="_blank">?</a>
                                            </div>
                                            <div class="mt-3" v-if="filter_genes_regexp.length">
                                                <label for="filter_genes_regexp" class="form-label">Matched genes (preview):</label>
                                                <select ref="filter_genes_regexp" id="filter_genes_regexp" multiple class="p-2 form-select w-100 border border-1" @click="removeFilterGene" title="Click to add sample">
                                                    <option v-for="gene in filter_genes_regexp" :value="gene">{{ gene }}</option>
                                                </select>
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
<!--                                        TODO: script maximum number of spots  -->
                                        <numeric-range title="Number of spots:" :min="0" :max="project.project_parameters.max_spots_number" :step="50" @updated="(min,max) => {params.gene_minspots = min; params.gene_maxspots = max}"></numeric-range>
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
                                    <numeric-range title="Keep spots/cells with this number of counts:" title-class="text-bold" :min="0" :max="project.project_parameters.max_spot_counts" :step="500" @updated="(min,max) => {params.spot_minreads = min; params.spot_maxreads = max}"></numeric-range>
                                </div>

                                <div class="mt-2 pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range title="Keep spots/cells with this number of expressed genes:" title-class="text-bold" :min="0" :max="project.project_parameters.total_genes" :step="500" @updated="(min,max) => {params.spot_mingenes = min; params.spot_maxgenes = max}"></numeric-range>
                                </div>

                                <div class="mt-4">




                                    <div class="text-start text-bold mt-4">Keep spots/cells by percentage of specific genes:</div>
                                    <div class="mt-4 justify-content-center row row-cols-2">
                                        <div class=" col my-4 justify-content-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chkFilterSpotRemoveMT" v-model="filter_spots_regexp_remove_mt">
                                                <label class="form-check-label" for="chkFilterSpotRemoveMT">
                                                    Remove spots... mitochondrial genes (^MT-)
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="chkFilterSpotRemoveRP" v-model="filter_spots_regexp_remove_rp">
                                                <label class="form-check-label" for="chkFilterSpotRemoveRP">
                                                    Remove spots ...ribosomal genes (^RP[L|S])
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
                                                            <div class="w-100 w-md-80 w-lg-70 w-xxl-60 d-flex">
                                                                <input type="text" class="form-control form-control-plaintext border border-1 px-2 text-sm w-100" placeholder="RegEx here... e.g. ^MT-" v-model="params.spot_pct_expr" @input="filterSpotsGenesRegexp">
                                                                <a class="ms-3 link-info text-lg float-end" href="https://towardsdatascience.com/regular-expressions-clearly-explained-with-examples-822d76b037b4" target="_blank">?</a>
                                                            </div>
                                                            <div class="mt-3" v-if="filter_spots_genes_regexp.length">
                                                                <label for="filter_genes_regexp" class="form-label">Matched genes (preview):</label>
                                                                <select ref="filter_genes_regexp" id="filter_genes_regexp" multiple class="p-2 form-select w-100 border border-1" title="Click to add sample">
                                                                    <option v-for="gene in filter_spots_genes_regexp" :value="gene">{{ gene }}</option>
                                                                </select>
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


<!--        <div class="row">-->
<!--            <div class="w-100 my-3">-->
<!--                <label>-->
<!--                    Name this filter: <input type="text" class="border border-1 rounded p-1">-->
<!--                </label>-->
<!--                <div>-->
<!--                <input type="button" class="btn btn-sm btn-outline-info" value="Save filter">-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->




        <div class="row">
            <div class="w-100">
                <div class="text-center w-100 w-md-40 w-lg-30 w-xl-20 float-end">
                    <button type="button" class="btn btn-lg bg-gradient-info w-100 mt-4 mb-0" @click="startProcess" :disabled="processing">{{ processing ? 'Please wait...' : 'Run Filter' }}</button>
                </div>
            </div>
        </div>

        <div v-if="'filter_violin' in project.project_parameters">

            <div class="row mt-5 row-cols-2">
                <div class="col">
                    <div>Color palette</div>
                    <div><multiselect :options="colorPalettes" @change="(value, select) => filter_color_palette = value"></multiselect></div>
                </div>
                <div class="col">
                    <div>Variable</div>
                    <div><multiselect :options="JSON.parse(project.project_parameters.filter_meta_options)" @change="(value, select) => filter_variable = value"></multiselect></div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="float-end">
                    <input type="button" class="btn btn-outline-info float-end" :value="generating_plots ? 'Please wait...' : 'Generate plots'" @click="filterPlots">
                </div>
            </div>

            <div class="mt-4" v-if="!generating_plots">
                <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="violinplot-tab" data-bs-toggle="tab" data-bs-target="#violinplot" type="button" role="tab" aria-controls="violinplot" aria-selected="false">Violin plots</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="boxplot-tab" data-bs-toggle="tab" data-bs-target="#boxplot" type="button" role="tab" aria-controls="boxplot" aria-selected="true">Boxplots</button>
                    </li>
                </ul>
                <div class="tab-content" id="filterDiagramsContent">

                    <div class="tab-pane fade show active" id="violinplot" role="tabpanel" aria-labelledby="violinplot-tab">
                        <div class="text-center m-4">
                            <img :src="project.project_parameters.filter_violin + '?' + Date.now()">
<!--                            <img :src="project.project_parameters.filter_violin + '?' + Date.now()">-->
                        </div>
                    </div>

                    <div class="tab-pane fade" id="boxplot" role="tabpanel" aria-labelledby="boxplot-tab">
                        <div class="text-center m-4">
                            <img :src="project.project_parameters.filter_boxplot + '?' + Date.now()">
                        </div>
                    </div>

                </div>
            </div>
        </div>


    </form>
</div>
</template>
<script>
    import {toJSON} from "lodash/seq";

    export default {
        name: 'qcDtFilter',

        props: {
            project: Object,
            samples: Object,
            colorPalettes: Object,
            filterUrl: String,
            filterUrlPlots: String
        },

        data() {
            return {

                filter_genes_regexp: [],
                filter_spots_genes_regexp: [],
                filter_genes: [],
                filter_genes_selected: [],
                filter_genes_regexp_remove_mt: false,
                filter_genes_regexp_remove_rp: false,

                filter_spots_regexp_remove_mt: false,
                filter_spots_regexp_remove_rp: false,

                filter_color_palette: 'okabeito',
                filter_variable: 'total_counts',

                generating_plots: false,

                params: {
                    spot_minreads: 0,
                    spot_maxreads: this.project.project_parameters.max_spot_counts, //TODO: arreglar

                    spot_mingenes: 0,
                    spot_maxgenes: this.project.project_parameters.total_genes,

                    gene_minreads: 0,
                    gene_maxreads: this.project.project_parameters.max_gene_counts,

                    gene_minspots: 0,
                    gene_maxspots: this.project.project_parameters.max_spots_number,

                    spot_minpct: 0,
                    spot_maxpct: 100,
                    spot_pct_expr: '',

                    gene_minpct: 0,
                    gene_maxpct: 100,

                    rm_genes: [],
                    rm_genes_expr: '',

                    samples: [],
                },

                processing: false,

            }
        },

        /*computed: {
            samplesToFilter() {
                console.log(this.$refs.selectedSamples.options)
            },
        },*/


        methods: {
            toJSON,

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

            addFilterGene(e) {
                if(e.target.index<0) return;
                console.log(e.target.text);
                if(!this.filter_genes_selected.includes(e.target.text))
                    this.filter_genes_selected.push(e.target.text);
            },

            removeFilterGene(e) {
                if(e.target.index<0) return;
                const index = this.filter_genes_selected.indexOf(e.target.text);
                this.filter_genes_selected.splice(index, 1);
            },

            selectedSamples(e) {

                /*this.samplesToProcess = Array.from(e.target.selectedOptions);

                console.log(e.target.selectedOptions);

                Array.from(e.target.selectedOptions).forEach(function (element) {
                    console.log(element.text)
                });*/
            },

            searchGenes: _.debounce(function(e) {
                console.log(e.target.value);

                axios.get('/projects/' + this.project.id + '/search-genes', {params: {'query': e.target.value}})
                    .then((response) => this.filter_genes = response.data /*console.log(response.data)*/)
                    .catch((error) => console.log(error));

            }, 700),

            filterGenesRegexp: _.debounce(function(e) {
                axios.get('/projects/' + this.project.id + '/search-genes-regexp', {params: {'query': e.target.value}})
                    .then((response) => this.filter_genes_regexp = response.data /*console.log(response.data)*/)
                    .catch((error) => console.log(error));

            }, 700),

            filterSpotsGenesRegexp: _.debounce(function(e) {
                axios.get('/projects/' + this.project.id + '/search-genes-regexp', {params: {'query': e.target.value}})
                    .then((response) => this.filter_spots_genes_regexp = response.data /*console.log(response.data)*/)
                    .catch((error) => console.log(error));

            }, 700),

            startProcess() {

                this.processing = true;

                //Check if specific samples were selected and form the list
                if(this.$refs.availableSamples.options.length && this.$refs.selectedSamples.options.length)
                    for(let i = 0; i< this.$refs.selectedSamples.options.length; i++)
                        this.params.samples.push(Number(this.$refs.selectedSamples.options[i].value));
                if(this.params.samples.length) {
                    this.params.samples = this.params.samples.join("','");
                    this.params.samples = "c('" + this.params.samples + "')";
                }
                else
                    this.params.samples = '';

                //Check if specific genes are to be removed
                if(this.filter_genes_selected.length) {
                    this.params.rm_genes = this.filter_genes_selected.join("','");
                    this.params.rm_genes = "c('" + this.params.rm_genes + "')";
                }
                else
                    this.params.rm_genes = '';

                //Check if Regexp for MT or RP genes removing are set
                if(this.filter_genes_regexp_remove_mt)
                    this.params.rm_genes_expr += (this.params.rm_genes_expr.length ? '|' : '') + '^MT-';
                if(this.filter_genes_regexp_remove_rp)
                    this.params.rm_genes_expr += (this.params.rm_genes_expr.length ? '|' : '') + '^RP[L|S]';

                //Check if Regexp for MT or RP SPOT genes PCT are set
                if(this.filter_spots_regexp_remove_mt)
                    this.params.spot_pct_expr += (this.params.spot_pct_expr.length ? '|' : '') + '^MT-';
                if(this.filter_spots_regexp_remove_rp)
                    this.params.spot_pct_expr += (this.params.spot_pct_expr.length ? '|' : '') + '^RP[L|S]';



                axios.post(this.filterUrl, {parameters: this.params})
                    .then((response) => {
                        console.log(response.data);
                        this.processing = false;
                        //this.plots_data = response.data;

                        for(let property in response.data)
                            this.project.project_parameters[property] = response.data[property];

                        /*this.project.project_parameters.filter_meta_options = response.data.filter_meta_options;
                        this.project.project_parameters.filter_violin = response.data.filter_violin;
                        this.project.project_parameters.filter_boxplot = response.data.filter_boxplot;*/
                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                    })


            },

            filterPlots() {
                this.generating_plots = true;
                axios.post(this.filterUrlPlots, {color_palette: this.filter_color_palette, variable: this.filter_variable})
                    .then((response) => {
                        this.generating_plots = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                    })
            }
        },

    }
</script>
