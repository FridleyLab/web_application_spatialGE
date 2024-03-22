<template>
<div class="m-4">
    <form>

        <div>
            <div class="d-flex my-3 text-bold">
                InSituType
            </div>

            <div class="text-justify mb-4">
                The InSituType method ...
            </div>






                    <div class="row justify-content-center text-center m-3">

                        <div class="w-100 w-lg-90 w-xxl-85" :class="(processing2 || processing3) ? 'disabled-clicks' : ''">

                            <div class="row justify-content-center text-center m-3">
                                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                    <div>
                                        <label class="text-lg">
                                            Gene signatures
                                        </label>
                                        <show-modal tag="stgradient_annotation_to_test"></show-modal>
                                    </div>
                                    <div>
                                        <span>
                                            <Multiselect :options="gene_signatures" v-model="params2.celltype_markers"></Multiselect>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-center text-center m-4">
                                <div class="w-100">
                                    <div class="me-3">
                                        <label class="text-lg">
                                            Point size:&nbsp;
                                        </label>
                                        <input type="number" class="text-end text-md border border-1 rounded w-25 w-md-20 w-xl-15" size="3" v-model="params2.user_radius">
                                    </div>
                                    <input type="range" :min="1" :max="1000" step="1" class="w-100" v-model="params2.user_radius">
                                </div>
                            </div>

                            <div class="row justify-content-center text-center m-4">
                                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                    <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                                    <div><Multiselect :options="colorPalettes" v-model="params2.color_pal"></Multiselect></div>
                                </div>
                            </div>

                        </div>


                        <div class="p-3 text-center mt-4">
                            <send-job-button label="Run InSituType" :disabled="processing2 || processing3" :project-id="project.id" job-name="STdeconvolve2" @started="runSTdeconvolve2" @ongoing="processing2 = true" @completed="processCompleted2" :project="project" ></send-job-button>
                        </div>



                        <div v-if="topicNamesChanged" class="p-3 text-center mt-4">
                            <div class="text-warning">Please click the "RENAME TOPICS" button only after completing all annotation changes in all samples</div>
                            <send-job-button label="Rename Topics" :disabled="processing2 || processing3" :project-id="project.id" job-name="STdeconvolve3" @started="runSTdeconvolve3" @ongoing="processing3 = true" @completed="processCompleted3" :project="project" :download-log="false"></send-job-button>
                        </div>



                    </div>


        </div>









    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            stDeconvolveUrl: String,
            stDeconvolve2Url: String,
            stDeconvolve3Url: String,
            colorPalettes: Object,
        },

        data() {
            return {

                STdeconvolve: ('STdeconvolve' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve) : {},
                STdeconvolve2: ('STdeconvolve2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve2) : {},

                processing: false,
                processing2: false,
                processing3: false,

                textOutput: '',

                dynamicTreeCuts: false,
                params: {
                    min_k: 5,
                    max_k: 10,
                    rm_mt: true,
                    rm_rp: true,
                    use_var_genes: true,
                    use_var_genes_n: 1000,
                    col_pal: 'smoothrainbow'
                },

                filter_variable: '',

                gene_signatures: [
                    {value: 'CellMarker2.0_Human_Nov162023', label: 'CellMarker signatures (v2.0, Human)'},
                    {value: 'CellMarker2.0_CancerHuman_Nov162023', label: 'CellMarker signatures (v2.0, Human-Cancer)'},
                    {value: 'CellMarker2.0_Human_Breast__Nov162023', label: 'CellMarker signatures (v2.0, Human-Breast)'},
                    {value: 'CellMarker2.0_Mouse__Normal_cell_Nov162023', label: 'CellMarker signatures (v2.0, Mouse)'},
                    {value: 'CellMarker2.0_Mouse__Cancer_cell_Nov162023', label: 'CellMarker signatures (v2.0, Mouse-Cancer)'},
                    {value: 'CellMarker2.0_Mouse_Bone__Nov162023', label: 'CellMarker signatures (v2.0, Mouse-Bone)'},
                    {value: 'celltype_markers_25perc_200toplogFC_blueprint_Nov142023', label: 'BluePrint signatures (200 top genes, highest logFC)'},
                    {value: 'CellMarker2.0_Mouse_Bone__Nov162023_Curated_Feb262024', label: 'CellMarker signatures (v2.0, Mouse-Bone - curated)'},
                ],

                params2: {
                    celltype_markers: 'CellMarker2.0_Human_Nov162023',
                    q_val: 0.05,
                    user_radius: 100,
                    color_pal: 'discreterainbow'
                },

                plots_visible: [],

                showSuggestedKs: false,

                topicNamesChanged: false,
            }
        },

        watch: {

            'params.p'(newValue) {
                if(this.params.p > 1)
                    this.params.p = 1;
                else if(this.params.p < 0.05)
                    this.params.p = 0.05;
            },
        },

        mounted() {
            this.diplicateTopicNames();

            console.log(this.STdeconvolve2);
        },

        methods: {

            runSTdeconvolve() {
                this.processing = true;

                axios.post(this.stDeconvolveUrl, this.params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                //console.log(this.project.project_parameters);
                this.STdeconvolve = ('STdeconvolve' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve) : {};
                this.processing = false;
            },

            runSTdeconvolve2() {
                this.processing2 = true;

                let params = {
                    selected_k: this.STdeconvolve.selected_k,
                    celltype_markers: this.params2.celltype_markers,
                    q_val: this.params2.q_val,
                    user_radius: this.params2.user_radius,
                    color_pal: this.params2.color_pal
                };

                axios.post(this.stDeconvolve2Url, params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing2 = false;
                        console.log(error.message);
                    })
            },

            processCompleted2() {
                //console.log(this.project.project_parameters);
                this.STdeconvolve2 = ('STdeconvolve2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve2) : {};
                this.diplicateTopicNames();
                this.processing2 = false;
            },


            runSTdeconvolve3() {
                this.processing3 = true;

                let params = {
                    logfold_plots: this.STdeconvolve2['logfold_plots'],
                };

                axios.post(this.stDeconvolve3Url, params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing3 = false;
                        console.log(error.message);
                    })
            },

            processCompleted3() {
                //console.log(this.project.project_parameters);
                this.STdeconvolve2 = ('STdeconvolve2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.STdeconvolve2) : {};
                this.diplicateTopicNames();
                this.processing3 = false;
            },


            getTopicNewAnnotation(sample_name, topic) {
                let obj = this.STdeconvolve2.topic_annotations[sample_name][topic];
                return typeof obj === 'object' ? obj["new_annotation"] : '';
            },

            diplicateTopicNames() {
                if('logfold_plots' in this.STdeconvolve2) {
                    Object.entries(this.STdeconvolve2['logfold_plots']).forEach(([keySample, sample]) => {
                        Object.entries(sample).forEach(([keyTopic, topic]) => {
                            topic['current_annotation'] = topic['new_annotation'];
                        });
                    });
                }
            },

            _topicNamesChanged() {
                this.topicNamesChanged = false;
                if('logfold_plots' in this.STdeconvolve2) {
                    Object.entries(this.STdeconvolve2['logfold_plots']).forEach(([keySample, sample]) => {
                        Object.entries(sample).forEach(([keyTopic, topic]) => {
                            if(topic['current_annotation'].trim() !== topic['new_annotation']) this.topicNamesChanged = true; //change found
                        });
                    });
                }
            },

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
    /*--ms-dropdown-border-color: #3B82F6;*/
    --ms-tag-bg: #3B82F6;
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
