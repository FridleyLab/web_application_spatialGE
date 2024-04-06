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

                <div class="w-100 w-lg-90 w-xxl-85" :class="(processing || processing2) ? 'disabled-clicks' : ''">

                    <div class="row justify-content-center text-center m-3">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>
                                <label class="text-lg">
                                    Cell profile signature
                                </label>
                                <show-modal tag="stgradient_annotation_to_test"></show-modal>
                            </div>
                            <div>
                                <span>
                                    <Multiselect :options="gene_signatures" v-model="params.cell_profile"></Multiselect>
                                </span>
                            </div>
                        </div>
                    </div>


                    <div class="row justify-content-center text-center mt-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <label class="me-3 text-lg">
                                <input type="checkbox" v-model="params.refine_cells"> Refine celltypes <show-modal tag="sthet_plot_methods"></show-modal>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="text-center mt-3">
                    <send-job-button label="Run InSituType" :disabled="processing || processing2" :project-id="project.id" job-name="InSituType" @started="runInSituType" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                </div>

                <div v-if="'InSituType' in this.project.project_parameters" class="w-100 w-lg-90 w-xxl-85" :class="(processing || processing2) ? 'disabled-clicks' : ''">
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
                    cell_profile: '',
                    refine_cells: true
                },

                filter_variable: '',

                gene_signatures: [
                    {value: 'HUMAN-Brain_AllenBrainAtlas', label: 'HUMAN-Brain_AllenBrainAtlas'},
                    {value: 'HUMAN-Brain_Darmanis', label: 'HUMAN-Brain_Darmanis'},
                    {value: 'HUMAN-Colon_HCA', label: 'HUMAN-Colon_HCA'},
                    {value: 'HUMAN-Decidua_HCA', label: 'HUMAN-Decidua_HCA'},
                    {value: 'HUMAN-Esophagus_HCA', label: 'HUMAN-Esophagus_HCA'},
                    {value: 'HUMAN-Gut_HCA', label: 'HUMAN-Gut_HCA'},
                    {value: 'HUMAN-Heart_HCA', label: 'HUMAN-Heart_HCA'},
                    {value: 'HUMAN-Ileum_Wang', label: 'HUMAN-Ileum_Wang'},
                    {value: 'HUMAN-ImmuneCensus_HCA', label: 'HUMAN-ImmuneCensus_HCA'},
                    {value: 'HUMAN-ImmuneTumor_safeTME', label: 'HUMAN-ImmuneTumor_safeTME'},
                    {value: 'HUMAN-Kidney_HCA', label: 'HUMAN-Kidney_HCA'},
                    {value: 'HUMAN-Liver_HCA', label: 'HUMAN-Liver_HCA'},
                    {value: 'HUMAN-Lung_Control_Adams', label: 'HUMAN-Lung_Control_Adams'},
                    {value: 'HUMAN-Lung_COPD_Adams', label: 'HUMAN-Lung_COPD_Adams'},
                    {value: 'HUMAN-Lung_HCA', label: 'HUMAN-Lung_HCA'},
                    {value: 'HUMAN-Lung_IPF_Adams', label: 'HUMAN-Lung_IPF_Adams'},
                    {value: 'HUMAN-Muscle_DeMicheli', label: 'HUMAN-Muscle_DeMicheli'},
                    {value: 'HUMAN-Pancreas_HCA', label: 'HUMAN-Pancreas_HCA'},
                    {value: 'HUMAN-Placenta_HCA', label: 'HUMAN-Placenta_HCA'},
                    {value: 'HUMAN-Prostate_Henry', label: 'HUMAN-Prostate_Henry'},
                    {value: 'HUMAN-Rectum_Wang', label: 'HUMAN-Rectum_Wang'},
                    {value: 'HUMAN-Retina_HCA', label: 'HUMAN-Retina_HCA'},
                    {value: 'HUMAN-Skin_HCA', label: 'HUMAN-Skin_HCA'},
                    {value: 'HUMAN-Spleen_HCA', label: 'HUMAN-Spleen_HCA'},
                    {value: 'HUMAN-Testis_Guo', label: 'HUMAN-Testis_Guo'},

                    {value: 'MOUSE-Bladder_MCA', label: 'MOUSE-Bladder_MCA'},
                    {value: 'MOUSE-BoneMarrow_cKit_MCA', label: 'MOUSE-BoneMarrow_cKit_MCA'},
                    {value: 'MOUSE-BoneMarrow_MCA', label: 'MOUSE-BoneMarrow_MCA'},
                    {value: 'MOUSE-Brain_AllenBrainAtlas', label: 'MOUSE-Brain_AllenBrainAtlas'},
                    {value: 'MOUSE-Brain_MCA', label: 'MOUSE-Brain_MCA'},
                    {value: 'MOUSE-ImmuneAtlas_ImmGen_cellFamily', label: 'MOUSE-ImmuneAtlas_ImmGen_cellFamily'},
                    {value: 'MOUSE-ImmuneAtlas_ImmGen', label: 'MOUSE-ImmuneAtlas_ImmGen'},
                    {value: 'MOUSE-Kidney_MCA', label: 'MOUSE-Kidney_MCA'},
                    {value: 'MOUSE-Liver_MCA', label: 'MOUSE-Liver_MCA'},
                    {value: 'MOUSE-Lung_MCA', label: 'MOUSE-Lung_MCA'},
                    {value: 'MOUSE-MammaryGland_Involution_MCA', label: 'MOUSE-MammaryGland_Involution_MCA'},
                    {value: 'MOUSE-MammaryGland_Lactation_MCA', label: 'MOUSE-MammaryGland_Lactation_MCA'},
                    {value: 'MOUSE-MammaryGland_Pregnancy_MCA', label: 'MOUSE-MammaryGland_Pregnancy_MCA'},
                    {value: 'MOUSE-MammaryGland_Virgin_MCA', label: 'MOUSE-MammaryGland_Virgin_MCA'},
                    {value: 'MOUSE-Muscle_MCA', label: 'MOUSE-Muscle_MCA'},
                    {value: 'MOUSE-Ovary_MCA', label: 'MOUSE-Ovary_MCA'},
                    {value: 'MOUSE-Pancreas_MCA', label: 'MOUSE-Pancreas_MCA'},
                    {value: 'MOUSE-PeripheralBlood_MCA', label: 'MOUSE-PeripheralBlood_MCA'},
                    {value: 'MOUSE-Placenta_MCA', label: 'MOUSE-Placenta_MCA'},
                    {value: 'MOUSE-Prostate_MCA', label: 'MOUSE-Prostate_MCA'},
                    {value: 'MOUSE-SmallIntestine_MCA', label: 'MOUSE-SmallIntestine_MCA'},
                    {value: 'MOUSE-Spleen_MCA', label: 'MOUSE-Spleen_MCA'},
                    {value: 'MOUSE-Stomach_MCA', label: 'MOUSE-Stomach_MCA'},
                    {value: 'MOUSE-Testis_MCA', label: 'MOUSE-Testis_MCA'},
                    {value: 'MOUSE-Thymus_MCA', label: 'MOUSE-Thymus_MCA'},
                    {value: 'MOUSE-Uterus_MCA', label: 'MOUSE-Uterus_MCA'},


                ],

                params2: {
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

            runInSituType() {
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
