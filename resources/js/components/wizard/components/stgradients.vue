<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Spatial gradients with STgradient
            </div>
            <div class="text-justify">
                STgradient finds genes for which there is evidence of gene expression gradients with respect to a tissue niche/domain. The method calculates distances from each spot/cell to a “reference” tissue niche/domain (e.g., cluster defined via STclust) and correlates those distances with gene expression values from top variable genes. The distances to reference niche can be summarized using the average or the minimum value. Generally the minimum distances capture gradients at short ranges, while average distances capture whole-tissue gradients. Users can use robust regression to reduce (albeit not eliminate) the effect of zero inflation in spatial transcriptomics data.
            </div>

            <div class="d-flex justify-content-center text-center">
                <div class="w-100 w-lg-80 w-xxl-80">
                    <div class="accordion row mt-4 mx-2" id="accordionFilterTab" :class="processing ? 'disabled-clicks' : ''">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSelectSamples">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                                    <span class="me-3">Select sample(s) to run this test</span>
                                    <span class="text-success text-lg text-center" v-if="params.samples.length === samples.length">All samples selected</span>
                                    <span class="text-danger text-lg text-center" v-if="!params.samples.length">At least one sample must be selected</span>
                                    <span class="text-warning text-lg text-center" v-if="params.samples.length && params.samples.length < samples.length">{{ params.samples.length }} sample(s) selected</span>
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
                </div>
                <div class="float-start">
                    <show-modal tag="stgradient_samples"></show-modal>
                </div>
            </div>




            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-80">
                    <div class="row justify-content-center text-center">
                        <div class="">
                            <div class="me-3 d-block">
                                Number of most variable genes to use: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.topgenes">
                                <show-modal tag="stgradient_genes"></show-modal>
                            </div>
                            <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="500" class="w-100" v-model="params.topgenes">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Annotation to test</div>
                    <div>
                        <span>
                            <Multiselect :options="project.project_parameters.annotation_variables" v-model="params.annot"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-center text-center m-3">
                <div class="justify-content-center text-center m-3">
                    <div>Reference cluster</div>
                    <div>
                        <span>
                            <Multiselect :options="annotation_variables_clusters" v-model="params.ref"></Multiselect>
                        </span>
                    </div>
                </div>

                <div class="justify-content-center text-center m-3">
                    <div>Cluster(s) to exclude (optional)</div>
                    <div>
                        <span>
                            <Multiselect :multiple="true" mode="tags" :searchable="true" :options="annotation_variables_clusters_exclude" v-model="params.exclude" @select="checkNotAllSelected"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row text-center m-3">
                    <div class="d-flex justify-content-center">
                        <label>
                            <input type="checkbox" value="moran" v-model="params.robust"> Robust regression?
                        </label>
                        <label class="ms-4">
                            <input :disabled="params.robust" type="checkbox" value="moran" v-model="params.out_rm"> Ignore outliers?
                        </label>
                    </div>
            </div>

            <div class="row m-3">
                <div class="d-flex justify-content-center text-center">
                    <div class="me-3">Restrict correlation to this limit: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.limit"></div>
                    <div class="me-3">Minimum number of neighbors: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.min_nb"></div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-80 w-md-50 w-lg-50 w-xxl-30">
                    <div>Distance summary metric</div>
                    <div>
                        <Multiselect :options="distsumm_options" v-model="params.distsumm"></Multiselect>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-xxl-95">
                    <div class="row justify-content-center text-center">

                    </div>
                </div>
            </div>







        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run STgradient" :disabled="processing || !this.params.samples.length" :project-id="project.id" job-name="STGradients" @started="STGradients" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>




        <div v-if="!processing && ('stgradients' in project.project_parameters)" class="p-3 text-center mt-4">
            <a :href="stgradients.base_url + 'stgradients_results.xlsx'" class="btn btn-sm btn-outline-info me-2" download>Excel results - All samples</a>
        </div>




<!--         Create tabs for each sample-->
        <div v-if="!processing && ('stgradients' in project.project_parameters)">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li v-for="(sample, index) in stgradients.samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div v-for="(sample, index) in stgradients.samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">
                        <div class="m-4">
                            <vue3-easy-data-table v-if="(sample in results) && results[sample].loaded"
                                                  :headers="results[sample].data.headers"
                                                  :items="results[sample].data.items"
                                                  alternating
                                                  border-cell
                                                  body-text-direction="center"
                                                  header-text-direction="center"
                            >
                                <template #item-gene="{ gene }">
                                    <a :href="'https://www.genecards.org/cgi-bin/carddisp.pl?gene=' + gene" target="_blank" class="text-info">{{ gene }}</a>
                                </template>
                            </vue3-easy-data-table>
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
        name: 'stgradients',

        components: {
            Multiselect,
            Vue3EasyDataTable
        },

        props: {
            project: Object,
            samples: Object,
            stgradientsUrl: String,
        },

        data() {
            return {

                annotation_variables_clusters: [],
                annotation_variables_clusters_exclude: [],
                distsumm_options: [{'label': 'Minimum distance', 'value': 'min'}, {'label': 'Average distance', 'value': 'avg'}],

                stgradients: ('stgradients' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stgradients) : {},
                results: {},

                processing: false,

                textOutput: '',

                params: {

                    samples: this.samples.map(sample => sample.name),
                    samples_array: [],
                    topgenes: 3000,
                    annot: '',
                    ref: '',
                    exclude: [],
                    exclude_string: 'NULL',
                    out_rm: false,
                    limit: null,
                    distsumm: 'min',
                    min_nb: 3,
                    robust: true,
                },

            }
        },

        mounted() {
            this.loadResults();
        },

        watch: {
            'params.annot'(newValue) {

                this.params.ref = '';

                this.annotation_variables_clusters = [];
                this.annotation_variables_clusters_exclude = [];

                this.project.project_parameters.annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters.sort((a,b) => a.value - b.value);

            },

            'params.ref'(newValue) {

                this.params.exclude = [];

                this.annotation_variables_clusters_exclude = [];

                this.project.project_parameters.annotation_variables_clusters.map(annot => {if(annot.annotation === this.params.annot && annot.cluster !== this.params.ref) this.annotation_variables_clusters_exclude.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters_exclude.sort((a,b) => a.value - b.value);
            },

            'params.limit'(newValue) {
                if(newValue<0)
                    this.params.limit = null;
            },

            'params.min_nb'(newValue) {
                if(!Number.isInteger(newValue))
                    this.params.min_nb = Math.floor(newValue);
            },

            'params.robust'(newValue) {
                if(newValue)
                    this.params.out_rm = false;
            },
        },

        methods: {

            checkNotAllSelected(option) {
                if(this.params.exclude.length && this.params.exclude.length === this.annotation_variables_clusters_exclude.length)  //this.params.ref.length && (this.params.exclude.length - 1) === this.annotation_variables_clusters)
                    this.params.exclude.pop();
            },

            toggleSample(sampleName) {

                const index = this.params.samples.indexOf(sampleName);
                if (index > -1)
                    this.params.samples.splice(index, 1);
                else
                    this.params.samples.push(sampleName);

            },

            STGradients() {

                let data = JSON.parse(JSON.stringify(this.params));

                data.samples_array = data.samples;
                data.samples = data.samples.length !== this.samples.length ? "c('" + data.samples.join("','") + "')" : 'NULL';
                data.exclude_string = data.exclude.length ? "c(" + data.exclude.join(",") + ")" : 'NULL';

                this.processing = true;

                axios.post(this.stgradientsUrl, data)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                this.stgradients = ('stgradients' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stgradients) : {};
                this.processing = false;

                this.loadResults();
            },

            loadResults() {
                this.stdiff_ns = ('stgradients' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stgradients) : {};

                if(!('base_url' in this.stgradients))
                    return;

                this.stgradients.samples.forEach( sample => {
                    axios.get(this.stgradients.base_url + 'stgradients_' + sample + '.json' + '?' + Date.now())
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
