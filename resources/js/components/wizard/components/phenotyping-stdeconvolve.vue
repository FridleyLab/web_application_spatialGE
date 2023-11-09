<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="d-flex my-3 text-bold">
                STdeconvolve
            </div>
            <div class="text-justify">
                The STdeconvolve method provides a reference-free option to assign biological identity (i.e., phenotyping)
                to sampled ROIs/spots/cells in ST data. STdeconvolve Latent Dirichlet Allocation (LDA) to identify “topics”
                within the ST data. The topics correspond to gene expression profiles potentially representing cell types.
                Assignment of biological identity occurs in a second part, where GSEA or cell-type specific markers are used.
            </div>





            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-lg-90 w-xxl-85">

                    <div class="mt-4">
                        <numeric-range title="Fit LDa models with this many topics:" show-tool-tip="sdd_spagcn_number_of_domains" title-class="" :min="2" :max="20" :step="1" :default-min="5" :default-max="10" @updated="(min,max) => {params.min_k = min; params.max_k = max}"></numeric-range>
                    </div>

                    <div class="form-check mt-4">
                        <label class="text-lg">
                            <input type="checkbox" v-model="params.rm_mt"> Remove mitochondrial genes ("^MT-") <show-modal tag="sdd_stclust_range_of_ks"></show-modal>
                        </label>

                        <label class="text-md ms-3">
                            <input type="checkbox" v-model="params.rm_rp"> Remove ribosomal genes ("^RP[LIS]") <show-modal tag="sdd_stclust_dynamicTreeCuts"></show-modal>
                        </label>
                    </div>

                    <div class="row justify-content-center text-center m-4">
                        <div class="w-xxl-100">
                            <div class="me-3">
                                <label class="text-lg">
                                    <input type="checkbox" v-model="params.use_var_genes">&nbsp;Use <span v-if="params.use_var_genes">this many</span> variable genes<span v-if="params.use_var_genes">:</span>&nbsp;
                                </label>
                                <span v-if="params.use_var_genes"><input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.use_var_genes_n"></span><show-modal tag="qcpca_number_variable_genes"></show-modal>
                            </div>
                            <input v-if="params.use_var_genes" type="range" min="0" :max="40000" step="200" class="w-100" v-model="params.use_var_genes_n">
                        </div>
                    </div>

                    <!-- <div class="row justify-content-center text-center m-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                            <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
                        </div>
                    </div> -->


                </div>
            </div>
        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run STdeconvolve" :disabled="processing" :project-id="project.id" job-name="STdeconvolve" @started="runSTdeconvolve" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>






        <!-- Create tabs for each K value and sub-tabs for each sample -->
        <div v-if="!processing && ('STdeconvolve' in project.project_parameters)">

<!--            <div class="">-->
<!--                <a :href="project.assets_url + 'SpaGCN.zip'" class="float-end btn btn-sm btn-outline-info" download>Download all results (ZIP)</a>-->
<!--            </div>-->

            <div>
                <!-- <ul class="nav nav-tabs" id="STdeconvolve_myTab" role="tablist">
                    <template v-for="index in parseInt(STdeconvolve.parameters.max_k)">
                        <template v-if="index >= parseInt(STdeconvolve.parameters.min_k)">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === parseInt(STdeconvolve.parameters.min_k) ? 'active' : ''" :id="'STdeconvolve_K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + 'STdeconvolve_K_' + index" type="button" role="tab" :aria-controls="'STdeconvolve_K_' + index" aria-selected="true">{{ 'K=' + index }}</button>
                            </li>
                        </template>
                    </template>
                    <a :href="project.assets_url + 'STdeconvolve.zip'" class="ms-3 btn btn-sm btn-outline-info" download>Download all results (ZIP)</a>
                </ul>

                <div class="tab-content m-4" id="STdeconvolve_myTabContent"> -->

                    <!-- <div v-for="k in parseInt(STdeconvolve.parameters.max_k)" class="tab-pane fade min-vh-50" :class="k === parseInt(STdeconvolve.parameters.min_k) ? 'show active' : ''" :id="'STdeconvolve_K_' + k" role="tabpanel" :aria-labelledby="'STdeconvolve_K_' + k + '-tab'"> -->

                        <ul class="nav nav-tabs" id="STdeconvolve_myTab" role="tablist">
                            <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'STdeconvolve-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'STdeconvolve'" type="button" role="tab" :aria-controls="sample.name + 'STdeconvolve'" aria-selected="true">{{ sample.name }}</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="STdeconvolve_myTabContent">
                            <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'STdeconvolve'" role="tabpanel" :aria-labelledby="sample.name + 'STdeconvolve-tab'">
                                <div v-for="image in STdeconvolve.plots">
                                    <show-plot v-if="image.includes(sample.name)" :src="image" :sample="sample"></show-plot>
                                </div>
                            </div>
                        </div>

                    <!-- </div> -->
                <!-- </div> -->
            </div>
        </div>


    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stDeconvolveUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                STdeconvolve: ('STdeconvolve' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve) : {},

                processing: false,

                textOutput: '',

                dynamicTreeCuts: false,
                params: {
                    min_k: 5,
                    max_k: 10,
                    rm_mt: true,
                    rm_rp: true,
                    use_var_genes: true,
                    use_var_genes_n: 1000,
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

            runSTdeconvolve() {
                this.processing = true;

                axios.post(this.stDeconvolveUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                //console.log(this.project.project_parameters);
                this.STdeconvolve = ('STdeconvolve' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve) : {};
                this.processing = false;
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
