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
                            <strong>The Log-normal method</strong> uses... <code>features</code>, etc.
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
                        SCT
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>The SCT method</strong> is... <code>attributes</code>, etc.
                        </div>
                        <div class="mt-3">
                            <label>
                                <input type="radio" name="method" value="log" @click="params.method = 'sct'"> Use SCT
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
                    <div><Multiselect :options="colorPalettes" @change="(value, select) => filter_color_palette = value"></Multiselect></div>
                </div>
                <div class="col">
                    <div>Variable</div>
                    <div><Multiselect :options="JSON.parse(project.project_parameters.filter_meta_options)" @change="(value, select) => filter_variable = value"></Multiselect></div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="float-end">
                    <input type="button" class="btn btn-outline-info float-end" value="Generate plots" @click="normalizedPlots">
                </div>
            </div>

            <div class="mt-4" v-if="!generating_plots">
                <ul class="nav nav-tabs" id="normalizedDiagrams" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="normalized-violinplot-tab" data-bs-toggle="tab" data-bs-target="#normalized-violinplot" type="button" role="tab" aria-controls="normalized-violinplot" aria-selected="false">Violin plots</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="normalized-boxplot-tab" data-bs-toggle="tab" data-bs-target="#normalized-boxplot" type="button" role="tab" aria-controls="normalized-boxplot" aria-selected="true">Boxplots</button>
                    </li>
                </ul>
                <div class="tab-content" id="normalizedDiagramsContent">

                    <div class="tab-pane fade show active" id="normalized-violinplot" role="tabpanel" aria-labelledby="normalized-violinplot-tab">
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
                axios.post(this.normalizeUrlPlots, {color_palette: this.filter_color_palette, variable: this.filter_variable})
                    .then((response) => {
                        this.generating_plots = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                    })
            }
        },

    }
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
:root {
    --ms-tag-bg: #059669;
    --ms-tag-color: #D1FAE5;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;
}
</style>
