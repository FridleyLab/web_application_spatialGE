<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                STenrich - Gene level
            </div>
            <div>
                Detect genes showing spatial expression patterns (e.g., hotspots). This method tests if spots/cells with high average expression (or enrichment score) of a gene set, shows evidence of spatial aggregation. High expression/score spots or cells are identified using a threshold (average expression/score + X standard deviations).
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-90 w-lg-100 w-xxl-90">
                    <div class="d-flex">
                        <div class="w-60">
                            <div>Input gene sets</div>
                            <div>
                                <textarea class="w-100" v-model="params.gene_sets"></textarea>
                            </div>
                        </div>
                        <label class="w-20 text-lg">
                            <input type="checkbox" v-model="params.average"> Average
                        </label>
                        <label class="w-20 text-lg">
                            <input type="checkbox" v-model="params.gsea"> GSEA score
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
                            <Multiselect :multiple="true" mode="tags" :searchable="true" :options="annotation_variables_clusters" v-model="params.clusters"></Multiselect>
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

<!--        <vue3-easy-data-table-->
<!--            :headers="headers"-->
<!--            :items="items"-->
<!--        />-->


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

                        <a :href="stdiff_ns.base_url + 'stdiff_ns_' + sample + '.csv'" class="btn btn-sm btn-outline-info m-6" download>CSV results</a>

                    </div>
                </div>
            </div>
        </div>





    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'stenrich',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stenrichUrl: String,
        },

        data() {
            return {

                annotation_variables_clusters: [],

                test_types: [{'label': 'Wilcoxon\'s test', 'value': 'wilcoxon'}, {'label': 'T-test', 'value': 't_test'}, {'label': 'Mixed models', 'value': 'mm'}],

                stdiff_ns: ('stdiff_ns' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stdiff_ns) : {},

                processing: false,

                textOutput: '',

                params: {

                    gene_sets: '',
                    average: false,
                    gsea: false,

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
        },

        watch: {

            'params.annotation'(newValue) {

                this.params.clusters = [];

                this.annotation_variables_clusters = [{'label': 'ALL', 'value': 'NULL'}];

                this.project.project_parameters.annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters.sort((a,b) => a.value - b.value);

            },

            'params.clusters'(newValue) {
                let flag = false;
                this.params.clusters.map(cluster => {if(cluster === 'NULL') flag = true;});
                if(flag) this.params.clusters = ['NULL'];
                //console.log(this.params.clusters);
            },

        },

        methods: {

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
                //console.log(this.project.project_parameters);
                this.stclust = ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
                this.processing = false;
                this.$enableWizardStep('differential-expression');
            },
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
