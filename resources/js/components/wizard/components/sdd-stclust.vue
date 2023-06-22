<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Spatial Domain Detection with STclust
            </div>
            <div>
                The algorithm STclust detects spatial domains via hierarchical clustering of gene expression weighted by spot-to-spot distances. Users have the option of specifying a number of clusters (k) or use automatic detection using DynamicTreeCuts. The number of clusters inferred by DynamicTreeCuts can be controlled via DeepSplit.
            </div>





            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70  w-xxl-55">

                    <div class="row justify-content-center text-center mt-4">
                        <div class="">
                            <div class="me-3">
                                Spatial weight: <span class="text-lg text-bold text-primary">{{ params.ws }}</span>
                            </div>
                            <input type="range" min="0" max="0.1" step="0.01" class="w-100" v-model="params.ws">
                        </div>
                    </div>

                    <div class="form-check mt-4">
<!--                        <input class="form-check-input" type="checkbox" v-model="dynamicTreeCuts" id="flexCheckDefault">-->
<!--                        <label class="form-check-label text-lg" for="flexCheckDefault">-->
<!--                            DynamicTreeCuts - using {{dynamicTreeCuts ? 'deep split' : 'number of domains'}}-->
<!--                        </label>-->
                        <label class="text-lg">
                            <input type="radio" name="method" value="ds" v-model="method"> Select a range of Ks
                        </label>

                        <label class="text-lg ms-4">
                            <input type="radio" name="method" value="dtc" v-model="method"> Use DynamicTreeCuts
                        </label>
                    </div>

<!--                    <div :class="method === 'dtc' ? '' : 'disabled-clicks'" class="row justify-content-center text-center mt-4">-->
                    <div v-if="method === 'dtc'" class="row justify-content-center text-center mt-4">
                        <div class="">
                            <div class="me-3">
                                DeepSplit: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.deepSplit">
                                <!--                                DeepSplit: <span class="text-lg text-bold text-primary">{{ params.deepSplit }}</span>-->
                            </div>
                            <input type="range" min="0" max="4" step="0.5" class="w-100" v-model="params.deepSplit">
                        </div>
                    </div>

<!--                    <div :class="method === 'ds' ? '' : 'disabled-clicks'" class="mt-4">-->
                    <div v-if="method === 'ds'" class="mt-4">
                        <numeric-range title="Number of domains:" title-class="" :min="2" :max="30" :step="1" :default-max="5" @updated="(min,max) => {params.number_of_domains_min = min; params.number_of_domains_max = max}"></numeric-range>
                    </div>



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
            <send-job-button label="Run STclust" :disabled="processing" :project-id="project.id" job-name="STclust" @started="SDD_STclust" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>


        <div v-if="!processing && 'SDD_STclust' in project.project_parameters">

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Color palette</div>
                    <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
                </div>
            </div>


            <div class="row mt-3">
                <div class="p-3 text-end">
                    <send-job-button label="Generate plots" :disabled="processing || !params.col_pal.length" :project-id="project.id" job-name="STplotExpressionSurfacePlots" @started="generatePlots" @completed="processCompleted" :project="project" ></send-job-button>
                </div>
            </div>
        </div>


        <!-- Create tabs for each sample-->
        <div v-if="!processing && ('stclust' in project.project_parameters) && stclust.parameters.ks.includes('dtc')">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name" type="button" role="tab" :aria-controls="sample.name" aria-selected="true">{{ sample.name }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name" role="tabpanel" :aria-labelledby="sample.name + '-tab'">
                        <div v-for="image in stclust.plots">
                            <show-plot v-if="image.includes(sample.name)" :src="image"></show-plot>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Create tabs for each K value and sub-tabs for each sample -->
        <div v-if="!processing && ('stclust' in project.project_parameters) && !stclust.parameters.ks.includes('dtc')">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <template v-for="index in stclust.parameters.number_of_domains_max">
                    <li v-if="index >= stclust.parameters.number_of_domains_min" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === stclust.parameters.number_of_domains_min ? 'active' : ''" :id="'K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + 'K_' + index" type="button" role="tab" :aria-controls="'K_' + index" aria-selected="true">{{ 'K=' + index }}</button>
                    </li>
                </template>
            </ul>

            <div class="tab-content m-4" id="myTabContent">

                <div v-for="k in stclust.parameters.number_of_domains_max" class="tab-pane fade min-vh-50" :class="k === stclust.parameters.number_of_domains_min ? 'show active' : ''" :id="'K_' + k" role="tabpanel" :aria-labelledby="'K_' + k + '-tab'">

                    <ul class="nav nav-tabs" :id="'myTab' + k" role="tablist">
                        <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                            <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'K_' + k + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'K_' + k" type="button" role="tab" :aria-controls="sample.name + 'K_' + k" aria-selected="true">{{ sample.name }}</button>
                        </li>
                    </ul>

                    <div class="tab-content" :id="'myTabContent' + k">
                        <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'K_' + k" role="tabpanel" :aria-labelledby="sample.name + 'K_' + k + '-tab'">
                            <div v-for="image in stclust.plots">
                                <show-plot v-if="image.includes(sample.name) && image.includes('k' + k)" :src="image"></show-plot>
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

    export default {
        name: 'sddStclust',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sddStclustUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                stclust: ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {},

                processing: false,

                textOutput: '',

                method: 'ds',
                dynamicTreeCuts: false,
                params: {
                    ws: 0.02,
                    number_of_domains_min: 2,
                    number_of_domains_max: 5,
                    deepSplit: 0,
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 3000) ? 3000 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,

                    genes: [],
                    col_pal: 'sunset',
                    data_type: 'tr'
                },

                filter_variable: '',

                plots_visible: []
            }
        },

        watch: {

            'params.deepSplit'(newValue) {
                if(this.params.deepSplit > 4)
                    this.params.deepSplit = 4;
                else if(this.params.deepSplit < 0)
                    this.params.deepSplit = 0;
            },

            'params.ws'(newValue) {
                if(this.params.ws > 0.1)
                    this.params.ws = 0.1;
                else if(this.params.ws < 0)
                    this.params.ws = 0;
            },

            /*stclust: {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.stclust)) {
                        this.plots_visible[gene] = [];
                        for (const [index, sample] of Object.entries(samples)) {
                            this.plots_visible[gene][index] = true;
                        }
                    }
                },
                immediate: true,
            }*/
        },

        methods: {

            SDD_STclust() {
                this.processing = true;

                let parameters = {
                    ws: this.params.ws > 0 ? 'c(0,' + this.params.ws + ')' : '0',
                    ks: this.method === 'ds' ? 'c(' + this.params.number_of_domains_min + ':' + this.params.number_of_domains_max + ')' : "'dtc'",
                    topgenes: this.params.n_genes,
                    deepSplit: (this.method !== 'dtc' || this.params.deepSplit === 0) ? 'F' : this.params.deepSplit,
                    number_of_domains_min: this.params.number_of_domains_min,
                    number_of_domains_max: this.params.number_of_domains_max,
                    ws_value: this.params.ws
                };

                axios.post(this.sddStclustUrl, parameters)
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
                this.$enableWizardStep('spatial-gradients');
            },

            generatePlots() {
                this.processing = true;
                console.log(this.stplotExpressionSurfacePlotsUrl);
                axios.post(this.stplotExpressionSurfacePlotsUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.response);
                    })
            },

            hide_plot: function(gene, sample) {
                this.plots_visible[gene][sample] = false;
            },

            show_reset: function(gene) {
                for(let value in this.plots_visible[gene]) {
                    if (!this.plots_visible[gene][value]) return true;
                }
            },

            reset_plots: function(gene) {
                for(let value in this.plots_visible[gene]) {
                    this.plots_visible[gene][value] = true;
                }
            },

            searchGenes: async function(query) {

                const response = await fetch(
                    '/projects/' + this.project.id + '/search-genes?context=normalized&query=' + query
                );

                const data = await response.json(); // Here you have the data that you need

                return data.map((item) => {
                    return { value: item, label: item }
                })
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
