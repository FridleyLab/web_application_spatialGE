<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Spatial gradients with STgradient
            </div>
            <div class="text-justify">
                STgradient tests for genes for which there is evidence of expression spatial gradients with respect to a
                “reference” tissue niche/domain (e.g., higher expression closer to reference tissue niche, lower
                expression as farther from reference tissue niche). The method calculates distances from each spot/cell
                to the reference tissue niche/domain (e.g., a cluster defined via STclust – See “Spatial domain
                detection”) and correlates those distances with gene expression values from top variable genes (defined
                by standard deviation across ROIs/spots/cells). The distances to reference niche can be summarized using
                the average or the minimum value. Generally, the minimum distances might be better to capture gradients
                at short ranges, while average distances capture whole-tissue gradients. The use of robust regression to
                reduce (albeit not eliminate) the effect of zero inflation in spatial transcriptomics data. Spearman
                correlation coefficients are calculated using the ‘cor.test’ R function. The most variable genes to be tested are identified <i>before</i>
                removal of outliers.
            </div>

            <!-- <div class="d-flex justify-content-center text-center">
                <div class="w-100 w-lg-80 w-xxl-80">
                    <div class="accordion row mt-4 mx-2" id="accordionFilterTab" :class="processing ? 'disabled-clicks' : ''">
                        <div class="accordion-item">
                            <h2 class="accordion-header d-flex" id="headingSelectSamples">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                                    <span class="me-3">Select sample(s) to run this test</span>
                                    <span class="text-success text-lg text-center" v-if="params.samples.length === samples.length">All samples selected</span>
                                    <span class="text-danger text-lg text-center" v-if="!params.samples.length">At least one sample must be selected</span>
                                    <span class="text-warning text-lg text-center" v-if="params.samples.length && params.samples.length < samples.length">{{ params.samples.length }} sample(s) selected</span>
                                </button>
                                <show-modal tag="stgradient_samples"></show-modal>
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
            </div> -->


            <div class="my-4">
                <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :selected-keys="params.samples" @selected="(keys) => params.samples = keys"></project-summary-table>
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
                    <div>
                        Annotation to test
                        <show-modal tag="stgradient_annotation_to_test"></show-modal>
                    </div>
                    <div>
                        <span>
                            <Multiselect :options="annotation_variables" v-model="params.annot"></Multiselect>
                        </span>
                    </div>
                </div>
            </div>


            <div class="d-flex justify-content-center text-center m-3">
                <div class="justify-content-center text-center m-3">
                    <div>Reference cluster <show-modal tag="stgradient_reference_cluster"></show-modal></div>
                    <div>
                        <span>
                            <Multiselect :options="annotation_variables_clusters" v-model="params.ref"></Multiselect>
                        </span>
                    </div>
                </div>

                <div class="justify-content-center text-center m-3">
                    <div>Cluster(s) to exclude (optional) <show-modal tag="stgradient_clusters_to_exclude"></show-modal></div>
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
                            <input type="checkbox" value="moran" v-model="params.robust"> Robust regression?<show-modal tag="stgradient_robust_regression"></show-modal>
                        </label>
                        <label class="ms-4">
                            <input :disabled="params.robust" type="checkbox" value="moran" v-model="params.out_rm"> Ignore outliers?<show-modal tag="stgradient_ignore_outliers"></show-modal>
                        </label>
                    </div>
            </div>

            <div class="row m-3">
                <div class="d-flex justify-content-center text-center">
                    <div class="me-3">Restrict correlation to this limit: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.limit"> <show-modal tag="stgradient_restrict_correlation_to_this_limit"></show-modal></div>
                    <div class="me-3">Minimum number of neighbors: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.min_nb"><show-modal tag="stgradient_minimum_number_of_neighbors"></show-modal></div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-80 w-md-50 w-lg-50 w-xxl-30">
                    <div>Distance summary metric <show-modal tag="stgradient_distance_summary_metric"></show-modal></div>
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
            <send-job-button label="Run STgradient" :disabled="processing || !this.params.samples.length || !params.annot.length || !params.ref.length" :project-id="project.id" job-name="STGradients" @started="STGradients" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>





        <div v-if="!processing && ('stgradients' in project.project_parameters)" class="p-3 text-center mt-4">

            <div class="text-justify">
                <div class="fs-5">Explanation of results:</div>
                <ul>
                    <li><strong>Gene:</strong> The name of gene being tested with STgradient. By clicking the gene name you will be redirected to GeneCards</li>
                    <li><strong>Linear model slope:</strong> The coefficient from the linear model fit between the gene expression and distance from reference cluster. This coefficient is also known as the “slope” of the regression line</li>
                    <li><strong>Linear model p-value:</strong> The p-value resulting from the test on the linear model slope. Tests whether the slope is significantly different from zero</li>
                    <li><strong>Spearman’s coefficient:</strong> The value of the Spearman’s rank correlation coefficient (rho). This coefficient is a measure of the strength of the relationship between gene expression and distance from reference</li>
                    <li><strong>Spearman’s p-value:</strong> The p-value resulting from the test of significant correlation between gene expression and distance. The p-value can be used as a metric of statistical significance for the Spearman’s coefficient. Since the data is likely to contain “ties” (i.e., same distance and expression values for two or more spots/cells), the p-values are approximated</li>
                    <li><strong>Spearman’s adjusted p-value:</strong> The Benjamini-Hochberg correction for multiple testing on the Spearman’s coefficient p-values</li>
                    <li><strong>Comment:</strong> If a test is labeled as “zero_st_deviation”, it likely means that the expression data has the same value for all spots (likely “0”). The lack of variation is likely the result of outlier removal or exclusion of clusters/domains. If “rob_regr_no_convergence” it means that robust regression did not converge.</li>
                </ul>
            </div>

            <!-- <a :href="stgradients.base_url + 'stgradients_results.xlsx'" class="btn btn-sm btn-outline-info me-2" download>Download results (Excel)</a> -->
        </div>




<!--         Create tabs for each sample-->
        <div v-if="!processing && ('stgradients' in project.project_parameters)">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">

                    <li v-if="'heatmap' in stgradients" class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-heatmap" data-bs-toggle="tab" data-bs-target="#heatmap" type="button" role="tab" aria-controls="heatmap" aria-selected="true">Heatmap</button>
                    </li>

                    <template v-for="(sample, index) in stgradients.samples">
                        <li v-if="(sample in results) && results[sample].loaded" class="nav-item" role="presentation">
                            <button class="nav-link" :class="index === 0 ? /*'active'*/ '' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                        </li>
                    </template>

                </ul>

                <div class="tab-content" id="myTabContent">

                    <div v-if="'heatmap' in stgradients" class="tab-pane fade min-vh-50 show active" id="heatmap" role="tabpanel" aria-labelledby="tab-heatmap">
                        <div class="m-4" style="width:100%; height:1000px">
                            <heatmap
                                :color_palette="['blue', 'white', 'red']"
                                :csv_file="stgradients.base_url + stgradients.heatmap"
                                heatmap_title="Test sample-gene heatmap"
                                csv-header-gene-name="gene_name"
                            >
                            </heatmap>
                            <!-- <show-plot :src="stgradients.base_url + stgradients.heatmap" :show-image="false" :side-by-side="false"></show-plot> -->
                        </div>
                    </div>

                    <template v-for="(sample, index) in stgradients.samples">
                        <div v-if="(sample in results) && results[sample].loaded" class="tab-pane fade min-vh-50" :class="index === 0 ? /*'show active'*/ '' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">
                            <div class="m-4">
                                <a :href="stgradients.base_url + 'stgradients_results_' + sample + '.xlsx'" class="btn btn-sm btn-outline-info me-2 float-end" download>Download results (Excel)</a>
                                <data-grid v-if="(sample in results) && results[sample].loaded" :headers="results[sample].data.headers/*.map(a => a.value)*/" :data="results[sample].data.items"></data-grid>
                            </div>
                        </div>
                    </template>




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
                annotation_variables: [],
                all_annotation_variables_clusters: [],
                annotation_variables_clusters: [],
                annotation_variables_clusters_exclude: [],

                distsumm_options: [{'label': 'Minimum distance', 'value': 'min'}, {'label': 'Average distance', 'value': 'avg'}],

                stgradients: ('stgradients' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stgradients) : {},
                results: {},

                processing: false,

                textOutput: '',

                params: {

                    samples: [], // this.samples.map(sample => sample.name),
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

        async mounted() {
            await this.loadResults();

            let stdiff = await this.$getProjectSTdiffAnnotations(this.project.id);
            this.annotation_variables = stdiff['annotation_variables'];
            this.all_annotation_variables_clusters = stdiff['annotation_variables_clusters'];
        },

        watch: {
            'params.annot'(newValue) {

                this.params.ref = '';

                this.annotation_variables_clusters = [];
                this.annotation_variables_clusters_exclude = [];

                this.all_annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters.sort((a,b) => a.value - b.value);

            },

            'params.ref'(newValue) {

                this.params.exclude = [];

                this.annotation_variables_clusters_exclude = [];

                this.all_annotation_variables_clusters.map(annot => {if(annot.annotation === this.params.annot && annot.cluster !== this.params.ref) this.annotation_variables_clusters_exclude.push({'label': annot.cluster, 'value': annot.cluster})});

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

            getSampleByName(nameToFind) {
                return this.samples.find( sample => sample.name === nameToFind);
            },

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
                data.exclude_string = data.exclude.length ? "c('" + data.exclude.join("','") + "')" : 'NULL';

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

            async loadResults() {
                this.stdiff_ns = ('stgradients' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stgradients) : {};

                if(!('base_url' in this.stgradients))
                    return;

                this.stgradients.samples.forEach( sample => {
                    axios.get(this.stgradients.base_url + 'stgradients_' + sample + '.json' + '?' + Date.now())
                        .then((response) => {
                            this.results[sample] = {};
                            this.results[sample].data = response.data;
                            this.results[sample].loaded = true;
                            console.log(response.data);
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
