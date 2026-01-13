<template>
<div class="m-4">
    <form>

        <div>


            <div class="text-justify mb-4">
                Method description...
            </div>




            <div class="w-100 w-lg-90 w-xxl-85" :class="processing ? 'disabled-clicks' : ''">

                <div class="row justify-content-center text-center">
                    <div class="w-50">
                        <div>Bulk-RNA expression study <show-modal tag="degas_bulk_rna_study"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="studyNames" v-model="params.tcga_study"></Multiselect>
                            </span>
                        </div>
                    </div>
                    <div class="w-50">
                        <div>Clinical feature <show-modal tag="degas_clinical_feature"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="params.tcga_study ? Object.keys(tcgaVariables[params.tcga_study]) : []" v-model="params.tcga_feature"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>


                <div class="row text-center align-content-center mt-4">
                    <div class="w-50">
                        <div>Risk categories <show-modal tag="degas_category_risk"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="params.tcga_feature ? categoryNamesRisk : []" v-model="tcga_categories_risk" :multiple="true" mode="tags"></Multiselect>
                            </span>
                        </div>
                    </div>

                    <div class="w-50">
                        <div>Non-risk categories <show-modal tag="degas_category_non_risk"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect :options="params.tcga_feature ? categoryNamesNonRisk : []" v-model="tcga_categories_non_risk" :multiple="true" mode="tags"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>


                <div class="row justify-content-center text-center m-4">
                    <div class="w-xxl-100">
                        <div class="me-3">
                            <label>Threshold for removing non-expressed genes in the study:&nbsp;</label>
                            <input type="number" step="0.05" class="text-end text-sm border border-1 rounded w-30 w-sm-15 w-md-10 w-xxl-10" v-model="params.zero_thr"><show-modal tag="degas_threshold"></show-modal>
                        </div>
                        <input v-if="params.zero_thr" type="range" min="0" :max="1" step="0.05" class="w-100" v-model="params.zero_thr">
                    </div>
                </div>

                <div class="row justify-content-center text-center m-4">
                    <div class="w-xxl-100">
                        <div class="me-3">
                            <label>Top variable genes percentile:&nbsp;</label>
                            <input type="number" step="0.05" class="text-end text-sm border border-1 rounded w-30 w-sm-15 w-md-10 w-xxl-10" v-model="params.top_var"><show-modal tag="degas_top_var_genes"></show-modal>
                        </div>
                        <input v-if="params.top_var" type="range" min="0" max="0.5" step="0.05" class="w-100" v-model="params.top_var">
                    </div>
                </div>

                <div class="row text-center align-content-center">
                    <div class="w-50">
                        <div>Number of layers in the neural network <show-modal tag="degas_number_of_layers"></show-modal></div>
                        <div>
                            <input type="number" class="text-end border border-1 rounded w-60 w-sm-30 w-md-20 w-xxl-20" v-model="params.number_of_layers">
                        </div>
                    </div>

                    <div class="w-50">
                        <div>Number of Bootstrap samples <show-modal tag="degas_boostraps"></show-modal></div>
                        <div>
                            <input type="number" class="text-end border border-1 rounded w-60 w-sm-30 w-md-20 w-xxl-20" v-model="params.bootstraps">
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-90 w-lg-80 w-xxl-65">
                        <div>Annotation to test <show-modal tag="degas_annotations"></show-modal></div>
                        <div>
                            <span>
                                <Multiselect id="multiselect_annotation_variables" :options="annotation_variables" v-model="params.annotation"></Multiselect>
                            </span>
                        </div>
                    </div>
                </div>


            </div>

            <div class="p-3 text-center mt-4 mb-3">
                <send-job-button label="Run DEGAS" :disabled="processing || !canRunDEGAS" :project-id="project.id" job-name="DEGAS" @started="runDEGAS" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
            </div>


            <!-- <div v-if="loaded" class="m-4">
                <ul class="nav nav-tabs" id="mySamplesTab" role="tablist">
                    <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                    </li>
                </ul>

                <div class="tab-content" id="mySamplesTabContent">

                    <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">
                        <div class="m-4">
                            <data-grid v-if="(sample in results) && results[sample].loaded" :headers="results[sample].data.headers/*.map(a => a.value)*/" :data="results[sample].data.items" :allow-selection="false"></data-grid>
                        </div>
                    </div>

                </div>
            </div> -->
        </div>






        <div v-if="DEGAS" class="mt-4">

            <color-palettes @colors="colors => colorPalette = colors" palette-type="GRADIENT" default-palette="sunset"></color-palettes>

            <ul class="nav nav-tabs" id="DEGAStabs" role="tablist">
                <template v-for="(sample, index) in projectSamples">
                    <li v-if="DEGAS.samples.includes(sample.name)" class="nav-item" role="presentation">
                        <button class="nav-link" :id="'degas-' + sample.name + '-tab'" data-bs-toggle="tab" :data-bs-target="'#degas-' + sample.name" type="button" role="tab" :aria-controls="'degas-' + sample.name" aria-selected="true">{{ sample.name }}</button>
                    </li>
                </template>
            </ul>
            <div class="tab-content" id="DEGAStabsContent">

                <template v-for="(sample, index) in projectSamples">

                    <div v-if="DEGAS.samples.includes(sample.name)" class="tab-pane fade" :class="index === 0 ? 'show active' : ''" :id="'degas-' + sample.name" role="tabpanel" :aria-labelledby="'degas-' + sample.name + '-tab'">

                        <div>

                            <div v-if="sample.name in results" class="my-4" >

                                <div v-for="(plot_data, plotIndex) in results[sample.name]">

                                    <plots-component
                                        :base="sample.image_file_url !== null && sample.image_file_url.length ? sample.image_file_url : ''"
                                        :csv="plot_data.data"
                                        :title="sample.name + (plotIndex === 0 ? ' - Predicted correlation' : ' - Spatially smooth predictions')"
                                        plot-type="gradient"
                                        :color-palette="colorPalette"
                                        :legend-min="plot_data.min !== undefined ? plot_data.min : 0"
                                        :legend-max="plot_data.max !== undefined ? plot_data.max : 10"
                                        :is-y-axis-inverted="project.project_platform_id === 3"
                                        :is-grouped="false"
                                        :aspect-ratio="project.project_platform_id === 3 ? '3:2': ''"
                                        :p-key="'DEGAS-' + sample.name + '-' + plotIndex"
                                        range-bar-label="pred risk score"
                                    ></plots-component>

                                </div>


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
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            projectSamples: Object,
            colorPalettes: Object,
            tcgaVariables: Object,
            degas2Url: String,
        },

        data() {
            return {

                annotation_variables: [],

                tcga_categories_risk: [],
                tcga_categories_non_risk: [],

                params: {
                    zero_thr: 0.3,
                    top_var: 0.2,
                    annotation: null,
                    number_of_layers: 3,
                    bootstraps: 5,
                    tcga_study: null,
                    tcga_feature: null,
                    risk_cat: '',
                    non_risk_cat: '',
                },

                processing: false,

                loaded: false,
                results: {},

                DEGAS: null,

                colorPalette: [ "#E8ECFB", "#67B092", "#521A13" ],

                // samples: ['Lung5_Rep2_fov_14', 'Lung5_Rep2_fov_16', 'Lung5_Rep2_fov_19', 'Lung9_Rep2_fov_14', 'Lung9_Rep2_fov_16', 'Lung9_Rep2_fov_19'], //TEMP


            }
        },

        watch: {

            'params.tcga_study': function(newValue, oldValue) {
                this.params.tcga_feature = null;
            },

            'params.tcga_feature': function(newValue, oldValue) {
                this.tcga_categories_risk = [];
                this.tcga_categories_non_risk = [];
            },

            'params.number_of_layers': function(newValue, oldValue) {
                if(newValue < 3) {
                    this.params.number_of_layers = 3;
                }
                if(newValue > 5) {
                    this.params.number_of_layers = 5;
                }
            },

            'params.zero_thr': function(newValue, oldValue) {
                if(newValue < 0) {
                    this.params.zero_thr = 0;
                }
                if(newValue > 1) {
                    this.params.zero_thr = 1;
                }
            },

            'params.top_var': function(newValue, oldValue) {
                if(newValue < 0) {
                    this.params.top_var = 0;
                }
                if(newValue > 0.5) {
                    this.params.top_var = 0.5;
                }
            },

            'params.bootstraps': function(newValue, oldValue) {
                if(newValue < 5) {
                    this.params.bootstraps = 5;
                }
                if(newValue > 7) {
                    this.params.bootstraps = 7;
                }
            },

        },

        computed: {

            studyNames() {

                let studies = [];
                Object.entries(this.tcgaVariables).forEach(([studyName, study]) => {
                    // if(studyName.includes('rna_seq')) {
                    //     studies.push({value: studyName, label: studyName.replace('rna_seq', 'RNA-Seq').replace('_mrna', '').replace('mrna', '')});
                    // }
                    // else if(studyName.includes('mrna')) {
                    //     studies.push({value: studyName, label: studyName.replace('mrna', 'microarray').replace('_mrna', '').replace('mrna', '')});
                    // }

                    studies.push({value: studyName, label: studyName});

                });

                return studies;
            },

            categoryNamesRisk() {
                if(!this.params.tcga_study || !this.params.tcga_feature) {
                    return [];
                }

                let categories = this.tcgaVariables[this.params.tcga_study][this.params.tcga_feature]['categories'] || [];

                categories = categories.filter(c => !this.tcga_categories_non_risk.includes(c.name));

                let result = categories.map(c => ({value: c.name, label: c.name + ' (' + c.count + ')'}));
                console.log(result);
                return result;
            },

            categoryNamesNonRisk() {
                if(!this.params.tcga_study || !this.params.tcga_feature) {
                    return [];
                }

                let categories = this.tcgaVariables[this.params.tcga_study][this.params.tcga_feature]['categories'] || [];

                categories = categories.filter(c => !this.tcga_categories_risk.includes(c.name));

                let result = categories.map(c => ({value: c.name, label: c.name + ' (' + c.count + ')'}));
                console.log(result);
                return result;
            },

            canRunDEGAS() {
                if(this.params.zero_thr < 0 || this.params.zero_thr > 1) {
                    return false;
                }
                if(this.params.top_var < 0 || this.params.top_var > 1) {
                    return false;
                }
                if(!this.params.tcga_study || !this.params.tcga_feature || this.tcga_categories_risk.length == 0) {
                    return false;
                }
                if(!this.params.annotation) {
                    return false;
                }
                if(this.params.number_of_layers < 3 || this.params.number_of_layers > 5) {
                    return false;
                }
                if(this.params.bootstraps < 3 || this.params.bootstraps > 5) {
                    return false;
                }

                return true;
            }
        },

        async mounted() {

            this.DEGAS = 'DEGAS' in this.project.project_parameters ? JSON.parse(this.project.project_parameters['DEGAS']) : null;

            let stdiff = await this.$getProjectSTdiffAnnotations(this.project.id);
            this.annotation_variables = stdiff['annotation_variables'];

            //filter out InSituType --> makes the process take too much time
            this.annotation_variables = this.annotation_variables.filter((a) => {
                return a.value !== 'insitutype_cell_types';
            });

            await this.loadResults();

            console.log('DEGAS results loaded', this.results);
        },

        methods: {

            runDEGAS() {

                this.params.risk_cat = this.tcga_categories_risk.map(c => "'" + c + "'").join(",");
                if(this.tcga_categories_non_risk.length > 0) {
                    this.params.non_risk_cat = this.tcga_categories_non_risk.map(c => "'" + c + "'").join(",");
                }
                else {
                    let categories = this.tcgaVariables[this.params.tcga_study][this.params.tcga_feature]['categories'] || [];
                    categories = categories.filter(c => !this.tcga_categories_risk.includes(c));
                    this.params.non_risk_cat = categories.map(c => "'" + c.name + "'").join(",");
                }

                this.processing = true;

                axios.post(this.degas2Url, this.params)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            async loadResults() {

                if(this.DEGAS === null || !('samples' in this.DEGAS)) {
                    return;
                }

                this.loaded = false;

                // if(!('base_url') in this.stenrich)
                //     return;

                // let base_url = '/storage/users/9999/166/';

                let base_url = this.DEGAS.base_path;

                this.DEGAS.files?.forEach( file => {
                    const timestamp = new Date().getTime(); // Unique timestamp to avoid caching

                    let sample = null;
                    this.DEGAS.samples.forEach(s => {
                        if(file.includes(s)) {
                            sample = s;
                        }
                    });
                    if(!sample) {
                        console.warn('Sample not found for file:', file);
                        return;
                    }

                    axios.get(base_url + file + '?cachebuster=' + timestamp)
                        .then((response) => {

                            let data = {}
                            data = {};
                            data.data = response.data;
                            data.loaded = true;

                            if(!(sample in this.results)) {
                                this.results[sample] = [];
                            }

                            this.results[sample].push(data);

                            const lines = data.data.trim().split('\n');
                            if (lines.length > 1) {
                                const values = lines.slice(1).map(line => {
                                    const cols = line.split(',');
                                    return parseFloat(cols[3]);
                                }).filter(v => !isNaN(v));
                                data.min = Math.min(...values);
                                data.max = Math.max(...values);
                            }

                            //console.log(this.results[sample].data);
                        })
                        .catch((error) => {
                            this.results[sample] = {};
                            this.results[sample].data = {};
                            this.results[sample].loaded = false;
                            console.log(error.message);
                            return;
                        })
                });

                this.loaded = true;
            },

            async processCompleted() {
                this.DEGAS = 'DEGAS' in this.project.project_parameters ? JSON.parse(this.project.project_parameters['DEGAS']) : null;
                this.loadResults();
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
