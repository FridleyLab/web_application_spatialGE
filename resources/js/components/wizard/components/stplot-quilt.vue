<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Quilt Plot
        </div>
        <div>
            Select two samples to compare the distribution of counts, genes, or any spot-level  data in the spatial context.
        </div>





        <div class="row justify-content-center text-center m-3">
            <div class="w-100 w-md-80 w-lg-70  w-xxl-55 row row-cols-2">
            <div class="col">
                <div>Color palette</div>
                <div><Multiselect :options="colorPalettes" v-model="params.color_pal"></Multiselect></div>
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

        <div class="row mt-3">
            <div class="float-end">
                <input v-if="!generating_quilt" type="button" class="btn btn-outline-info float-end" :class="generating_quilt || !params.color_pal.length || !params.plot_meta.length || !params.sample1.length || !params.sample2.length || (params.sample1===params.sample2) ? 'disabled' : ''" :value="generating_quilt ? 'Please wait...' : 'Generate plot'" @click="quiltPlot">
                <img v-if="generating_quilt" src="/images/loading-circular.gif" class="float-end mt-3 me-6" style="width:100px" />
            </div>
        </div>


        <div class="mt-4" v-if="'quilt_plot_1' in project.project_parameters">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">Quilt plot</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">

                    <div class="d-xxl-flex">
                        <div class="text-center m-4 w-xxl-50">
                            <img :src="project.project_parameters.quilt_plot_1 + '?' + Date.now()" class="img-fluid">
                        </div>

                        <div class="text-center m-4 w-xxl-50">
                            <img :src="project.project_parameters.quilt_plot_2 + '?' + Date.now()" class="img-fluid">
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
                    color_pal: '',
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
                axios.post(this.stplotQuiltUrl, this.params)
                    .then((response) => {
                        for(let property in response.data)
                            this.project.project_parameters[property] = response.data[property];
                        this.generating_quilt = false;
                    })
                    .catch((error) => {
                        this.generating_quilt = false;
                        console.log(error.message)
                    })
            },
        },

    }
</script>
