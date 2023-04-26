<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            STHet Plot
        </div>
        <div>
            Select one or more genes to visualize relative expression ***********TODO***********
        </div>





        <div class="row justify-content-center text-center m-3">
            <div class="w-100 w-md-80 w-lg-70  w-xxl-55">
                <div>
                    <div>Search and select genes</div>
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
        </div>

        <div class="col">
            <div>Color by</div>
            <div><Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect></div>
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
                        <input type="checkbox" value="moran" v-model="params.method"> Moran's I
                    </label>
                    <label>
                        <input type="checkbox" value="geary" v-model="params.method"> Geary's C
                    </label>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="float-end">
                <input v-if="!generating" type="button" class="btn btn-outline-info float-end" :class="generating || !params.genes.length || !params.color_pal.length || !params.plot_meta.length ? 'disabled' : ''" :value="generating ? 'Please wait...' : 'Generate plots'" @click="applyPca">
                <img v-if="generating" src="/images/loading-circular.gif" class="float-end mt-3 me-6" style="width:100px" />
            </div>
        </div>


        <div class="mt-4" v-if="'sthet_plot' in project.project_parameters">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-sthetplot-tab" data-bs-toggle="tab" data-bs-target="#nd-sthetplot" type="button" role="tab" aria-controls="nd-sthetplot" aria-selected="true">SThet plot</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-sthetplot" role="tabpanel" aria-labelledby="nd-sthetplot-tab">

                    <div>
                        <div class="text-center m-4">
                            <object :data="project.project_parameters.sthet_plot + '.svg' + '?' + Date.now()" class="img-fluid"></object>
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
    name: 'sthetPlot',

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

            params: {
                color_pal: '',
                plot_meta: '',
                method: [],
                genes: []
            },

            filter_variable: '',

            generating: false,

            plot_meta_options: ['race', 'therapy'],
        }
    },

    watch: {
        params: {
            handler(newValue, oldValue) {
                console.log(this.params);
            },
            deep: true
        }
    },


    methods: {

        quiltPlot() {
            this.generating = true;
            axios.post(this.quiltUrl, this.params)
                .then((response) => {
                    for(let property in response.data)
                        this.project.project_parameters[property] = response.data[property];
                    this.generating = false;
                })
                .catch((error) => {
                    this.generating = false;
                    console.log(error.message)
                })
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
