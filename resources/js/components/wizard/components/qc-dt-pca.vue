<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Principal Component Analysis - PCA
        </div>
        <div>
            The following PCA plot has been created by calculating the average expression of genes within each sample. This PCA does not incorporate any spatial component of the data.
            The PCA is calculated with a number of user-selected most variable genes based on standard deviation.
        </div>

<!--        <div class="mt-4">-->
<!--            <label class="form-label">Variable genes to calculate PCA:</label> <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes">-->
<!--            <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="500" class="form-range" v-model="params.n_genes">-->
<!--        </div>-->

        <div class="row justify-content-center text-center m-4">
            <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                <div class="me-3">Number of most variable genes to calculate PCA: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"></div>
                <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="500" class="w-100" v-model="params.n_genes">
            </div>
        </div>

        <div class="mt-4 row justify-content-center text-center">
            <label class="form-label">Number of genes to display on heatmap:</label> <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.hm_display_genes">
<!--            <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="10" class="form-range" v-model="params.hm_display_genes">-->
        </div>


        <div class="row mt-5 row-cols-2">
            <div class="col">
                <div>Color palette</div>
                <div><Multiselect :options="colorPalettes" v-model="params.color_pal" :close-on-select="true" :searchable="true"></Multiselect></div>
            </div>

            <div class="col">
                <div>Color by</div>
                <div><Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect></div>
            </div>

        </div>

        <div class="row mt-3">

            <div class="p-3 text-end">
                <send-job-button label="Generate plots" :disabled="generating_pca || !params.color_pal.length || !params.plot_meta.length" :project-id="project.id" job-name="applyPca" @started="applyPca" @completed="processCompleted" :project="project" ></send-job-button>
            </div>

<!--            <div class="float-end">-->
<!--                <input v-if="!generating_pca" type="button" class="btn btn-outline-info float-end" :class="generating_pca || !params.color_pal.length || !params.plot_meta.length ? 'disabled' : ''" :value="generating_pca ? 'Please wait...' : 'Generate plots'" @click="applyPca">-->
<!--&lt;!&ndash;                <img v-if="generating_pca" src="/images/loading-circular.gif" class="float-end mt-3 me-6" style="width:100px" />&ndash;&gt;-->
<!--            </div>-->
<!--            <div v-if="generating_pca" class="text-info text-bold float-end m-4">-->
<!--                The [Principal Component Analysis - PCA] job has been submitted. You will get an email notification when completed. <br />-->
<!--                You can close this window or reload it when notified.-->
<!--            </div>-->
        </div>


        <div class="mt-4" v-if="!generating_pca && 'pseudo_bulk_pca' in project.project_parameters">
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

                    <div class="text-center m-4">
                        <div>
                            <object :data="project.project_parameters.pseudo_bulk_pca + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                        </div>
                        <div>
                            <a :href="project.project_parameters.pseudo_bulk_pca + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                            <a :href="project.project_parameters.pseudo_bulk_pca + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                            <a :href="project.project_parameters.pseudo_bulk_pca + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" id="heatmap" role="tabpanel" aria-labelledby="heatmap-tab">

                    <div class="text-center m-4">
                        <div>
                            <object :data="project.project_parameters.pseudo_bulk_heatmap + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                        </div>
                        <div>
                            <a :href="project.project_parameters.pseudo_bulk_heatmap + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                            <a :href="project.project_parameters.pseudo_bulk_heatmap + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                            <a :href="project.project_parameters.pseudo_bulk_heatmap + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
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
        name: 'qcDtPca',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            pcaUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                textOutput: '',

                params: {
                    color_pal: 'sunset',
                    plot_meta: '',
                    n_genes: 3000,
                    hm_display_genes: 30
                },

                //plot_meta_options: ['race', 'therapy'],
                plot_meta_options: 'metadata_names' in this.project.project_parameters ? this.project.project_parameters.metadata_names : [],

                generating_pca: false,
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
                if(this.params.n_genes < 0)
                    this.params.n_genes = 0;
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
                        //for(let property in response.data)
                        //    this.project.project_parameters[property] = response.data[property];
                        //this.generating_pca = false;
                    })
                    .catch((error) => {
                        //this.generating_pca = false;
                        console.log(error.message)
                    })
            },

            processCompleted() {
                this.generating_pca = false;
            }
        },

    }
</script>
