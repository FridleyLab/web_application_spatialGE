<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Expression surface plot
        </div>
        <div>
            Select one or more genes to plot a surface of the gene expression using spatial interpolation (“kriging”). The expression surfaces allow to easily identify hot or cold spots within the tissue.<br/>
            This is a time consuming step, please allow some time for the interpolation to complete.
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
                <div>Color palette</div>
                <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="float-end">
                <input v-if="!generating_quilt" type="button" class="btn btn-outline-info float-end" :class="generating_quilt || !params.genes.length  ? 'disabled' : ''" :value="generating_quilt ? 'Please wait...' : 'Generate plots'" @click="quiltPlot">
                <img v-if="generating_quilt" src="/images/loading-circular.gif" class="float-end mt-3 me-6" style="width:100px" />
            </div>
        </div>


        <div class="mt-4" v-if="!generating_quilt && Object.keys(plots).length /*('stplot_quilt' in project.project_parameters)*/">
            <ul class="nav nav-tabs" id="stplotQuilt" role="tablist">
                <li v-for="(samples, gene, index) in plots" class="nav-item" role="presentation">
                    <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="'expression-surface-' + gene + '-tab'" data-bs-toggle="tab" :data-bs-target="'#expression-surface-' + gene" type="button" role="tab" :aria-controls="'expression-surface-' + gene" aria-selected="true">{{ gene }}</button>
                </li>
            </ul>
            <div class="tab-content" id="stplotQuiltContent">
                <template v-for="(samples, gene, index) in plots">
                    <div class="tab-pane fade" :class="index === 0 ? 'show active' : ''" :id="'expression-surface-' + gene" role="tabpanel" :aria-labelledby="'expression-surface-' + gene + '-tab'">

                        <div class="mt-4">
                            <ul class="nav nav-tabs" id="stplotQuilt" role="tablist">
                                <li v-if="Object.keys(samples).length > 1" class="nav-item" role="presentation">
                                    <button class="nav-link active" :id="'expression-surface-' + gene + '_' + 'all_samples' + '-tab'" data-bs-toggle="tab" :data-bs-target="'#expression-surface-' + gene + '_' + 'all_samples'" type="button" role="tab" :aria-controls="'expression-surface-' + gene + '_' + 'all_samples'" aria-selected="true">All samples</button>
                                </li>
                                <li v-for="(image, sample, index) in samples" class="nav-item" role="presentation">
                                    <button class="nav-link" :class="Object.keys(samples).length === 1 && index === 0 ? 'active' : ''" :id="'expression-surface-' + gene + '_' + sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#expression-surface-' + gene + '_' + sample" type="button" role="tab" :aria-controls="'expression-surface-' + gene + '_' + sample" aria-selected="true">{{ sample }}</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="stplotQuiltContent">

                                <div v-if="Object.keys(samples).length > 1" class="tab-pane fade show active" :id="'expression-surface-' + gene + '_' + 'all_samples'" role="tabpanel" :aria-labelledby="'expression-surface-' + gene + '_' + 'all_samples' + '-tab'">

                                    <div v-if="show_reset(gene)" class="m-4">
                                        <button class="btn btn-outline-info" @click="reset_plots(gene)">Reset</button>
                                    </div>

                                    <div class="d-xxl-flex">
                                        <template v-for="(image, sample, index) in samples">
                                            <div v-if="plots_visible[gene][sample]" class="text-center m-4 w-xxl-50">
                                                <object :data="image + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                                <button @click="hide_plot(gene, sample)" class="btn btn-sm btn-outline-secondary">Hide</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <template v-for="(image, sample, index) in samples">
                                    <div class="tab-pane fade" :class="Object.keys(samples).length === 1 && index === 0 ? 'show active' : ''" :id="'expression-surface-' + gene + '_' + sample" role="tabpanel" :aria-labelledby="'expression-surface-' + gene + '_' + sample + '-tab'">
                                        <div>
                                            <div class="text-center m-4">
                                                <div>
                                                    <object :data="image + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                                </div>
                                                <div class="">
                                                    <a :href="image + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                                                    <a :href="image + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                                                    <a :href="image + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
                                                </div>
<!--                                                <img :src="image + '?' + Date.now()" class="img-fluid">-->
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
        name: 'stplotExpressionSurface',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stplotExpressionSurfaceUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                plots: ('stplot_expression_surface' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stplot_expression_surface) : {},

                processing: false,

                textOutput: '',

                params: {
                    genes: [],
                    ptsize: 2,
                    col_pal: '',
                    data_type: 'tr'
                },

                filter_variable: '',

                generating_quilt: false,

                plots_visible: []
            }
        },

        watch: {
            plots: {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.plots)) {
                        this.plots_visible[gene] = [];
                        for (const [index, sample] of Object.entries(samples)) {
                            this.plots_visible[gene][index] = true;
                        }
                    }
                },
                immediate: true,
            }
        },

        methods: {

            quiltPlot() {
                this.generating_quilt = true;
                axios.post(this.stplotExpressionSurfaceUrl, this.params)
                    .then((response) => {
                        this.plots = response.data;
                        this.generating_quilt = false;
                    })
                    .catch((error) => {
                        this.generating_quilt = false;
                        console.log(error.message);
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
