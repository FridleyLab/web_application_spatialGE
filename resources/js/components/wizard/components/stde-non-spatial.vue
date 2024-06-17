<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Non-spatial tests of differential gene expression
            </div>
            <div>
                Select a test to detect genes for which expression varies from one tissue domain to another. The tests available in this module do not incorporate the spatial context of the spot/cells.
            </div>


            <div class="accordion row justify-content-center text-center my-4 mx-3" id="accordionFilterTab" :class="processing ? 'disabled-clicks' : ''">
                <div class="accordion-item w-100 w-lg-80 w-xxl-70">
                    <h2 class="accordion-header d-flex" id="headingSelectSamples">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                            <span class="me-3">Select sample(s) to run this test</span>
                            <span class="text-success text-lg text-center" v-if="params.samples.length === samples.length">All samples selected</span>
                            <span class="text-danger text-lg text-center" v-if="!params.samples.length">At least one sample must be selected</span>
                            <span class="text-warning text-lg text-center" v-if="params.samples.length && params.samples.length < samples.length">{{ params.samples.length }} sample(s) selected</span>
                        </button>
                        <show-modal tag="stdiff_non_spatial_samples"></show-modal>
                    </h2>
                    <div id="collapseSelectSamples" class="accordion-collapse collapse" aria-labelledby="headingSelectSamples" data-bs-parent="#accordionFilterTab">

                        <div class="text-center justify-content-center w-100">
                            <div class="m-4 gap-4">
                                <div class="text-info text-lg text-bolder text-center mb-2">Click to add/remove a sample</div>
                                <div class="container-fluid" v-for="sample in samples">
                                    <button type="button" class="btn btn-lg" :class="params.samples.includes(sample.name) ? 'bg-gradient-info' : 'btn-outline-info'" @click="toggleSample(sample.name)">
                                        {{ sample.name }}
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="d-flex">
                        <span class="w-40">
                            <div>Type of test <show-modal tag="stdiff_non_spatial_type_of_test"></show-modal></div>
                            <Multiselect :options="test_types" v-model="params.test_type"></Multiselect>
                        </span>
                        <label class="w-60 text-lg ms-3">
                            <input type="checkbox" value="moran" v-model="params.pairwise"> All pairwise comparisons between the tissue domains?
                        </label>
                        <show-modal tag="stdiff_non_spatial_pairwise"></show-modal>
                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-90 w-lg-80 w-xxl-65">
                    <div>Annotation to test <show-modal tag="stdiff_non_spatial_annotation"></show-modal></div>
                    <div>
                        <span>
                            <Multiselect id="multiselect_annotation_variables" :options="annotation_variables" v-model="params.annotation"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Cluster annotations to test <show-modal tag="stdiff_non_spatial_cluster"></show-modal></div>
                    <div>
                        <span>
                            <Multiselect id="multiselect_annotation_variables_clusters" :multiple="true" mode="tags" :searchable="true" :options="annotation_variables_clusters" v-model="params.clusters" @select="checkIfAllSelected"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="row justify-content-center text-center mt-5">
                        <div class="">
                            <div class="me-3">Number of most variable genes to use: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"> <show-modal tag="stdiff_non_spatial_genes"></show-modal></div>
                            <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="500" class="w-100" v-model="params.n_genes">
                        </div>
                    </div>
                </div>
            </div>



        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run Non-Spatial tests" :disabled="processing || !this.params.samples.length || !this.params.test_type.length || !this.params.annotation.length || !this.params.clusters.length" :project-id="project.id" job-name="STDiffNonSpatial" @started="nonSpatial" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>


        <div v-if="!processing && ('stdiff_ns' in project.project_parameters)" class="p-3 text-center mt-4">


            <div class="text-justify">
                <div class="fs-5">Explanation of results:</div>
                <ul>
                    <li><strong>Gene:</strong> The name of the gene tested. If the gene name is clicked, the corresponding GeneCards record is opened.</li>
                    <li><strong>Avg log2(FC):</strong> The average log2 fold-change between the expression of the domain in the Cluster 1 column and the rest of the sample, or Cluster 1 and Cluster 2 if pairwise testing was requested.</li>
                    <li><strong>Wilcoxon/T-test/Mixed Model p-value:</strong> The nominal p-value resulting from the Wilcoxon’s Rank Test, the T-test, or mixed model fit.</li>
                    <li><strong>Adjusted p-value:</strong> The False Discovery Rate (FDR) adjusted p-value. These adjusted p-values can be used to decide whether a gene is differentially expressed.</li>
                </ul>
            </div>


            <a :href="stdiff_ns.base_url + 'stdiff_ns_results.xlsx'" class="btn btn-sm btn-outline-info me-2" download>Excel results - All samples</a>
        </div>


        <!-- Create tabs for each sample-->
        <div v-if="!processing && ('stdiff_ns' in project.project_parameters)">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li v-for="(sample, index) in stdiff_ns.samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" :aria-selected="index === 0">{{ sample }}</button>
                    </li>
                </ul>

                <div class="tab-content mx-3" id="myTabContent">
                    <div v-for="(sample, index) in stdiff_ns.samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">

                        <div class="mt-4">
                            <ul class="nav nav-tabs" id="stdiff_ns_tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" :id="'stdiff_ns_' + sample + 'table' + '-tab'" data-bs-toggle="tab" :data-bs-target="'#stdiff_ns_' + sample + 'table'" type="button" role="tab" :aria-controls="'stdiff_ns_' + sample + 'table'" aria-selected="true">Table</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" :id="'stdiff_ns' + sample + 'volcano' + '-tab'" data-bs-toggle="tab" :data-bs-target="'#stdiff_ns' + sample + 'volcano'" type="button" role="tab" :aria-controls="'stdiff_ns' + sample + 'volcano'" aria-selected="false">Volcano</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="stdiff_ns_tabsContent">

                                <div class="tab-pane fade show active" :id="'stdiff_ns_' + sample + 'table'" role="tabpanel" :aria-labelledby="'stdiff_ns_' + sample + 'table' + '-tab'">
                                    <div class="m-4" v-if="(sample in results) && results[sample].loaded">

                                        <data-grid v-if="(sample in results) && results[sample].loaded" :headers="results[sample].data.headers/*.map(a => a.value)*/" :data="results[sample].data.items"></data-grid>
<!--                                        <vue3-easy-data-table v-if="(sample in results) && results[sample].loaded"-->
<!--                                                              :headers="results[sample].data.headers"-->
<!--                                                              :items="results[sample].data.items"-->
<!--                                                              alternating-->
<!--                                                              border-cell-->
<!--                                                              body-text-direction="center"-->
<!--                                                              header-text-direction="center"-->
<!--                                        >-->
<!--                                            <template #item-gene="{ gene }">-->
<!--                                                <a :href="'https://www.genecards.org/cgi-bin/carddisp.pl?gene=' + gene" target="_blank" class="text-info">{{ gene }}</a>-->
<!--                                            </template>-->
<!--                                        </vue3-easy-data-table>-->
                                    </div>
                                </div>

                                <div class="tab-pane fade" :id="'stdiff_ns' + sample + 'volcano'" role="tabpanel" :aria-labelledby="'stdiff_ns' + sample + 'volcano' + '-tab'">
                                    <div v-for="vp in volcano_plots(sample)">
                                        <show-plot :src="vp"></show-plot>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>





    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';


    export default {
        name: 'stdeNonSpatial',

        components: {
            Multiselect,
            Vue3EasyDataTable
        },

        props: {
            project: Object,
            samples: Object,
            nonSpatialUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {
                annotation_variables: [],
                all_annotation_variables_clusters: [],
                annotation_variables_clusters: [],

                test_types: [{'label': 'Wilcoxon\'s test', 'value': 'wilcoxon'}, {'label': 'T-test', 'value': 't_test'}, {'label': 'Mixed models', 'value': 'mm'}],

                stdiff_ns: ('stdiff_ns' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stdiff_ns) : {},
                results: {},

                processing: false,

                textOutput: '',

                params: {

                    samples: [], // this.samples.map(sample => sample.name),
                    test_type: 'mm',
                    pairwise: false,
                    annotation: '',
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 100) ? 100 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,
                    clusters: []

                },

            }
        },

        async mounted() {
            await this.loadResults();

            let stdiff = await this.$getProjectSTdiffAnnotations(this.project.id);
            this.annotation_variables = stdiff['annotation_variables'];
            this.all_annotation_variables_clusters = stdiff['annotation_variables_clusters'];
        },

        watch: {

            'params.annotation'(newValue) {

                this.annotation_variables_clusters = [{'label': 'ALL', 'value': 'NULL'}];

                this.params.clusters = ['NULL'];

                this.all_annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters.sort((a,b) => a.value - b.value);

            },

        },

        methods: {

            checkIfAllSelected(option) {

                if(option === 'NULL')
                    this.params.clusters = ['NULL'];
                else
                    if(this.params.clusters.includes('NULL'))
                        this.params.clusters = [option];
            },

            toggleSample(sampleName) {

                const index = this.params.samples.indexOf(sampleName);
                if (index > -1)
                    this.params.samples.splice(index, 1);
                else
                    this.params.samples.push(sampleName);

            },

            nonSpatial() {
                this.processing = true;

                let parameters = {
                    samples_array: this.params.samples,
                    samples: this.params.samples.length !== this.samples.length ? "c('" + this.params.samples.join("','") + "')" : 'NULL',
                    annotation: this.params.annotation,
                    topgenes: this.params.n_genes,
                    test_type: this.params.test_type,
                    pairwise: this.params.pairwise ? 'T' : 'F',
                    clusters: this.params.clusters[0] === 'NULL' ? 'NULL' : "c('" + this.params.clusters.join("','") + "')"
                };

                axios.post(this.nonSpatialUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                console.log('NS - inicio processCompleted');
                this.stclust = ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
                this.processing = false;
                this.loadResults();
            },

            async loadResults() {

                this.stdiff_ns = ('stdiff_ns' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stdiff_ns) : {};

                if(!('base_url' in this.stdiff_ns))
                    return;

                this.stdiff_ns.samples.forEach( sample => {
                    axios.get(this.stdiff_ns.base_url + 'stdiff_ns_' + sample + '.json' + '?' + Date.now())
                        .then((response) => {
                            this.results[sample] = {};
                            this.results[sample].data = response.data;
                            this.results[sample].loaded = true;
                        })
                        .catch((error) => {
                            this.results[sample] = {};
                            this.results[sample].data = {};
                            this.results[sample].loaded = false;
                            console.log(error.message);
                        })
                });
            },

            volcano_plots(sample) {
                if(!('volcano_plots' in this.stdiff_ns) || !this.stdiff_ns.volcano_plots.length) return [];
                return this.stdiff_ns.volcano_plots.filter(p => {return p.includes(sample)});
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
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
