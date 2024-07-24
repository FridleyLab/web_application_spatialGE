<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Spatial gene set enrichment with STenrich
            </div>
            <div>
                Detect genes showing spatial expression patterns (e.g., hotspots). This method tests if spots/cells with
                high average expression (or enrichment score) of a gene set, shows evidence of spatial aggregation. High
                expression/score spots or cells are identified using a threshold (average expression/score + X standard
                deviations). Currently, the expression of a gene set is calculated as the average of the genes within a
                set. In the future, an option to run STenrich on enrichment scores will be provided.
            </div>


            <div class="row justify-content-center text-center m-3">
                <div class="w-70 w-md-50 w-lg-40 w-xxl-30">
                    <div class="">
                        <div class="">
                            <div>Select a gene set database <show-modal tag="stenrich_select_gene_set"></show-modal></div>
                            <div>
                                <span>
                                    <Multiselect :options="gene_sets_options" v-model="params.gene_sets"></Multiselect>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-bg-light">
                        <div>Coming soon... <show-modal tag="stenrich_select_measure"></show-modal></div>
                        <label class="text-lg">
                            <input type="checkbox" v-model="params.average" disabled> Average
                        </label>
                        <label class="ms-4 text-lg">
                            <input type="checkbox" v-model="params.gsea" disabled> GSEA score
                        </label>
                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-5">
                <div class="w-100 w-xxl-95">
                    <div class="row justify-content-center text-center">
                        <div class="">
                            <div class="me-3">Permutations: <input id="number_of_permutations" type="number" class="text-end text-sm border border-1 rounded w-25 w-md-20 w-xxl-10" v-model="params.permutations"> <show-modal tag="stenrich_permutations"></show-modal></div>
                            <input type="range" min="100" :max="1000000" step="1000" class="w-100" v-model="params.permutations">
                            <div class="me-3">Seed number (permutation): <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-20 w-xxl-10" v-model="params.seed"> <show-modal tag="stenrich_seed_number"></show-modal></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-5">
                <div class="w-100 w-xxl-95">
                    <div class="row justify-content-center text-center">

                            <div class="w-30">Minimum number of spots: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.min_spots"> <show-modal tag="stenrich_minimum_spots"></show-modal></div>
                            <div class="w-30">Minimum number of genes: <input type="number" class="text-end text-sm border border-1 rounded" v-model="params.min_genes"> <show-modal tag="stenrich_minimum_genes"></show-modal></div>

                    </div>
                </div>
            </div>


            <div class="row justify-content-center text-center m-5">
                <div class="w-100 w-xxl-95">
                    <div class="row justify-content-center text-center">
                        <div class="">
                            <div class="me-3">Standard deviations: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-20 w-xxl-10" v-model="params.num_sds"> <show-modal tag="stenrich_standard_deviations"></show-modal></div>
                            <input type="range" min="1" :max="3" step="0.5" class="w-50" v-model="params.num_sds">
                        </div>
                    </div>
                </div>
            </div>






        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run STenrich" :disabled="processing || !this.params.gene_sets.length || !(this.params.permutations >= 100)" :project-id="project.id" job-name="STEnrich" @started="STEnrich" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>




        <div v-if="!processing && ('stenrich' in project.project_parameters)" class="p-3 text-center mt-4">

            <div class="text-justify">
                <div class="fs-5">Explanation of results:</div>
                <ul>
                    <li><strong>Gene set:</strong> The name of the gene set/pathway tested</li>
                    <li><strong>Size test:</strong> The number of genes in the sample that belong to a given gene set/pathway. If this value falls below the set value in “Minimum number of genes”, the gene set was not tested</li>
                    <li><strong>Size gene set:</strong> The total number of genes belonging to a gene set</li>
                    <li><strong>p-value:</strong> The nominal p-value resulting from the permutation procedure</li>
                    <li><strong>Adj. p-value:</strong> The FDR-adjusted (a.k.a Benjamini-Hochberg) p-values. These values are the recommended values to decide if gene set presents spatial patterns</li>
                </ul>
            </div>

            <a :href="stenrich.base_url + 'stenrich_results.xlsx'" class="btn btn-sm btn-outline-info me-2" download>Download results (Excel)</a>
        </div>




        <!-- Create tabs for each sample-->
        <div v-if="!processing && ('stenrich' in project.project_parameters)">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li v-for="(sample, index) in stenrich.samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div v-for="(sample, index) in stenrich.samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">
                        <div class="m-4">
<!--                            <a :href="stenrich.base_url + 'stenrich_' + sample + '.csv'" class="btn btn-sm btn-outline-info my-3" download>CSV results</a>-->

                            <ul class="nav nav-tabs" :id="'myTab-' + sample" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" :id="sample + '-tab-data'" data-bs-toggle="tab" :data-bs-target="'#' + sample + '-data'" type="button" role="tab" :aria-controls="sample + '-data'" aria-selected="true">Results</button>
                                </li>
                                <li v-if="'heatmap' in stenrich" class="nav-item" role="presentation">
                                    <button class="nav-link" :id="sample + '-tab-heatmap'" data-bs-toggle="tab" :data-bs-target="'#' + sample + '-heatmap'" type="button" role="tab" :aria-controls="sample + '-heatmap'" aria-selected="true">Heatmap</button>
                                </li>
                            </ul>

                            <div class="tab-content" :id="'myTabContent-' + sample">
                                <div class="tab-pane fade min-vh-50 show active" :id="sample + '-data'" role="tabpanel" :aria-labelledby="sample + '-tab-data'">
                                    <div class="m-4">
                                        <data-grid v-if="(sample in results) && results[sample].loaded" :headers="results[sample].data.headers/*.map(a => a.value)*/" :data="results[sample].data.items"></data-grid>
                                    </div>
                                </div>
                                <div class="tab-pane fade min-vh-50" :id="sample + '-heatmap'" role="tabpanel" :aria-labelledby="sample + '-tab-heatmap'">
                                    <div class="m-4">
                                        <show-plot :src="stenrich.base_url + stenrich.heatmap" :show-image="false" :sample="getSampleByName(sample)" :side-by-side="false"></show-plot>
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
        name: 'stenrich',

        components: {
            Multiselect,
            Vue3EasyDataTable
        },

        props: {
            project: Object,
            samples: Object,
            stenrichUrl: String,
        },

        data() {
            return {

                gene_sets_options: [
                    {'label': 'KEGG - human', 'value': 'kegg'},
                    {'label': 'HALLMARK - human', 'value': 'hallmark'},
                    {'label': 'HALLMARK - Mouse', 'value': 'mh.all.v2023.1.Mm.symbols'},
                    {'label': 'REACTOME - Human', 'value': 'c2.cp.reactome.v2023.1.Hs.symbols'},
                    {'label': 'REACTOME - Mouse', 'value': 'm2.cp.reactome.v2023.1.Mm.symbols'},
                    {'label': 'GO - Biological process - Human', 'value': 'c5.go.bp.v2023.1.Hs.symbols'},
                    {'label': 'GO - Cellular component - Human', 'value': 'c5.go.cc.v2023.1.Hs.symbols'},
                    {'label': 'GO - Molecular function - Human', 'value': 'c5.go.mf.v2023.1.Hs.symbols'},
                    {'label': 'GO - Biological process - Mouse', 'value': 'm5.go.bp.v2023.1.Mm.symbols'},
                    {'label': 'GO - Cellular Component - Mouse', 'value': 'm5.go.cc.v2023.1.Mm.symbols'},
                    {'label': 'GO - Molecular function - Mouse', 'value': 'm5.go.mf.v2023.1.Mm.symbols'}
                ],

                stenrich: ('stenrich' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stenrich) : {},
                results: {},

                processing: false,

                textOutput: '',

                params: {

                    gene_sets: '',
                    average: false,
                    gsea: false,
                    permutations: 100,
                    seed: 12345,
                    min_spots: 5,
                    min_genes: 5,
                    num_sds: 1,
                },

            }
        },

        mounted() {
            //console.log(this.project.project_parameters.annotation_variables_clusters);
            this.loadResults();
        },

        watch: {

        },

        methods: {

            getSampleByName(nameToFind) {
                return this.samples.find( sample => sample.name === nameToFind);
            },

            STEnrich() {

                this.processing = true;

                axios.post(this.stenrichUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                //console.log(this.project.project_parameters);
                this.stclust = ('stenrich' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
                this.processing = false;
                //this.$enableWizardStep('differential-expression');

                this.loadResults();
            },

            loadResults() {
                if(!('base_url') in this.stenrich)
                    return;

                this.stenrich.samples?.forEach( sample => {
                    axios.get(this.stenrich.base_url + 'stenrich_' + sample + '.json' + '?' + Date.now())
                        .then((response) => {
                            this.results[sample] = {};
                            this.results[sample].data = response.data;
                            this.results[sample].loaded = true;
                            console.log(this.results[sample].data);
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
