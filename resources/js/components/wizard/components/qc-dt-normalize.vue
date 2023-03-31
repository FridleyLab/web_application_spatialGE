<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Select a method:
        </div>
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        Log-normal
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>Log-normalization:</strong> Counts are normalized to library size (total number of counts per spot or cell), followed by multiplication with a scaling factor. Then, scaled counts are log-transformed (natural logarithm + 1).
<!--                            <strong>The Log-normal method</strong> uses... <code>features</code>, etc.-->
                        </div>
                        <div class="my-3">
                            <label class="form-label">Scaling factor:</label> <input type="number" class="text-end text-sm border border-1 rounded w-10" v-model="params.scale_f">
                        </div>
                        <div class="mt-3">
                            <label>
                                <input type="radio" name="method" value="log" @click="params.method = 'log'"> Use Log-normal
                            </label>
<!--                            <a href="#" class="btn btn-sm btn-outline-info">Use this method</a>-->
                        </div>
                    </div>

                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        SCTransform (Seurat implementation)
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>SCTransform:</strong> Method implemented in Seurat for count transformation and reduction of technical effects. The method applies regularized negative binomial regression with counts per spot or cell as covariate (See their <a class="link-info" href="https://doi.org/10.1186/s13059-019-1874-1">technical article</a> for more details).
                        </div>
                        <div class="mt-3">
                            <label>
                                <input type="radio" name="method" value="log" @click="params.method = 'sct'"> Use SCTransform
                            </label>
                        </div>
                    </div>
                </div>
            </div>
<!--            <div class="accordion-item">-->
<!--                <h2 class="accordion-header" id="headingThree">-->
<!--                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">-->
<!--                        Quantile-->
<!--                    </button>-->
<!--                </h2>-->
<!--                <div id="collapseThree" class="accordion-collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">-->
<!--                    <div class="accordion-body">-->
<!--                        <div>-->
<!--                            <strong>The Quantile method</strong> creates... <code>variables</code>, etc.-->
<!--                        </div>-->
<!--                        <div class="mt-3">-->
<!--                            <label>-->
<!--                                <input type="radio" name="method" value="log" @click="params.method = 'quantile'"> Use Quantile-->
<!--                            </label>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
        </div>


        <div class="row">
            <div class="w-100">
                <div class="text-center w-100 w-md-40 w-lg-30 w-xl-20 float-end">
                    <button type="button" class="btn btn-lg bg-gradient-info w-100 mt-4 mb-0" @click="startProcess" :disabled="processing">{{ processing ? 'Please wait...' : 'Normalize' }}</button>
                </div>
            </div>
        </div>

        <div v-if="'normalized_violin' in project.project_parameters">

            <div class="row mt-5 row-cols-2">
                <div class="col">
                    <div>Color palette</div>
                    <div><Multiselect :options="colorPalettes" v-model="filter_color_palette"></Multiselect></div>
                </div>
                <div class="col">
                    <div>Gene</div>
                    <div>
                        <Multiselect

                            v-model="selected_gene"
                            placeholder="Select options"
                            :close-on-select="true"
                            :searchable="true"
                            :resolve-on-load="false"
                            :delay="0"
                            :min-chars="1"
                            :options="async (query) => { return await searchGenes(query) }"
                        />
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="float-end">
                    <input type="button" class="btn btn-outline-info float-end" :class="generating_plots || !filter_color_palette.length || !selected_gene.length ? 'disabled' : ''" :value="generating_plots ? 'Please wait...' : 'Generate plots'" @click="normalizedPlots">
                </div>
            </div>

            <div class="mt-4" v-if="!generating_plots">
                <ul class="nav nav-tabs" id="normalizedDiagrams" role="tablist">
<!--                    <li class="nav-item" role="presentation">-->
<!--                        <button class="nav-link active" id="normalized-summary-tab" data-bs-toggle="tab" data-bs-target="#normalized-summary" type="button" role="tab" aria-controls="normalized-summary" aria-selected="true">Summary</button>-->
<!--                    </li>-->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="normalized-density-tab" data-bs-toggle="tab" data-bs-target="#normalized-density" type="button" role="tab" aria-controls="normalized-density" aria-selected="true">Count distributions</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link " id="normalized-violinplot-tab" data-bs-toggle="tab" data-bs-target="#normalized-violinplot" type="button" role="tab" aria-controls="normalized-violinplot" aria-selected="false">Violin plots</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="normalized-boxplot-tab" data-bs-toggle="tab" data-bs-target="#normalized-boxplot" type="button" role="tab" aria-controls="normalized-boxplot" aria-selected="false">Boxplots</button>
                    </li>

                </ul>
                <div class="tab-content" id="normalizedDiagramsContent">

<!--                    <div class="tab-pane fade show active" id="normalized-summary" role="tabpanel" aria-labelledby="normalized-summary-tab">-->
<!--                        <div class="text-center m-4">-->
<!--                            <project-summary-table :data="project.project_parameters.normalized_stlist_summary" :reference="project.project_parameters.initial_stlist_summary"></project-summary-table>-->
<!--                        </div>-->
<!--                    </div>-->

                    <div class="tab-pane fade show active" id="normalized-density" role="tabpanel" aria-labelledby="normalized-density-tab">
                        <div class="text-center m-4">
                            <!--                            <img src="/storage/users/9999/1/densityplot.png">-->
                            <!--                            <img src="/storage/users/9999/1/violinplot.png">-->
                            <img src="/storage/users/9999/1/boxplot.png">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="normalized-violinplot" role="tabpanel" aria-labelledby="normalized-violinplot-tab">
                        <div class="text-center m-4">
                            <img :src="project.project_parameters.normalized_violin + '?' + Date.now()">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="normalized-boxplot" role="tabpanel" aria-labelledby="normalized-boxplot-tab">
                        <div class="text-center m-4">
                            <img :src="project.project_parameters.normalized_boxplot + '?' + Date.now()">
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
        name: 'qcDtNormalize',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            colorPalettes: Object,
            normalizeUrl: String,
            normalizeUrlPlots: String,
        },

        data() {
            return {

                params: {
                    method: 'sct',
                    scale_f: 10000
                },

                processing: false,

                generating_plots: false,

                selected_gene: [],
                filter_color_palette: [],
            }
        },



        methods: {

            startProcess() {

                this.processing = true;

                console.log(this.params);

                axios.post(this.normalizeUrl, {parameters: this.params})
                    .then((response) => {
                        console.log(response.data);
                        this.processing = false;

                        for(let property in response.data)
                            this.project.project_parameters[property] = response.data[property];

                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                    })


            },

            normalizedPlots() {
                this.generating_plots = true;
                axios.post(this.normalizeUrlPlots, {color_palette: this.filter_color_palette, gene: this.selected_gene})
                    .then((response) => {
                        this.generating_plots = false;
                    })
                    .catch((error) => {
                        this.generating_plots = false;
                        console.log(error.message)
                    })
            },

            searchGenes: async function(query) {

                const response = await fetch(
                    '/projects/' + this.project.id + '/search-genes?query=' + query
                );

                const data = await response.json(); // Here you have the data that you need

                console.log(data.length);

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
    --ms-tag-color: #3B82F6;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
