<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="d-flex my-3 text-bold">
                STdeconvolve
            </div>

            <div class="text-justify mb-4">
                The STdeconvolve method provides a reference-free option to assign biological identity (i.e., phenotyping)
                to sampled ROIs/spots/cells in ST data. STdeconvolve Latent Dirichlet Allocation (LDA) to identify “topics”
                within the ST data. The topics correspond to gene expression profiles potentially representing cell types.
                Assignment of biological identity occurs in a second part, where GSEA or cell-type specific markers are used.
            </div>




            <div class="accordion row" id="accordionFilterTab" :class="processing ? 'disabled-clicks' : ''">
                <div class="accordion-item">
                    <h2 class="accordion-header d-flex" id="headingSelectSamples">
                        <show-modal tag="qcfilter_samples"></show-modal>
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSelectSamples" aria-expanded="false" aria-controls="collapseSelectSamples">
                            Model fitting
                        </button>
                    </h2>
                    <div id="collapseSelectSamples" class="accordion-collapse collapse" aria-labelledby="headingSelectSamples" data-bs-parent="#accordionFilterTab">

                        <div class="row justify-content-center text-center m-3">
                            <div class="w-100 w-lg-90 w-xxl-85">

                                <div class="mt-4">
                                    <numeric-range title="Fit LDA models with this many topics:" show-tool-tip="sdd_spagcn_number_of_domains" title-class="" :min="2" :max="20" :step="1" :default-min="5" :default-max="10" @updated="(min,max) => {params.min_k = min; params.max_k = max}"></numeric-range>
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

                                <div class="p-3 text-center mt-4 mb-3">
                                    <send-job-button label="Run LDA models" :disabled="processing" :project-id="project.id" job-name="STdeconvolve" @started="runSTdeconvolve" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                                </div>

                                <!-- Create tabs for each sample -->
                                <div v-if="!processing && ('STdeconvolve' in project.project_parameters)">

                                    <stdeconvolve-suggested-ks :samples="samples" :STdeconvolve="STdeconvolve" key="step1"></stdeconvolve-suggested-ks>

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
                </div>

                <div class="accordion-item" v-if="!processing && ('STdeconvolve2' in project.project_parameters)">
                    <h2 class="accordion-header d-flex" id="headingBiologicalIdentities">
                        <show-modal tag="qcfilter_samples"></show-modal>
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBiologicalIdentities" aria-expanded="false" aria-controls="collapseBiologicalIdentities">
                            Biological identities
                        </button>
                    </h2>
                    <div id="collapseBiologicalIdentities" class="accordion-collapse collapse" aria-labelledby="headingBiologicalIdentities" data-bs-parent="#accordionFilterTab">

                        <div class="row justify-content-center text-center m-3">
                            <div class="w-100 w-lg-90 w-xxl-85">


                                <button type="button" class="btn btn-sm btn-outline-success mt-3" @click="showSuggestedKs = !showSuggestedKs">Modify Suggested K</button>
                                <!-- Create tabs for each sample -->
                                <div v-if="showSuggestedKs && !processing && ('STdeconvolve' in project.project_parameters)" class="mb-5">

                                    <stdeconvolve-suggested-ks :samples="samples" :STdeconvolve="STdeconvolve" key="step2" :editable="true"></stdeconvolve-suggested-ks>

                                </div>



                                <div class="row justify-content-center text-center m-3">
                                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                        <div>
                                            <label class="text-lg">
                                                Gene signatures
                                            </label>
                                            <show-modal tag="stgradient_annotation_to_test"></show-modal>
                                        </div>
                                        <div>
                                            <span>
                                                <Multiselect :options="gene_signatures" v-model="params2.celltype_markers"></Multiselect>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-center text-center m-4">
                                    <div class="w-50">
                                        <div class="me-3">
                                            <label class="text-lg">
                                                GSEA q-value threshold&nbsp;&nbsp;
                                            </label>
                                            <input type="number" class="text-end text-md border border-1 rounded w-50 w-md-35 w-xl-25" size="3" v-model="params2.q_val">
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-center text-center m-4">
                                    <div class="w-100">
                                        <div class="me-3">
                                            <label class="text-lg">
                                                Scatterpie radius:&nbsp;
                                            </label>
                                            <input type="number" class="text-end text-md border border-1 rounded w-25 w-md-20 w-xl-15" size="3" v-model="params2.user_radius">
                                        </div>
                                        <input type="range" :min="1" :max="1000" step="1" class="w-100" v-model="params2.user_radius">
                                    </div>
                                </div>

                                <div class="row justify-content-center text-center m-4">
                                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                        <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                                        <div><Multiselect :options="colorPalettes" v-model="params2.color_pal"></Multiselect></div>
                                    </div>
                                </div>

                                <div class="p-3 text-center mt-4">
                                    <send-job-button label="Assign identities" :disabled="processing2" :project-id="project.id" job-name="STdeconvolve2" @started="runSTdeconvolve2" @ongoing="processing2 = true" @completed="processCompleted2" :project="project" ></send-job-button>
                                </div>







                                <!-- Create tabs for each sample -->
                                <div v-if="!processing2 && ('STdeconvolve2' in project.project_parameters)">

                                    <div>
                                        <ul class="nav nav-tabs" id="STdeconvolve2_myTab" role="tablist">
                                            <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'STdeconvolve2-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'STdeconvolve2'" type="button" role="tab" :aria-controls="sample.name + 'STdeconvolve2'" aria-selected="true">{{ sample.name }}</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="STdeconvolve2_myTabContent">
                                            <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'STdeconvolve2'" role="tabpanel" :aria-labelledby="sample.name + 'STdeconvolve2-tab'">
                                                <div v-for="image in STdeconvolve2.logfold_plots">
                                                    <show-plot v-if="image.includes(sample.name)" :src="image" :sample="sample"></show-plot>
                                                </div>
                                                <div v-for="image in STdeconvolve2.scatterpie_plots">
                                                    <show-plot v-if="image.includes(sample.name)" :src="image" :sample="sample"></show-plot>
                                                </div>
                                            </div>
                                        </div>
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

    export default {
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stDeconvolveUrl: String,
            stDeconvolve2Url: String,
            colorPalettes: Object,
        },

        data() {
            return {

                STdeconvolve: ('STdeconvolve' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve) : {},
                STdeconvolve2: ('STdeconvolve2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve2) : {},

                processing: false,
                processing2: false,

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

                gene_signatures: [{value: 'celltype_markers_25perc_200toplogFC_blueprint_Nov142023', label: 'BluePrint signatures (200 top genes, highest logFC)'}],

                params2: {
                    celltype_markers: 'celltype_markers_25perc_200toplogFC_blueprint_Nov142023',
                    q_val: 0.05,
                    user_radius: 100,
                    color_pal: 'discreterainbow'
                },

                plots_visible: [],

                showSuggestedKs: false,
            }
        },

        watch: {

            'params.p'(newValue) {
                if(this.params.p > 1)
                    this.params.p = 1;
                else if(this.params.p < 0.05)
                    this.params.p = 0.05;
            },
        },

        mounted() {
            console.log(this.STdeconvolve);
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

            runSTdeconvolve2() {
                this.processing2 = true;

                let params = {
                    selected_k: this.STdeconvolve.selected_k,
                    celltype_markers: this.params2.celltype_markers,
                    q_val: this.params2.q_val,
                    user_radius: this.params2.user_radius,
                    color_pal: this.params2.color_pal
                };

                axios.post(this.stDeconvolve2Url, params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted2() {
                //console.log(this.project.project_parameters);
                this.STdeconvolve2 = ('STdeconvolve2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve2) : {};
                this.processing2 = false;
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
