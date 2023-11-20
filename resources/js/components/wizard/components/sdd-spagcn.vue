<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="d-flex my-3 text-bold">
                SpaGCN&nbsp;<show-vignette url="/documentation/vignettes/spatial_domain_detection_spagcn.pdf"></show-vignette>
            </div>
            <div>
                The domain detection method <a href="https://www.nature.com/articles/s41592-021-01255-8" class="text-info" target="_blank">SpaGCN</a> (Hu et al. 2021) implements a graph convolutional neural (GCN) network approach to integrate spatial gene expression with the accompanying spatial coordinates and optionally, tissue imaging. The GCNs are used to condense the information from the different data modalities, followed by Louvain clustering to clasify the spots or cells into tissue domains.
            </div>





            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70  w-xxl-55">

                    <div class="row justify-content-center text-center mt-4">
                        <div class="">
                            <div class="me-3">
                                Percentage of neighborhood expression: <span class="text-lg text-bold text-primary">{{ params.p }}</span> <show-modal tag="sdd_spagcn_perc_neigh_expr"></show-modal>
                            </div>
                            <input type="range" min="0.05" max="1" step="0.05" class="w-100" v-model="params.p">
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="me-3">Seed number (permutation): <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-20 w-xxl-10" v-model="params.user_seed"> <show-modal tag="sdd_spagcn_seed_number"></show-modal></div>

                        <div v-if="project.platform_name === 'VISIUM'" class="mt-3">
                            <label class="me-3 text-md">
                                <input type="checkbox" v-model="params.refine_clusters"> Refine clusters? <show-modal tag="sdd_spagcn_refine_clusters"></show-modal>
                            </label>
                        </div>

                    </div>

                    <div class="mt-4">
                        <numeric-range title="Number of domains:" show-tool-tip="sdd_spagcn_number_of_domains" title-class="" :min="2" :max="30" :step="1" :default-max="5" @updated="(min,max) => {params.number_of_domains_min = min; params.number_of_domains_max = max}"></numeric-range>
                    </div>


                    <div class="row justify-content-center text-center m-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                            <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run SpaGCN" :disabled="processing" :project-id="project.id" job-name="SpaGCN" @started="SDD_SpaGCN" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>






        <!-- Create tabs for each K value and sub-tabs for each sample -->
        <div v-if="!processing && ('spagcn' in project.project_parameters)">

<!--            <div class="">-->
<!--                <a :href="project.assets_url + 'SpaGCN.zip'" class="float-end btn btn-sm btn-outline-info" download>Download all results (ZIP)</a>-->
<!--            </div>-->

            <div>
                <ul class="nav nav-tabs" id="SPAGCN_myTab" role="tablist">
                    <template v-for="index in parseInt(spagcn.parameters.number_of_domains_max)">
                        <template v-if="index >= parseInt(spagcn.parameters.number_of_domains_min)">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === parseInt(spagcn.parameters.number_of_domains_min) ? 'active' : ''" :id="'SPAGCN_K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + 'SPAGCN_K_' + index" type="button" role="tab" :aria-controls="'SPAGCN_K_' + index" aria-selected="true">{{ 'K=' + index }}</button>
                            </li>
                        </template>
                    </template>
                    <a :href="project.assets_url + 'SpaGCN.zip'" class="ms-3 btn btn-sm btn-outline-info" download>Download all results (ZIP)</a>
                </ul>

                <div class="tab-content m-4" id="SPAGCN_myTabContent">

                    <div v-for="k in parseInt(spagcn.parameters.number_of_domains_max)" class="tab-pane fade min-vh-50" :class="k === parseInt(spagcn.parameters.number_of_domains_min) ? 'show active' : ''" :id="'SPAGCN_K_' + k" role="tabpanel" :aria-labelledby="'SPAGCN_K_' + k + '-tab'">

                        <ul class="nav nav-tabs" :id="'SPAGCN_myTab' + k" role="tablist">
                            <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'SPAGCN_K_' + k + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'SPAGCN_K_' + k" type="button" role="tab" :aria-controls="sample.name + 'SPAGCN_K_' + k" aria-selected="true">{{ sample.name }}</button>
                            </li>
                        </ul>

                        <div class="tab-content" :id="'SPAGCN_myTabContent' + k">
                            <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'SPAGCN_K_' + k" role="tabpanel" :aria-labelledby="sample.name + 'SPAGCN_K_' + k + '-tab'">
                                <div v-for="image in spagcn.plots">
                                    <template v-if="image.includes('spagcn') && image.includes(sample.name) && (image.endsWith('k' + k) || image.endsWith('k' + k + '_refined'))">
                                        <h4 class="text-center" v-if="image.includes('refined')">Refined clusters</h4>
                                        <show-plot :src="image" :show-image="Boolean(sample.has_image)" :sample="sample" :side-by-side="true"></show-plot>
                                    </template>
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

    export default {
        name: 'sddSpagcn',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sddSpagcnUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                spagcn: ('spagcn' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn) : {},

                processing: false,

                textOutput: '',

                dynamicTreeCuts: false,
                params: {
                    p: 0.5,
                    user_seed: 12345,
                    refine_clusters: this.project.platform_name === 'VISIUM',
                    number_of_domains_min: 2,
                    number_of_domains_max: 5,
                    col_pal: 'smoothrainbow'
                },

                filter_variable: '',

                plots_visible: []
            }
        },

        watch: {

            'params.p'(newValue) {
                if(this.params.p > 1)
                    this.params.p = 1;
                else if(this.params.p < 0.05)
                    this.params.p = 0.05;
            },

            /*spagcn: {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.spagcn)) {
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

            SDD_SpaGCN() {
                this.processing = true;

                let parameters = {
                    p: this.params.p,
                    user_seed: this.params.user_seed,
                    number_of_domains_min: this.params.number_of_domains_min,
                    number_of_domains_max: this.params.number_of_domains_max,
                    refine_clusters: this.params.refine_clusters ? 'True' : 'False',
                    col_pal: this.params.col_pal,
                };

                axios.post(this.sddSpagcnUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                //console.log(this.project.project_parameters);
                this.spagcn = ('spagcn' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn) : {};
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
    /*--ms-dropdown-border-color: #3B82F6;*/
    --ms-tag-bg: #3B82F6;
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
