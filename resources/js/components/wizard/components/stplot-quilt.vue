<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Quilt Plot
        </div>
        <div>
            Select one or more genes to visualize relative expression at each spot/cell.
        </div>





        <div class="row justify-content-center text-center m-3">
            <div class="w-100 w-md-80 w-lg-70  w-xxl-55">
<!--                <div class="col">-->
<!--                    <div>Color palette</div>-->
<!--                    <div><Multiselect :options="colorPalettes" v-model="params.color_pal"></Multiselect></div>-->
<!--                </div>-->

                <div>
                    <div>Search and select genes</div>
                    <div>
                        <Multiselect
                            v-model="params.genes"
                            mode="tags"
                            placeholder="Select options"
                            :close-on-select="false"
                            :searchable="true"
                            :resolve-on-load="false"
                            :delay="0"
                            :min-chars="1"
                            :options="async (query) => { return await searchGenes(query) }"
                        />
                    </div>
                </div>
            </div>

        </div>

        <div class="row justify-content-center text-center m-4">
            <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                <div class="me-3">Point size: <span class="text-lg text-bold text-primary">{{ params.ptsize }}</span></div>
                <input type="range" min="0" max="5" step="0.1" class="w-100" v-model="params.ptsize">
            </div>
        </div>

        <div class="row mt-3">
            <div class="float-end">
                <input v-if="!generating_quilt" type="button" class="btn btn-outline-info float-end" :class="generating_quilt || !params.genes.length  ? 'disabled' : ''" :value="generating_quilt ? 'Please wait...' : 'Generate plots'" @click="quiltPlot">
                <img v-if="generating_quilt" src="/images/loading-circular.gif" class="float-end mt-3 me-6" style="width:100px" />
            </div>
        </div>


        <div class="mt-4" v-if="!generating_quilt && ('stplot_quilt' in project.project_parameters)">
            <ul class="nav nav-tabs" id="stplotQuilt" role="tablist">
                <li v-for="(samples, gene, index) in quilt_plots" class="nav-item" role="presentation">
                    <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="'quilt-' + gene + '-tab'" data-bs-toggle="tab" :data-bs-target="'#quilt-' + gene" type="button" role="tab" :aria-controls="'quilt-' + gene" aria-selected="true">{{ gene }}</button>
                </li>
            </ul>
            <div class="tab-content" id="stplotQuiltContent">
                <template v-for="(samples, gene, index) in quilt_plots">
                    <div class="tab-pane fade" :class="index === 0 ? 'show active' : ''" :id="'quilt-' + gene" role="tabpanel" :aria-labelledby="'quilt-' + gene + '-tab'">

                        <div class="mt-4">
                            <ul class="nav nav-tabs" id="stplotQuilt" role="tablist">
                                <li v-if="Object.keys(samples).length > 1" class="nav-item" role="presentation">
                                    <button class="nav-link active" :id="'quilt-' + gene + '_' + 'all_samples' + '-tab'" data-bs-toggle="tab" :data-bs-target="'#quilt-' + gene + '_' + 'all_samples'" type="button" role="tab" :aria-controls="'quilt-' + gene + '_' + 'all_samples'" aria-selected="true">All samples</button>
                                </li>
                                <li v-for="(image, sample, index) in samples" class="nav-item" role="presentation">
                                    <button class="nav-link" :class="Object.keys(samples).length === 1 && index === 0 ? 'active' : ''" :id="'quilt-' + gene + '_' + sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#quilt-' + gene + '_' + sample" type="button" role="tab" :aria-controls="'quilt-' + gene + '_' + sample" aria-selected="true">{{ sample }}</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="stplotQuiltContent">

                                <div v-if="Object.keys(samples).length > 1" class="tab-pane fade show active" :id="'quilt-' + gene + '_' + 'all_samples'" role="tabpanel" :aria-labelledby="'quilt-' + gene + '_' + 'all_samples' + '-tab'">

                                    <div v-if="show_reset(gene)" class="m-4">
                                        <button class="btn btn-outline-info" @click="reset_plots(gene)">Reset</button>
                                    </div>

                                    <div class="d-xxl-flex">
                                        <template v-for="(image, sample, index) in samples">
                                            <div v-if="plots_visible[gene][sample]" class="text-center m-4 w-xxl-50">
                                                <img :src="image + '?' + Date.now()" class="img-fluid">
                                                <button @click="hide_plot(gene, sample)" class="btn btn-sm btn-outline-secondary">Hide</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <template v-for="(image, sample, index) in samples">
                                    <div class="tab-pane fade" :class="Object.keys(samples).length === 1 && index === 0 ? 'show active' : ''" :id="'quilt-' + gene + '_' + sample" role="tabpanel" :aria-labelledby="'quilt-' + gene + '_' + sample + '-tab'">
                                        <div>
                                            <div class="text-center m-4">
                                                <img :src="image + '?' + Date.now()" class="img-fluid">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>
            </div>
        </div>


    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'stplotQuilt',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stplotQuiltUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {



                processing: false,

                textOutput: '',

                params: {
                    genes: [],
                    ptsize: 2
                },

                filter_variable: '',

                generating_quilt: false,

                plots_visible: []
            }
        },

        computed: {
            quilt_plots() {
                return JSON.parse(this.project.project_parameters.stplot_quilt);
            }
        },

        watch: {
            'project.project_parameters.stplot_quilt': {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.quilt_plots)) {
                        this.plots_visible[gene] = [];
                        for (const [index, sample] of Object.entries(samples)) {
                            this.plots_visible[gene][index] = true;
                        }
                    }
                },
                immediate: true
            }
        },

        methods: {

            quiltPlot() {
                this.generating_quilt = true;
                axios.post(this.stplotQuiltUrl, this.params)
                    .then((response) => {
                        for(let property in response.data) {
                            console.log(response.data[property]);
                            this.project.project_parameters[property] = response.data[property];
                        }
                        this.generating_quilt = false;
                    })
                    .catch((error) => {
                        this.generating_quilt = false;
                        console.log(error.message)
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
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
