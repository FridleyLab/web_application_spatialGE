<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Principal Component Analysis - PCA
        </div>
        <div>
            The following PCA plot has been created by calculating the average expression of genes within each sample. This PCA does not incorporate any spatial component of the data.
        </div>

        <div class="mt-4">
            <label class="form-label">Variable genes to calculate PCA:</label> <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes">
<!--            TODO: recalculate max gene count on the filtered stlist  -->
            <input type="range" min="0" max="40000" step="500" class="form-range" v-model="params.n_genes">
        </div>


        <div class="row mt-5 row-cols-2">
            <div class="col">
                <div>Color palette</div>
                <div><Multiselect :options="colorPalettes" v-model="params.color_pal"></Multiselect></div>
            </div>

            <div class="col">
                <div>Color by</div>
                <div><Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect></div>
            </div>

        </div>

        <div class="row mt-3">
            <div class="float-end">
                <input type="button" class="btn btn-outline-info float-end" :class="generating_pca || !params.color_pal.length || !params.plot_meta.length ? 'disabled' : ''" :value="generating_pca ? 'Please wait...' : 'Generate plots'" @click="applyPca">
            </div>
        </div>


        <div class="mt-4" v-if="'pseudo_bulk_pca' in project.project_parameters">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">PCA plot</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">

                    <div class="text-center m-4">
                        <img :src="project.project_parameters.pseudo_bulk_pca + '?' + Date.now()" class="img-fluid">
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



                processing: false,

                textOutput: '',

                params: {
                    color_pal: '',
                    plot_meta: '',
                    n_genes: 3000,
                },

                plot_meta_options: ['race', 'therapy'],

                generating_pca: false,
            }
        },



        methods: {

            applyPca() {
                this.generating_pca = true;
                axios.post(this.pcaUrl, this.params)
                    .then((response) => {
                        for(let property in response.data)
                            this.project.project_parameters[property] = response.data[property];
                        this.generating_pca = false;
                    })
                    .catch((error) => {
                        this.generating_pca = false;
                        console.log(error.message)
                    })
            },
        },

    }
</script>
