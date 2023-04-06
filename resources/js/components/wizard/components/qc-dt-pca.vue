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
            <label class="form-label">Variable genes to calculate PCA:</label> <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="n_genes">
<!--            TODO: recalculate max gene count on the filtered stlist  -->
            <input type="range" min="0" max="40000" step="500" class="form-range" v-model="n_genes">
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


        <div class="mt-4">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">PCA plot</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">

                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
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
            samples: Object,
            filterUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                n_genes: 3000,

                processing: false,

                textOutput: '',

                params: {
                    color_pal: '',
                    plot_meta: '',
                },

                plot_meta_options: ['Therapy']
            }
        },



        methods: {

            startProcess() {

                this.processing = true;

                axios.post(this.filterUrl)
                    .then((response) => {
                        //document.getElementById("imgResult").src = 'data:image/png;base64,' + response.data.image;

                        //this.textOutput = response.data.output;

                        console.log(response.data);

                        this.processing = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                    })


            },
        },

    }
</script>
