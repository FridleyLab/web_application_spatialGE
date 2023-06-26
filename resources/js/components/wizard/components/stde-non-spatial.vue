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
                    <h2 class="accordion-header" id="headingSelectSamples">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                            <span class="me-3">Select samples to apply this filter</span>
                            <span class="text-success text-lg text-center" v-if="params.samples.length === samples.length">All samples selected</span>
                            <span class="text-danger text-lg text-center" v-if="!params.samples.length">At least one sample must be selected</span>
                            <span class="text-warning text-lg text-center" v-if="params.samples.length && params.samples.length < samples.length">{{ params.samples.length }} samples selected</span>
                        </button>
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
                    <div>Type of test</div>
                    <div class="d-flex">
                        <span class="w-40">
                            <Multiselect :options="test_types" v-model="params.test_type"></Multiselect>
                        </span>
                        <label class="w-60 text-lg">
                            <input type="checkbox" value="moran" v-model="params.pairwise"> All pairwise comparisons between the tissue domains?
                        </label>
                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Annotation to test</div>
                    <div>
                        <span>
                            <Multiselect :options="project.project_parameters.annotation_variables" v-model="params.annotation"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Cluster annotations to test</div>
                    <div>
                        <span>
                            <Multiselect :multiple="true" mode="tags" :searchable="true" :options="annotation_variables_clusters" v-model="params.clusters" @select="checkIfAllSelected"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="row justify-content-center text-center mt-5">
                        <div class="">
                            <div class="me-3">Number of most variable genes to use: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"></div>
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
            <a :href="stdiff_ns.base_url + 'stdiff_ns_results.xlsx'" class="btn btn-sm btn-outline-info me-2" download>Excel results - All samples</a>
        </div>




        <!-- Create tabs for each sample-->
        <div v-if="!processing && ('stdiff_ns' in project.project_parameters)">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li v-for="(sample, index) in stdiff_ns.samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div v-for="(sample, index) in stdiff_ns.samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">

<!--                        <a :href="stdiff_ns.base_url + 'stdiff_ns_' + sample + '.csv'" class="btn btn-sm btn-outline-info m-6" download>CSV results</a>-->

                        <div class="m-4" v-if="(sample in results) && results[sample].loaded">
<!--                            <a :href="stdiff_ns.base_url + 'stdiff_ns_' + sample + '.csv'" class="btn btn-sm btn-outline-info my-3" download>CSV results</a>-->

                            <vue3-easy-data-table v-if="(sample in results) && results[sample].loaded"
                                                  :headers="results[sample].data.headers"
                                                  :items="results[sample].data.items"
                                                  alternating
                                                  border-cell
                                                  body-text-direction="center"
                                                  header-text-direction="center"
                            />
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

                annotation_variables_clusters: [],

                test_types: [{'label': 'Wilcoxon\'s test', 'value': 'wilcoxon'}, {'label': 'T-test', 'value': 't_test'}, {'label': 'Mixed models', 'value': 'mm'}],

                stdiff_ns: ('stdiff_ns' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stdiff_ns) : {},
                results: {},

                processing: false,

                textOutput: '',

                params: {

                    samples: this.samples.map(sample => sample.name),
                    test_type: 'wilcoxon',
                    pairwise: false,
                    annotation: '',
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 100) ? 100 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,
                    clusters: []

                },

            }
        },

        mounted() {
            //console.log(this.project.project_parameters.annotation_variables_clusters);
            this.loadResults();
        },

        watch: {

            'params.annotation'(newValue) {

                this.params.clusters = [];

                this.annotation_variables_clusters = [{'label': 'ALL', 'value': 'NULL'}];

                this.project.project_parameters.annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

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
                    clusters: this.params.clusters[0] === 'NULL' ? 'NULL' : "c(" + this.params.clusters.join(",") + ")"
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

            loadResults() {

                this.stdiff_ns = ('stdiff_ns' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stdiff_ns) : {};

                if(!('base_url' in this.stdiff_ns))
                    return;

                this.stdiff_ns.samples.forEach( sample => {
                    axios.get(this.stdiff_ns.base_url + 'stdiff_ns_' + sample + '.json')
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
