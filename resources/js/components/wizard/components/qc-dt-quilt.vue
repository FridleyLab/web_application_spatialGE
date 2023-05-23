<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Quilt Plot
        </div>
        <div>
            Select two samples to compare the distribution of counts, genes, or any spot-level  data in the spatial context.
        </div>



        <div v-if="'filter_meta_options' in project.project_parameters">

            <div :class="generating_quilt ? 'disabled-clicks' : ''">
                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-80 w-lg-70  w-xxl-55 row row-cols-2">
                    <div class="col">
                        <div>Color palette</div>
                        <div><Multiselect :options="colorPalettes" v-model="params.color_pal" :close-on-select="true" :searchable="true"></Multiselect></div>
                    </div>

                    <div class="col">
                        <div>Variable</div>
                        <div><Multiselect :options="JSON.parse(project.project_parameters.filter_meta_options)" v-model="params.plot_meta"></Multiselect></div>
                    </div>
                    </div>

                </div>

                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-80 w-lg-70  w-xxl-55 row row-cols-2">
                        <div class="col">
                            <label for="sampleList2" class="form-label text-lg">First sample:</label>
                            <select id="sampleList2" class="p-2 form-select w-100 border border-1" v-model="params.sample1">
                                <option value="">-- select a sample --</option>
                                <option v-for="sample in samples" :value="sample.name">{{ sample.name }}</option>
                            </select>
                        </div>
                        <div class="col">
                            <label for="sampleList1" class="form-label text-lg">Second sample:</label>
                            <select id="sampleList2" class="p-2 form-select w-100 border border-1" v-model="params.sample2">
                                <option value="">-- select a sample --</option>
                                <option v-for="sample in samples" :value="sample.name">{{ sample.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">

                <div class="p-3 text-end">
                    <send-job-button label="Generate plots" :disabled="generating_quilt || !params.color_pal.length || !params.plot_meta.length || !params.sample1.length || !params.sample2.length || (params.sample1===params.sample2)" :project-id="project.id" job-name="quiltPlot" @started="quiltPlot" @ongoing="generating_quilt = true" @completed="processCompleted" :project="project" ></send-job-button>
                </div>

            </div>


            <div class="mt-4" v-if="!generating_quilt && 'quilt_plot_1' in project.project_parameters">
                <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">Quilt plot</button>
                    </li>
                </ul>
                <div class="tab-content" id="filterDiagramsContent">
                    <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">

                        <div class="my-3 text-center"><h3>Initial data set</h3></div>

                        <div class="d-xxl-flex">
                            <div class="text-center m-4 w-xxl-50">
                                <div>
                                    <object :data="project.project_parameters.quilt_plot_1_initial + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                </div>
                                <div>
                                    <a :href="project.project_parameters.quilt_plot_1_initial + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                                    <a :href="project.project_parameters.quilt_plot_1_initial + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                                    <a :href="project.project_parameters.quilt_plot_1_initial + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
                                </div>
                            </div>

                            <div class="text-center m-4 w-xxl-50">
                                <div>
                                    <object :data="project.project_parameters.quilt_plot_2_initial + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                </div>
                                <div>
                                    <a :href="project.project_parameters.quilt_plot_2_initial + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                                    <a :href="project.project_parameters.quilt_plot_2_initial + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                                    <a :href="project.project_parameters.quilt_plot_2_initial + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
                                </div>
                            </div>
                        </div>


                        <div class="mb-3 mt-5 text-center"><h3>Filtered & Normalized data set</h3></div>

                        <div class="d-xxl-flex">
                            <div class="text-center m-4 w-xxl-50">
                                <div>
                                    <object :data="project.project_parameters.quilt_plot_1 + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                </div>
                                <div>
                                    <a :href="project.project_parameters.quilt_plot_1 + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                                    <a :href="project.project_parameters.quilt_plot_1 + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                                    <a :href="project.project_parameters.quilt_plot_1 + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
                                </div>
                            </div>

                            <div class="text-center m-4 w-xxl-50">
                                <div>
                                    <object :data="project.project_parameters.quilt_plot_2 + '.svg' + '?' + Date.now()" class="img-fluid"></object>
                                </div>
                                <div>
                                    <a :href="project.project_parameters.quilt_plot_2 + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
                                    <a :href="project.project_parameters.quilt_plot_2 + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
                                    <a :href="project.project_parameters.quilt_plot_2 + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
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
        name: 'qcDtQuilt',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            quiltUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                params: {
                    color_pal: 'sunset',
                    plot_meta: '',
                    sample1: '',
                    sample2: '',
                },

                filter_variable: '',

                generating_quilt: false,
            }
        },



        methods: {

            quiltPlot() {
                this.generating_quilt = true;
                axios.post(this.quiltUrl, this.params)
                    .then((response) => {
                        /*for(let property in response.data)
                            this.project.project_parameters[property] = response.data[property];
                        this.generating_quilt = false;*/
                    })
                    .catch((error) => {
                        this.generating_quilt = false;
                        console.log(error.message)
                    })
            },

            processCompleted() {
                this.generating_quilt = false;
            }
        },

    }
</script>
