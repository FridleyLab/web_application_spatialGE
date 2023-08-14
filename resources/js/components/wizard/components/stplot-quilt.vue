<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Quilt Plot
        </div>
        <div>
            Select one or more genes to visualize relative expression at each spot/cell.
        </div>





        <div :class="generating_quilt ? 'disabled-clicks' : ''">
            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70  w-xxl-55">

                    <div>
                        <div>Search and select genes</div>
                        <div>
                            <Multiselect
                                id="quilt-plot-gene-list"
                                v-model="params.genes"
                                mode="tags"
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

            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3">Point size: <span class="text-lg text-bold text-primary">{{ params.ptsize }}</span></div>
                    <input type="range" min="0" max="5" step="0.1" class="w-100" v-model="params.ptsize">
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Color palette</div>
                    <div><Multiselect :options="colorPalettes" v-model="params.col_pal" :searchable="true"></Multiselect></div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Data type</div>
                    <div>
                        <label class="me-3">
                            <input type="radio" value="tr" v-model="params.data_type"> Normalized expression
                        </label>
                        <label>
                            <input type="radio" value="raw" v-model="params.data_type"> Raw counts
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="p-3 text-end">
                <send-job-button label="Generate plots" :disabled="generating_quilt || !params.col_pal.length || !params.genes.length" :project-id="project.id" job-name="STplotQuilt" @started="quiltPlot" @ongoing="generating_quilt = true" @completed="processCompleted" :project="project" ></send-job-button>
            </div>
        </div>


        <div class="mt-4" v-if="!generating_quilt && Object.keys(plots).length /*('stplot_quilt' in project.project_parameters)*/">
            <ul class="nav nav-tabs" id="stplotQuilt" role="tablist">

                <li v-if="Object.keys(plots).length > 1" class="nav-item" role="presentation">
                    <button class="nav-link active" :id="'quilt-' + 'all_plots' + '-tab'" data-bs-toggle="tab" :data-bs-target="'#quilt-' + 'all_plots'" type="button" role="tab" :aria-controls="'quilt-' + 'all_plots'" aria-selected="true">All plots</button>
                </li>

                <li v-for="(samples, gene, index) in plots" class="nav-item" role="presentation">
                    <button class="nav-link" :class="(Object.keys(plots).length === 1 && index === 0) ? 'active' : ''" :id="'quilt-' + gene + '-tab'" data-bs-toggle="tab" :data-bs-target="'#quilt-' + gene" type="button" role="tab" :aria-controls="'quilt-' + gene" aria-selected="true">{{ gene }}</button>
                </li>
            </ul>
            <div class="tab-content" id="stplotQuiltContent">

                <div v-if="Object.keys(plots).length > 1" class="tab-pane fade show active" :id="'quilt-' + 'all_plots'" role="tabpanel" :aria-labelledby="'quilt-' + 'all_plots' + '-tab'">

                    <div v-for="sample_object in samples" class="mt-4 ms-4">
                        <div>
                            <div class="text-primary text-bold text-lg">{{ sample_object.name }}</div>
                            <div class="d-xxl-flex">
                                <template v-for="(samples, gene, index) in plots">
                                    <template v-for="(image, sample, index) in samples">
                                        <div v-if="sample === sample_object.name && plots_visible[gene][sample]" class="text-center w-xxl-50">
                                            <show-plot :src="image" :downloadable="false"></show-plot>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>

                <template v-for="(samples, gene, index) in plots">
                    <div class="tab-pane fade" :class="(Object.keys(plots).length === 1 && index === 0) ? 'show active' : ''" :id="'quilt-' + gene" role="tabpanel" :aria-labelledby="'quilt-' + gene + '-tab'">

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
                                            <div v-if="plots_visible[gene][sample]" class="text-center w-xxl-50">
                                                <show-plot :src="image" :downloadable="false"></show-plot>
                                                <button @click="hide_plot(gene, sample)" class="btn btn-sm btn-outline-secondary">Hide</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <template v-for="(image, sample, index) in samples">
                                    <div class="tab-pane fade" :class="Object.keys(samples).length === 1 && index === 0 ? 'show active' : ''" :id="'quilt-' + gene + '_' + sample" role="tabpanel" :aria-labelledby="'quilt-' + gene + '_' + sample + '-tab'">
                                        <div>
                                            <show-plot :src="image" :show-image="Boolean(getSampleByName(sample))" :sample="getSampleByName(sample)" :side-by-side="true"></show-plot>
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

                plots: ('stplot_quilt' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stplot_quilt) : {},

                processing: false,

                textOutput: '',

                params: {
                    genes: [],
                    ptsize: 2,
                    col_pal: 'sunset',
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

            getSampleByName(nameToFInd) {
                return this.samples.find( sample => sample.name === nameToFInd);
            },

            quiltPlot() {
                this.generating_quilt = true;
                axios.post(this.stplotQuiltUrl, this.params)
                    .then((response) => {
                        //this.plots = response.data;
                        //this.generating_quilt = false;
                    })
                    .catch((error) => {
                        //this.generating_quilt = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                this.generating_quilt = false;
                this.plots = ('stplot_quilt' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stplot_quilt) : {};
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
                    '/projects/' + this.project.id + '/search-genes?context=N&query=' + query
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
