<template>
<div class="m-4">
    <form>

        <div>


            <div class="text-justify mb-4">
                Runs the <em>exponly</em> version of CalicoST, a CNV detection method for spatial transcriptomics. This version does not require allele information and works with only gene expression data and spatial coordinates.
            </div>




            <div class="w-100 w-lg-90 w-xxl-85" :class="processing ? 'disabled-clicks' : ''">

                <div class="row justify-content-center text-center">
                    <div class="w-50">
                        <div>Sample selection <show-modal tag="calicost_sample_selection"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="sampleNamesList" v-model="params.sample_name"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-90 w-lg-80 w-xxl-65">
                        <div>Annotation to select normal spots from <show-modal tag="calicost_annotation"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect id="multiselect_annotation_variables" :options="annotation_variables" v-model="params.annotation_name"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                        <div>Cluster(s) that will be considered normal spots <show-modal tag="calicost_annotation_clusters"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect id="multiselect_annotation_variables_clusters" :multiple="false" mode="single" :searchable="true" :options="annotation_variables_clusters" v-model="params.cluster"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- <div class="row justify-content-center text-center mt-4">
                    <div class="w-50">
                        <div>HGTable file <show-modal tag="calicost_hgtable_file"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="hgtable_options" v-model="params.hgtable_file"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div> -->


                <div class="row justify-content-center text-center m-4">
                    <div class="w-xxl-100">
                        <div class="me-3">
                            <label class="text-md">Number of clones:&nbsp;</label>
                            <input type="number" class="text-end border border-1 rounded w-30 w-sm-15 w-md-10 w-xxl-10" v-model="params.n_clones"><show-modal tag="calicost_n_clones"></show-modal>
                        </div>
                        <input v-if="params.n_clones" type="range" :min="3" :max="5" step="1" class="w-100" v-model="params.n_clones">
                    </div>
                </div>

                <!-- <div class="row justify-content-center text-center m-4">
                    <div class="w-xxl-100">
                        <div class="me-3">
                            <label>Number of initializations:&nbsp;</label>
                            <input type="number" class="text-end text-sm border border-1 rounded w-30 w-sm-15 w-md-10 w-xxl-10" v-model="params.num_initializations"><show-modal tag="calicost_num_initializations"></show-modal>
                        </div>
                        <input v-if="params.num_initializations" type="range" min="1" :max="5" step="1" class="w-100" v-model="params.num_initializations">
                    </div>
                </div>

                <div class="row justify-content-center text-center m-4">
                    <div class="w-xxl-100">
                        <div class="me-3">
                            <label>Maximum number of spots to pool:&nbsp;</label>
                            <input type="number" class="text-end text-sm border border-1 rounded w-30 w-sm-15 w-md-10 w-xxl-10" v-model="params.maxspots_pooling"><show-modal tag="calicost_maxspots_pooling"></show-modal>
                        </div>
                        <input v-if="params.maxspots_pooling" type="range" min="1" :max="10" step="1" class="w-100" v-model="params.maxspots_pooling">
                    </div>
                </div> -->


            </div>

            <div class="p-3 text-center mt-4 mb-3">
                <send-job-button label="Run CalicoST" :disabled="processing || !canRunCalicoST" :project-id="project.id" job-name="CalicoST" @started="runCalicoST" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
            </div>

        </div>




        <div v-if="CalicoST && !processing" class="mt-4">

            <ul class="nav nav-tabs" id="CalicoSTtabs" role="tablist">
                <template v-for="(sampleName, index) in CalicoST.samples">
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link"
                            :class="{ active: index === 0 }"
                            :id="'calicost-' + sampleName + '-tab'"
                            data-bs-toggle="tab"
                            :data-bs-target="'#calicost-' + sampleName"
                            type="button"
                            role="tab"
                            :aria-controls="'calicost-' + sampleName"
                            :aria-selected="index === 0 ? 'true' : 'false'"
                        >
                            {{ sampleName }}
                        </button>
                    </li>
                </template>
            </ul>
            <div class="tab-content" id="CalicoSTtabsContent">

                <template v-for="(sampleName, index) in CalicoST.samples">
                    <div class="tab-pane fade" :class="{ show: index === 0, active: index === 0 }" :id="'calicost-' + sampleName" role="tabpanel" :aria-labelledby="'calicost-' + sampleName + '-tab'">
                        <div>
                            <show-plot :src="CalicoST.base_path + CalicoST.files.clone_spatial" :sample="projectSamples.filter(sample => sample.name === sampleName)[0]" :show-image="true"></show-plot>
                        </div>
                        <div>
                            <show-plot :src="CalicoST.base_path + CalicoST.files.total_cn"></show-plot>
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
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            projectSamples: Object,
            colorPalettes: Object,
            calicost2Url: String,
        },

        data() {
            return {

                annotation_variables: [],
                all_annotation_variables_clusters: [],
                annotation_variables_clusters: [],

                params: {
                    sample_name: null,
                    annotation_name: '',
                    n_clones: 3,
                    num_initializations: 3,
                    maxspots_pooling: 7,
                    hgtable_file: 'hg38_gencode',
                    normalidx_file: null,
                    cluster: ''
                },

                processing: false,

                loaded: false,
                results: {},

                CalicoST: null, // {samples: {sample_093d: {src: '/storage/users/9999/167/clone_spatial', src2: '/storage/users/9999/167/total_cn'}}}, //TEMP

                colorPalette: [ "#E8ECFB", "#67B092", "#521A13" ],

                // samples: ['Lung5_Rep2_fov_14', 'Lung5_Rep2_fov_16', 'Lung5_Rep2_fov_19', 'Lung9_Rep2_fov_14', 'Lung9_Rep2_fov_16', 'Lung9_Rep2_fov_19'], //TEMP


            }
        },

        watch: {
            'params.annotation_name'(newValue) {

                this.annotation_variables_clusters = []; // [{'label': 'ALL', 'value': 'NULL'}];

                this.params.clusters = ['NULL'];

                this.all_annotation_variables_clusters.map(annot => {if(annot.annotation === newValue) this.annotation_variables_clusters.push({'label': annot.cluster, 'value': annot.cluster})});

                this.annotation_variables_clusters.sort((a,b) => a.value - b.value);

                // console.log('Clusters for annotation', newValue, this.annotation_variables_clusters);

            },

            'params.clusters'(newValue) {
                if(this.params.clusters.length && this.params.clusters.length === this.annotation_variables_clusters.length) {
                    this.params.clusters.pop();
                }
            },

            'params.n_clones': function(newValue, oldValue) {

                if (!Number.isInteger(newValue)) {
                    this.params.n_clones = oldValue;
                    return;
                }

                if(newValue < 3) {
                    this.params.n_clones = 3;
                }
                if(newValue > 5) {
                    this.params.n_clones = 5;
                }
            },
        },

        computed: {

            hgtable_options() {

                return [{value: 'hg38_gencode', label: 'hg38 GENCODE'}];

            },

            sampleNamesList() {
                return this.projectSamples.map(sample => ({
                    label: sample.name,
                    value: sample.name
                }));
            },

            canRunCalicoST() {

                if(!this.params.annotation_name.length) {
                    return false;
                }
                if(!this.params.sample_name.length) {
                    return false;
                }

                if(!this.params.cluster.length) {
                    return false;
                }

                if(this.params.n_clones < 3 || this.params.n_clones > 5) {
                    return false;
                }

                return true;
            }
        },

        async mounted() {

            this.CalicoST = 'CalicoST' in this.project.project_parameters ? JSON.parse(this.project.project_parameters['CalicoST']) : null;

            let stdiff = await this.$getProjectSTdiffAnnotations(this.project.id);
            this.annotation_variables = stdiff['annotation_variables'];
            this.all_annotation_variables_clusters = stdiff['annotation_variables_clusters'];
            // console.log('ST diff annotations', this.annotation_variables, this.all_annotation_variables_clusters);

            // await this.loadResults();

            // console.log('CalicoST samples', this.projectSamples);
        },

        methods: {

            runCalicoST() {
                this.processing = true;

                console.log('Posting to', this.calicost2Url, 'with params', this.params);

                axios.post(this.calicost2Url, this.params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                this.CalicoST = ('CalicoST' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.CalicoST) : {};
                this.processing = false;
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
