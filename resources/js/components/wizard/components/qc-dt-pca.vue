<template>
<div class="m-4">
    <form>

        <div :class="generating_plots || generating_pca ? 'disabled-clicks' : ''">

            <div class="my-3 text-bold">
                Principal Component Analysis - PCA
            </div>
            <div>
                The following PCA plot has been created by calculating the average expression of genes within each sample. This PCA does not incorporate any spatial component of the data.
                The PCA is calculated with a number of user-selected most variable genes based on standard deviation. <span class="text-bold">Note:</span> Pseudobulk analysis can only be performed on three samples or more.
            </div>


            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3 mb-3">Number of most variable genes to calculate PCA: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"><show-modal tag="qcpca_number_variable_genes"></show-modal></div>
                    <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="10" class="w-100" v-model="params.n_genes">
                </div>
            </div>

        </div>

        <div class="p-3 text-center">
            <send-job-button v-if="samples.length > 2" label="Calculate PCA" :disabled="generating_pca || generating_plots" :project-id="project.id" job-name="applyPca" @started="applyPca" @ongoing="generating_pca = true" @completed="processCompleted" :project="project" ></send-job-button>
            <div v-else class="text-warning text-xl-center">
                A minimum of 3 samples are needed to Calculate PCA
            </div>
        </div>



        <template v-if="!generating_pca && !generating_plots && 'qc_pca' in project.project_parameters">
            <div class="mt-4 justify-content-center text-center">
                <label class="form-label">Number of genes to display on heatmap:</label>
                <input type="number" class="ms-1 text-end text-sm border border-1 rounded w-25 w-md-20 w-lg-15 w-xxl-10" v-model="params.hm_display_genes">
                <show-modal tag="qcpca_number_genes_display_heatmap"></show-modal>
            </div>


            <div class="row mt-5 row-cols-2">
                <div class="col">
                    <div>Color palette <show-modal tag="qcpca_color_palette"></show-modal></div>
                    <div><Multiselect :options="colorPalettes" v-model="params.color_pal" :close-on-select="true" :searchable="true"></Multiselect></div>
                </div>

                <div class="col">
                    <div>Color by <show-modal tag="qcpca_color_by"></show-modal></div>
                    <div><Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect></div>
                </div>

            </div>

        </template>
        <div v-if="!generating_pca && 'qc_pca' in project.project_parameters" class="row mt-3">
            <div class="p-3 text-end">
                <send-job-button label="Generate plots" :disabled="generating_pca || !params.color_pal.length /*|| !params.plot_meta.length*/" :project-id="project.id" job-name="pcaPlots" @started="pcaPlots" @ongoing="generating_plots = true" @completed="processPlotsCompleted" :project="project" ></send-job-button>
            </div>
        </div>


        <div class="mt-4" v-if="!generating_pca && !generating_plots && ('pseudo_bulk_pca' in project.project_parameters)">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pcaplot-tab" data-bs-toggle="tab" data-bs-target="#pcaplot" type="button" role="tab" aria-controls="pcaplot" aria-selected="true">PCA plot</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="heatmap-tab" data-bs-toggle="tab" data-bs-target="#heatmap" type="button" role="tab" aria-controls="heatmap" aria-selected="true">Heatmap</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="pcaplot" role="tabpanel" aria-labelledby="pcaplot-tab">
                    <div class="m-4">
                        <p><strong>Pseudo-bulk samples in a principal component analysis (PCA) plot.</strong> This analysis is intended to help users detect samples with unexpected gene expression patterns.</p>
                    </div>
                    <show-plot :src="project.project_parameters.pseudo_bulk_pca"></show-plot>
                </div>
                <div class="tab-pane fade" id="heatmap" role="tabpanel" aria-labelledby="heatmap-tab">
                    <div class="m-4">
                        <p><strong>Heatmap of expression of top variable genes (rows) across pseudo-bulk samples (columns).</strong> This plot is intended to help users detect samples with unexpected gene expression patterns.</p>
                    </div>
                    <show-plot :src="project.project_parameters.pseudo_bulk_heatmap"></show-plot>
                </div>
            </div>
        </div>

    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'qcDtPca',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            pcaUrl: String,
            pcaPlotsUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                textOutput: '',

                params: {
                    color_pal: 'Spectral',
                    plot_meta: '',
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 3000) ? 3000 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,
                    hm_display_genes: 30
                },

                plot_meta_options: 'metadata_names' in this.project.project_parameters ? [{'label': 'select a metadata (optional)', 'value': ''} ,...this.project.project_parameters.metadata_names] : [{'label': 'select a metadata (optional)', 'value': ''}],

                generating_pca: false,
                generating_plots: false,
            }
        },

        mounted() {
            console.log(this.project.project_parameters);
            console.log(this.project.project_parameters.metadata_names);
        },

        watch: {
            'params.n_genes'(newValue) {
                if(this.params.n_genes > this.project.project_parameters.pca_max_var_genes)
                    this.params.n_genes = this.project.project_parameters.pca_max_var_genes;
                if(this.params.n_genes < 2)
                    this.params.n_genes = 2;
            },

            'params.hm_display_genes'(newValue) {
                if(this.params.hm_display_genes > this.params.n_genes)
                    this.params.hm_display_genes = this.params.n_genes;
                if(this.params.hm_display_genes < 0)
                    this.params.hm_display_genes = 0;
            }
        },



        methods: {

            applyPca() {
                this.generating_pca = true;
                axios.post(this.pcaUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        //this.generating_pca = false;
                        console.log(error.message)
                    })
            },

            pcaPlots() {
                this.generating_plots = true;
                axios.post(this.pcaPlotsUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        console.log(error.message)
                    })
            },

            processCompleted() {
                this.generating_pca = false;
            },

            processPlotsCompleted() {
                this.generating_plots = false;
            }
        },

    }
</script>
