<template>
<div v-if="loaded" class="m-4">
    <form>

<!--        <div class="w-30 m-4">-->
<!--            <multiselect></multiselect>-->
<!--        </div>-->

        <div class="accordion row" id="accordionFilterTab" :class="processing || generating_plots ? 'disabled-clicks' : ''">
            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="headingSelectSamples">
                    <show-modal tag="qcfilter_samples"></show-modal>
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                        Select samples to apply this filter
                    </button>
                </h2>
                <div id="collapseSelectSamples" class="accordion-collapse collapse" aria-labelledby="headingSelectSamples" data-bs-parent="#accordionFilterTab">

                    <div class="text-center justify-content-center w-100 py-2">

                        <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :selected-keys="params.samples" @selected="(keys) => params.samples = keys"></project-summary-table>

                        <!-- <div class="m-4 gap-4">
                            <div class="text-info text-lg text-bolder text-center mb-2">Click to add/remove a sample</div>
                            <div class="text-info text-lg text-center mb-2" v-if="params.samples.length === samples.length">All samples selected</div>
                            <div class="text-info text-lg text-center mb-2" v-if="!params.samples.length">You must select at least one sample</div>
                            <div class="container-fluid" v-for="sample in samples">
                                <button type="button" class="btn btn-lg" :class="params.samples.includes(sample.name) ? 'bg-gradient-info' : 'btn-outline-info'" @click="toggleSample(sample.name)">
                                    {{ sample.name }}
                                </button>
                            </div>

                        </div> -->
                    </div>

                </div>
            </div>



            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="headingSpotCell">
                    <show-modal tag="qcfilter_spots_cells"></show-modal>
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSpotCell" aria-expanded="false" aria-controls="collapseSpotCell">
                        Filter spots/cells
                    </button>
                </h2>
                <div id="collapseSpotCell" class="accordion-collapse collapse" aria-labelledby="headingSpotCell" data-bs-parent="#accordionFilterTab">

                    <div class="m-4 gap-1">

                        <div class="m-4 gap-1">
                            <div class="row">

                                <div class="mt-2 pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range
                                        title="Keep spots/cells with this number of counts:"
                                        show-tool-tip="qcfilter_spots_cells_number_counts"
                                        :default-min="params.spot_minreads" :default-max="params.spot_maxreads"
                                        title-class="text-bold" :min="0" :max="project.project_parameters.max_spot_counts" :step="500"
                                        @updated="(min,max) => {params.spot_minreads = min; params.spot_maxreads = max}"></numeric-range>
                                </div>

                                <div class="mt-2 pb-4 border border-4 border-start-0 border-end-0 border-top-0">
                                    <numeric-range
                                        title="Keep spots/cells with this number of expressed genes:"
                                        show-tool-tip="qcfilter_spots_cells_number_expressed_counts"
                                        :default-min="params.spot_mingenes" :default-max="params.spot_maxgenes"
                                        :show-percentages="true" title-class="text-bold" :min="0" :max="project.project_parameters.total_genes" :step="500"
                                        @updated="(min,max) => {params.spot_mingenes = min; params.spot_maxgenes = max}"></numeric-range>
                                </div>

                                <div class="mt-4">

                                    <div class="text-start text-bold mt-2">Keep spots/cells by percentage of counts: <show-modal tag="qcfilter_spots_cells_percentage_counts"></show-modal></div>
                                    <div class="mt-2 justify-content-center row row-cols-2">
                                        <div class=" col my-2 justify-content-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chkFilterSpotRemoveMT" v-model="filter_spots_regexp_remove_mt">
                                                <label class="form-check-label" for="chkFilterSpotRemoveMT">
                                                    Mitochondrial genes (^MT-)
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="" id="chkFilterSpotRemoveRP" v-model="filter_spots_regexp_remove_rp">
                                                <label class="form-check-label" for="chkFilterSpotRemoveRP">
                                                    Ribosomal genes (^RP[L|S])
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col mt-3">
                                        <div class="accordion row w-100 w-xl-90" id="accordionRegExFilterSpots">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header d-flex" id="headingRegExFilterSpots">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegExFilterSpots" aria-expanded="false" aria-controls="collapseRegExFilterSpots">
                                                        Regular expression (advanced users)
                                                    </button>
                                                    <show-modal tag="qcfilter_spots_cells_percentage_counts_regexp"></show-modal>
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
                                        <numeric-range title="" :min="0" :max="100" :step="1"
                                            :default-min="params.spot_minpct" :default-max="params.spot_maxpct"
                                            @updated="(min,max) => {params.spot_minpct = min; params.spot_maxpct = max}">
                                        </numeric-range>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header d-flex" id="headingRemoveGenes">
                    <show-modal tag="qcfilter_genes"></show-modal>
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
                                <label for="genesExcluded" class="form-label">Excluded genes:</label><show-modal tag="qcfilter_genes_excluded"></show-modal>
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
                            <show-modal tag="qcfilter_genes_remove_mito_ribo"></show-modal>
                        </div>

                        <div class="accordion row w-100 w-xxl-60" id="accordionRegExFilterGenes">
                            <div class="accordion-item">
                                <h2 class="accordion-header d-flex" id="headingRegExFilterGenes">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRegExFilterGenes" aria-expanded="false" aria-controls="collapseRegExFilterGenes">
                                        Remove genes using regular expression (advanced users)
                                    </button>
                                    <show-modal tag="qcfilter_genes_remove_regexp"></show-modal>
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
                                    <numeric-range title="Keep genes with counts between:" show-tool-tip="qcfilter_genes_counts_between" title-class="text-bold"
                                        :default-min="params.gene_minreads" :default-max="params.gene_maxreads"
                                        :min="0" :max="project.project_parameters.max_gene_counts" :step="500" @updated="(min,max) => {params.gene_minreads = min; params.gene_maxreads = max}">
                                    </numeric-range>
                                </div>
                                <div class="mt-4">
                                    <div class="text-start text-bold">Keep genes expressed in: <show-modal tag="qcfilter_genes_expressed_in"></show-modal></div>
                                    <div class="mt-2">
                                        <!--                                        TODO: script maximum number of spots  -->
                                        <numeric-range title="Number of spots:" :show-percentages="true"
                                            :default-min="params.gene_minspots" :default-max="params.gene_maxspots"
                                            :min="0" :max="project.project_parameters.max_spots_number" :step="50"
                                            @updated="(min,max) => {params.gene_minspots = min; params.gene_maxspots = max}">
                                        </numeric-range>
                                    </div>
                                    <!--                                    <div class="mt-4">-->
                                    <!--                                        <numeric-range title="Percentage of spots" :min="0" :max="100" :step="1" @updated="(min,max) => {gene_minpct = min; gene_maxpct = max}"></numeric-range>-->
                                    <!--                                    </div>-->
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




        <div class="p-3 text-end" :class="generating_plots ? 'disabled-clicks' : ''">
            <send-job-button label="Apply filter" :disabled="!params.samples.length" :project-id="project.id" job-name="applyFilter" @started="startProcess" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>


        <div v-if="!processing && 'filter_violin' in project.project_parameters">

            <div class="row mt-5 row-cols-2" :class="generating_plots ? 'disabled-clicks' : ''">
                <div class="col">
                    <div>Color palette</div>
                    <div><Multiselect :options="colorPalettes" v-model="filter_color_palette"></Multiselect></div>
                </div>
                <div class="col">
                    <div>Variable</div>
                    <div><Multiselect :options="JSON.parse(project.project_parameters.filter_meta_options)" v-model="filter_variable"></Multiselect></div>
                </div>
            </div>
            <div class="row mt-3">

                <div class="p-3 text-end">
                    <send-job-button label="Generate plots" :project-id="project.id" job-name="generateFilterPlots" @started="filterPlots" @ongoing="generating_plots = true" @completed="plotsProcessCompleted" :project="project" ></send-job-button>
                </div>

<!--                <div class="float-end">-->
<!--                    <input type="button" class="btn btn-outline-info float-end" :class="generating_plots ? 'disabled' : ''" :value="generating_plots ? 'Please wait...' : 'Generate plots'" @click="filterPlots">-->
<!--                </div>-->
            </div>

            <div class="mt-4" v-if="!generating_plots">
                <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="filtered-summary-tab" data-bs-toggle="tab" data-bs-target="#filtered-summary" type="button" role="tab" aria-controls="filtered-summary" aria-selected="true">Summary</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="violinplot-tab" data-bs-toggle="tab" data-bs-target="#violinplot" type="button" role="tab" aria-controls="violinplot" aria-selected="false">Violin plots</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="boxplot-tab" data-bs-toggle="tab" data-bs-target="#boxplot" type="button" role="tab" aria-controls="boxplot" aria-selected="false">Boxplots</button>
                    </li>
                </ul>
                <div class="tab-content" id="filterDiagramsContent">

                    <div class="tab-pane fade show active" id="filtered-summary" role="tabpanel" aria-labelledby="filtered-summary-tab">
                        <div class="text-center m-4">
                            <project-summary-table :data="project.project_parameters.filtered_stlist_summary" :reference="project.project_parameters.initial_stlist_summary"></project-summary-table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="violinplot" role="tabpanel" aria-labelledby="violinplot-tab">
                        <show-plot :src="project.project_parameters.filter_violin"></show-plot>
                        <plot-holder v-if="'violin_box_plot_data' in project.project_parameters"
                            :csv="project.project_parameters.violin_box_plot_data + '.csv'"
                            expression="Violin"
                            title="Violin Plot"
                            plot-type="violin"
                            :plot-variable="filter_variable"
                            :palette="{'sample_093d': '#1fa371', 'sample_396a': '#a5570e', 'sample_396c': '#590cc7', 'sample_397d': '#a30354'}"
                            :inverted="false"
                        ></plot-holder>
                    </div>

                    <div class="tab-pane fade" id="boxplot" role="tabpanel" aria-labelledby="boxplot-tab">
                        <show-plot :src="project.project_parameters.filter_boxplot"></show-plot>
                        <plot-holder v-if="'violin_box_plot_data' in project.project_parameters"
                            :csv="project.project_parameters.violin_box_plot_data + '.csv'"
                            expression="Box"
                            title="Box Plot"
                            plot-type="box"
                            :plot-variable="filter_variable"
                            :palette="{'sample_093d': '#1fa371', 'sample_396a': '#a5570e', 'sample_396c': '#590cc7', 'sample_397d': '#a30354'}"
                            :inverted="false"
                        ></plot-holder>
                    </div>

                </div>
            </div>
        </div>


    </form>
</div>
</template>
<script>
    import {toJSON} from "lodash/seq";
    import Multiselect from '@vueform/multiselect';

    export default {
        name: 'qcDtFilter',

        components: {
            Multiselect,
        },

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

                filter_color_palette: 'Spectral',
                filter_variable: 'total_counts',

                generating_plots: false,

                gene_minpct: 0,
                gene_maxpct: 100,

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

                    //gene_minpct: 0,
                    //gene_maxpct: 100,

                    rm_genes: [],
                    rm_genes_expr: '',

                    //samples: [],
                    samples: this.samples.map(sample => sample.name),
                },

                processing: false,

                jobPositionInQueue: 0,
                checkQueueIntervalId: 0,
                reloadPage: false,

                loaded: false,

            }
        },

        mounted() {

            if('applyFilter' in this.project.project_parameters) {
                let params = JSON.parse(this.project.project_parameters['applyFilter'])['parameters'];
                for(let param in params) {
                    if(!isNaN(params[param])) {
                        params[param] = Number.parseFloat(params[param]);
                    }
                    //console.log(param, params[param], typeof params[param], !isNaN(params[param]));
                }
                console.log(params);
                this.params = params;
            }

            this.loaded = true;

        //     this.setIntervalQueue();
        },

        /*computed: {
            samplesToFilter() {
                console.log(this.$refs.selectedSamples.options)
            },
        },*/

        watch: {
            'gene_minpct': function(newValue, oldValue) {
                console.log(newValue);
                this.params.gene_minspots = Math.round(Number(this.project.project_parameters.max_spots_number) * newValue/100);
                console.log(this.params.gene_minspots);
            },

            // jobPositionInQueue: {
            //     handler: function (newValue, oldValue) {
            //         console.log('---', this.jobPositionInQueue);
            //         if(this.jobPositionInQueue) this.reloadPage = true;
            //         this.processing = !!this.jobPositionInQueue;
            //         if(!this.jobPositionInQueue) {
            //             clearInterval(this.checkQueueIntervalId);
            //             if(this.reloadPage) location.reload();
            //         }
            //     },
            //     immediate: true
            // }
        },


        methods: {
            toJSON,

            toggleSample(sampleName) {

                const index = this.params.samples.indexOf(sampleName);
                if (index > -1)
                    this.params.samples.splice(index, 1);
                else
                    this.params.samples.push(sampleName);

            },

            // setIntervalQueue: function() {
            //     this.checkQueueIntervalId = setInterval(async () => {this.jobPositionInQueue =  await this.$getJobPositionInQueue(this.project.id, 'applyFilter');}, 1800);
            //     console.log('Interval set');
            // },

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

                let _params = JSON.parse(JSON.stringify(this.params));

                /*if(_params.samples.length) {
                    _params.samples = _params.samples.join("','");
                    _params.samples = "c('" + _params.samples + "')";
                }
                else
                    _params.samples = '';*/

                //Check if specific genes are to be removed
                if(this.filter_genes_selected.length) {
                    _params.rm_genes = this.filter_genes_selected.join("','");
                    _params.rm_genes = "c('" + _params.rm_genes + "')";
                }
                else
                    _params.rm_genes = '';

                //Check if Regexp for MT or RP genes removing are set
                if(this.filter_genes_regexp_remove_mt)
                    _params.rm_genes_expr += (_params.rm_genes_expr.length ? '|' : '') + '^MT-';
                if(this.filter_genes_regexp_remove_rp)
                    _params.rm_genes_expr += (_params.rm_genes_expr.length ? '|' : '') + '^RP[L|S]';

                //Check if Regexp for MT or RP SPOT genes PCT are set
                if(this.filter_spots_regexp_remove_mt)
                    _params.spot_pct_expr += (_params.spot_pct_expr.length ? '|' : '') + '^MT-';
                if(this.filter_spots_regexp_remove_rp)
                    _params.spot_pct_expr += (_params.spot_pct_expr.length ? '|' : '') + '^RP[L|S]';


                console.log(_params);
                //return;


                axios.post(this.filterUrl, {parameters: _params})
                    .then((response) => {
                        //logic moved to the queue job-button component
                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                        this.generating_plots = false;
                    })


            },

            processCompleted() {
                this.processing = false;
            },

            filterPlots() {
                this.generating_plots = true;
                axios.post(this.filterUrlPlots, {color_palette: this.filter_color_palette, variable: this.filter_variable, samples: this.params.samples})
                    .then((response) => {
                        //this.generating_plots = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                    })
            },

            plotsProcessCompleted() {
                this.generating_plots = false;
            }
        },

    }
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>

:root {
    --ms-placeholder-color: #3B82F6;
    --ms-border-color-active: #3B82F6;
    --ms-ring-color: #3B82F630;
    --ms-spinner-color: #3B82F6;
    //--ms-dropdown-border-color: #3B82F6;
    --ms-tag-bg: #3B82F6;
    --ms-tag-color: #3B82F6;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
