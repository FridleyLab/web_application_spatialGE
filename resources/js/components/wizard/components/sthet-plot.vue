<template>

    <!--    <div v-if="generating" id="cover" style="position: fixed; height: 100%; width: 100%; top:0; left: 0; background: white; z-index:9999; opacity: 80%">-->
    <!--        <div class="justify-content-center text-center moffitt-bg-blue text-white rounded rounded-pill text-3xl text-bolder" style="width:600px;position: absolute;top: 50%;left: 50%;margin-top: -170px;margin-left: -300px;height: 100px;">-->
    <!--            spatialGE is working, please wait!-->
    <!--        </div>-->
    <!--        <img v-if="generating" src="/images/loading-circular.gif" style="width:100px;position: absolute;top: 50%;left: 50%;margin-top: -50px;margin-left: -50px;height: 100px;" />-->
    <!--    </div>-->

    <div class="m-4">
        <form>

            <div :class="generating ? 'disabled-clicks' : ''">
                <div class="my-3 text-bold">
                    SThet
                </div>
                <div>
                    <p class="text-bold">Select one or more genes to calculate spatial autocorrelation statistics for
                        those genes in each sample. Currently supports Moran’s I and Geary’s C statistics, which measure
                        the tendency of gene expression to show “hot spots” within a tissue. The resulting statistics
                        are displayed in a plot along with sample-level variables if available (i.e., overall survival,
                        tissue type).</p>
                    <ul>
                        <li>Moran’s I: Ranges from -1 to 1. When I is closer to -1, the cell/spots with high expression
                            of a given gene tend to be evenly distributed throughout the tissue. If I is closer to 1,
                            the cells/spots with high expression of a given gene tend to be close or aggregated
                            (“hot-spot”)
                        </li>
                        <li class="mt-3 mb-6">Geary’s C: Ranges from 0 to 2. When C is closer to 2, the cell/spots with
                            high expression of a given gene tend to be evenly distributed throughout the tissue. If C is
                            closer to 0, the cells/spots with high expression of a given gene tend to be close or
                            aggregated (“hot-spot”). The Geary’s C statistic tends to be more sensitive to small-scale
                            changes in expression compared to Moran’s I.
                        </li>
                    </ul>
                </div>


                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                        <div>Search and select genes to calculate SThet</div>
                        <div>
                            <Multiselect
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

                <div class="row justify-content-center text-center m-4">
                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                        <div>Methods</div>
                        <div>
                            <label class="me-3">
                                <input type="checkbox" value="moran" v-model="params.method"> Moran's I
                            </label>
                            <label>
                                <input type="checkbox" value="geary" v-model="params.method"> Geary's C
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pe-3 text-end">
                    <send-job-button label="Calculate Sthet"
                                     :disabled="generating || !params.genes.length"
                                     :project-id="project.id" job-name="SThet" @started="sthet"
                                     @ongoing="generating = true" @completed="processCompleted"
                                     :project="project"></send-job-button>
                </div>



                <template v-if="!generating && 'sthet_genes' in project.project_parameters">
                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Select genes to plot</div>
                            <div>
                                <Multiselect :multiple="true" mode="tags" :searchable="false" :options="sthet_genes" v-model="params.plot_genes"></Multiselect>
                            </div>
                        </div>
                    </div>

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color by</div>
                            <div>
                                <Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect>
                            </div>
                        </div>
                    </div>

                    <div class="row justify-content-center text-center m-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color palette</div>
                            <div>
                                <Multiselect :options="colorPalettes" v-model="params.color_pal"
                                             :searchable="true"></Multiselect>
                            </div>
                        </div>
                    </div>

                    <div class="pe-3 text-end">
                        <send-job-button label="Generate plots"
                                         :disabled="generating_plots || !params.plot_genes.length || !params.color_pal.length || !params.plot_meta.length"
                                         :project-id="project.id" job-name="SThetPlot" @started="sthetPlot"
                                         @ongoing="generating_plots = true" @completed="processCompletedPlots"
                                         :project="project"></send-job-button>
                    </div>
                </template>


            </div>




            <div class="mt-4" v-if="!generating_plots && 'sthet_plot' in project.project_parameters">
                <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="nd-sthetplot-tab" data-bs-toggle="tab"
                                data-bs-target="#nd-sthetplot" type="button" role="tab" aria-controls="nd-sthetplot"
                                aria-selected="true">SThet plot
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="filterDiagramsContent">
                    <div class="tab-pane fade show active" id="nd-sthetplot" role="tabpanel"
                         aria-labelledby="nd-sthetplot-tab">
                        <show-plot :src="project.project_parameters.sthet_plot"></show-plot>
                        <div class="text-center">
                            <a v-if="'sthet_plot_table_results' in project.project_parameters"
                               :href="project.project_parameters.sthet_plot_table_results + '.xlsx'"
                               class="btn btn-sm btn-outline-info" download>Download spatial statistics</a>
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
    name: 'sthetPlot',

    components: {
        Multiselect,
    },

    props: {
        project: Object,
        samples: Object,
        sthetUrl: String,
        sthetPlotUrl: String,
        colorPalettes: Object,
    },

    data() {
        return {

            sthet_genes: 'sthet_genes' in this.project.project_parameters ? JSON.parse(this.project.project_parameters.sthet_genes) : [],

            params: {
                color_pal: 'Spectral',
                plot_meta: '',
                method: ['moran'],
                genes: [],
                plot_genes: [],
            },

            filter_variable: '',

            generating: false,
            generating_plots: false,

            //plot_meta_options: ['race', 'therapy'],
            plot_meta_options: 'metadata_names' in this.project.project_parameters ? this.project.project_parameters.metadata_names : [],
        }
    },

    watch: {
        params: {
            handler(newValue, oldValue) {
                //console.log(this.params);
            },
            deep: true
        }
    },

    methods: {

        sthet() {
            this.generating = true;
            axios.post(this.sthetUrl, this.params)
                .then((response) => {
                })
                .catch((error) => {
                    console.log(error.message)
                })
        },

        sthetPlot() {
            this.generating_plots = true;
            axios.post(this.sthetPlotUrl, this.params)
                .then((response) => {
                })
                .catch((error) => {
                    console.log(error.message)
                })
        },

        processCompleted() {
            this.generating = false;
        },

        processCompletedPlots() {
            this.generating_plots = false;
        },

        searchGenes: async function (query) {

            const response = await fetch(
                '/projects/' + this.project.id + '/search-genes?context=normalized&query=' + query
            );

            const data = await response.json(); // Here you have the data that you need

            return data.map((item) => {
                return {value: item, label: item}
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
//--ms-dropdown-border-color: #3B82F6; --ms-tag-bg: #3B82F6;
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
