<template>
<div class="m-4">
    <form>

        <div>
            <div class="d-flex my-3 text-bold">
                InSituType
            </div>

            <div class="text-justify mb-4">
                The InSituType algorithm is a Bayesian classifier that uses a cell profile matrix to assign cell types to single-cell spatial transcriptomics data (e.g., CosMx). The
                 cell profile matrix is a representation of the expected gene expression for a series of cell types. In spatialGE, the cell profile matrices are provided via the
                 <a class="link-info" href="https://bioconductor.org/packages/release/bioc/html/SpatialDecon.html" target="_blank">SpatialDecon package</a>. In addition to spatial plots showing the
                 location of the classified cells, spatialGE provides an UMAP of based on the gene expression and a “flight path” plot, to assess the confidence of
                 InSituType in classifying the cells. For more information on InSituType please
                 <a class="link-info" href="https://www.biorxiv.org/content/10.1101/2022.10.19.512902v1" target="_blank">click here</a>.
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
                                <input type="number" class="text-end text-md border border-1 rounded w-25 w-md-20 w-xl-15" size="3" v-model="params2.ptsize">
                            </div>
                            <input type="range" :min="1" :max="8" step="1" class="w-100" v-model="params2.ptsize">
                        </div>
                    </div>

                    <div class="row justify-content-center text-center m-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                            <div><Multiselect :options="colorPalettes" v-model="params2.color_pal"></Multiselect></div>
                        </div>
                    </div>
                </div>

                <div v-if="'InSituType' in this.project.project_parameters" class="text-center mt-3">
                    <send-job-button label="Run InSituType - 2" :disabled="processing || processing2" :project-id="project.id" job-name="InSituType2" @started="runInSituType2" @ongoing="processing2 = true" @completed="processCompleted2" :project="project" ></send-job-button>
                </div>




                <!-- <div v-if="inSituType2.plots">
                    <template v-for="(plot, sample, index) in inSituType2.plots">
                        {{ plot }} - {{ sample }} - {{ index }} <br />
                    </template>
                </div> -->


                <div class="mt-4" v-if="!processing && !processing2">
                    <ul class="nav nav-tabs" id="inSituTypePlots" role="tablist">
                        <template v-for="(image, sample, index) in inSituType2.plots">
                            <li class="nav-item" role="presentation" v-if="true || visibleSamples.includes(sample)">
                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="'inSituType-' + sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#inSituType-' + sample" type="button" role="tab" :aria-controls="'inSituType-' + sample" aria-selected="true">{{ sample }}</button>
                            </li>
                        </template>
                    </ul>
                    <div class="tab-content" id="inSituTypePlotsContent">
                        <template v-for="(image, sample, index) in inSituType2.plots">
                            <div v-if="true || visibleSamples.includes(sample)" class="tab-pane fade" :class="index === 0 ? 'show active' : ''"
                                 :id="'inSituType-' + sample" role="tabpanel" :aria-labelledby="'inSituType-' + sample + '-tab'">
                                <div>
                                    <show-plot :src="image" :show-image="Boolean(getSampleByName(sample))" :sample="getSampleByName(sample)" :side-by-side="true" side-by-side-tool-tip="vis_quilt_plot_side_by_side"></show-plot>
                                </div>
                            </div>
                        </template>
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
            inSituTypeUrl: String,
            inSituType2Url: String,
            colorPalettes: Object,
        },

        data() {
            return {

                inSituType: ('InSituType' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType) : {},
                inSituType2: ('InSituType2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType2) : {},

                processing: false,
                processing2: false,

                textOutput: '',

                params: {
                    cell_profile: '',
                    refine_cells: true
                },

                filter_variable: '',

                gene_signatures: [
                    {value: 'Human-Brain_AllenBrainAtlas', label: 'Human-Brain_AllenBrainAtlas'},
                    {value: 'Human-Brain_Darmanis', label: 'Human-Brain_Darmanis'},
                    {value: 'Human-Colon_HCA', label: 'Human-Colon_HCA'},
                    {value: 'Human-Decidua_HCA', label: 'Human-Decidua_HCA'},
                    {value: 'Human-Esophagus_HCA', label: 'Human-Esophagus_HCA'},
                    {value: 'Human-Gut_HCA', label: 'Human-Gut_HCA'},
                    {value: 'Human-Heart_HCA', label: 'Human-Heart_HCA'},
                    {value: 'Human-Ileum_Wang', label: 'Human-Ileum_Wang'},
                    {value: 'Human-ImmuneCensus_HCA', label: 'Human-ImmuneCensus_HCA'},
                    {value: 'Human-ImmuneTumor_safeTME', label: 'Human-ImmuneTumor_safeTME'},
                    {value: 'Human-Kidney_HCA', label: 'Human-Kidney_HCA'},
                    {value: 'Human-Liver_HCA', label: 'Human-Liver_HCA'},
                    {value: 'Human-Lung_Control_Adams', label: 'Human-Lung_Control_Adams'},
                    {value: 'Human-Lung_COPD_Adams', label: 'Human-Lung_COPD_Adams'},
                    {value: 'Human-Lung_HCA', label: 'Human-Lung_HCA'},
                    {value: 'Human-Lung_IPF_Adams', label: 'Human-Lung_IPF_Adams'},
                    {value: 'Human-Muscle_DeMicheli', label: 'Human-Muscle_DeMicheli'},
                    {value: 'Human-Pancreas_HCA', label: 'Human-Pancreas_HCA'},
                    {value: 'Human-Placenta_HCA', label: 'Human-Placenta_HCA'},
                    {value: 'Human-Prostate_Henry', label: 'Human-Prostate_Henry'},
                    {value: 'Human-Rectum_Wang', label: 'Human-Rectum_Wang'},
                    {value: 'Human-Retina_HCA', label: 'Human-Retina_HCA'},
                    {value: 'Human-Skin_HCA', label: 'Human-Skin_HCA'},
                    {value: 'Human-Spleen_HCA', label: 'Human-Spleen_HCA'},
                    {value: 'Human-Testis_Guo', label: 'Human-Testis_Guo'},

                    {value: 'Mouse-Bladder_MCA', label: 'Mouse-Bladder_MCA'},
                    {value: 'Mouse-BoneMarrow_cKit_MCA', label: 'Mouse-BoneMarrow_cKit_MCA'},
                    {value: 'Mouse-BoneMarrow_MCA', label: 'Mouse-BoneMarrow_MCA'},
                    {value: 'Mouse-Brain_AllenBrainAtlas', label: 'Mouse-Brain_AllenBrainAtlas'},
                    {value: 'Mouse-Brain_MCA', label: 'Mouse-Brain_MCA'},
                    {value: 'Mouse-ImmuneAtlas_ImmGen_cellFamily', label: 'Mouse-ImmuneAtlas_ImmGen_cellFamily'},
                    {value: 'Mouse-ImmuneAtlas_ImmGen', label: 'Mouse-ImmuneAtlas_ImmGen'},
                    {value: 'Mouse-Kidney_MCA', label: 'Mouse-Kidney_MCA'},
                    {value: 'Mouse-Liver_MCA', label: 'Mouse-Liver_MCA'},
                    {value: 'Mouse-Lung_MCA', label: 'Mouse-Lung_MCA'},
                    {value: 'Mouse-MammaryGland_Involution_MCA', label: 'Mouse-MammaryGland_Involution_MCA'},
                    {value: 'Mouse-MammaryGland_Lactation_MCA', label: 'Mouse-MammaryGland_Lactation_MCA'},
                    {value: 'Mouse-MammaryGland_Pregnancy_MCA', label: 'Mouse-MammaryGland_Pregnancy_MCA'},
                    {value: 'Mouse-MammaryGland_Virgin_MCA', label: 'Mouse-MammaryGland_Virgin_MCA'},
                    {value: 'Mouse-Muscle_MCA', label: 'Mouse-Muscle_MCA'},
                    {value: 'Mouse-Ovary_MCA', label: 'Mouse-Ovary_MCA'},
                    {value: 'Mouse-Pancreas_MCA', label: 'Mouse-Pancreas_MCA'},
                    {value: 'Mouse-PeripheralBlood_MCA', label: 'Mouse-PeripheralBlood_MCA'},
                    {value: 'Mouse-Placenta_MCA', label: 'Mouse-Placenta_MCA'},
                    {value: 'Mouse-Prostate_MCA', label: 'Mouse-Prostate_MCA'},
                    {value: 'Mouse-SmallIntestine_MCA', label: 'Mouse-SmallIntestine_MCA'},
                    {value: 'Mouse-Spleen_MCA', label: 'Mouse-Spleen_MCA'},
                    {value: 'Mouse-Stomach_MCA', label: 'Mouse-Stomach_MCA'},
                    {value: 'Mouse-Testis_MCA', label: 'Mouse-Testis_MCA'},
                    {value: 'Mouse-Thymus_MCA', label: 'Mouse-Thymus_MCA'},
                    {value: 'Mouse-Uterus_MCA', label: 'Mouse-Uterus_MCA'},

                ],

                params2: {
                    ptsize: 2,
                    color_pal: 'discreterainbow'
                },

            }
        },

        mounted() {

        },

        methods: {

            getSampleByName(nameToFind) {
                return this.samples.find( sample => sample.name === nameToFind);
            },

            runInSituType() {
                this.processing = true;

                axios.post(this.inSituTypeUrl, this.params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                //console.log(this.project.project_parameters);
                this.inSituType = ('inSituType' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.inSituType) : {};
                this.processing = false;
            },

            runInSituType2() {
                this.processing2 = true;

                axios.post(this.inSituType2Url, this.params2)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing2 = false;
                        console.log(error.message);
                    })
            },

            processCompleted2() {
                //console.log(this.project.project_parameters);
                this.inSituType2 = ('InSituType2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType2) : {};
                this.processing2 = false;
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
