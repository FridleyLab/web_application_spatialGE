<template>
<div class="m-4">
    <form>

        <div>
            <div class="d-flex my-3 text-bold">
                MILWRM
            </div>
            <div class="">
                The MILWRM module enables detection of spatial domains across tissues. Hence, the detected spatial domains represent the same gene expression profiles across samples. The pipeline leverages Harmony (Korsunsky et al. 2019) to correct batch effects among samples. The alpha parameter controls the resolution of the clustering. The lower the alpha value, the more spatial domains are detected.
            </div>




                    <div class="row justify-content-center text-center m-3">

                        <div class="w-100 w-md-80 w-lg-70  w-xxl-55" :class="processing ? 'disabled-clicks' : ''">

                            <div class="row justify-content-center text-center mt-4">
                                <div class="">
                                    <div class="me-3">
                                        Alpha: <span class="text-lg text-bold text-primary">{{ params.alpha }}</span> <show-modal tag="sdd_spagcn_perc_neigh_expr"></show-modal>
                                    </div>
                                    <input type="range" min="0.01" max="0.05" step="0.01" class="w-100" v-model="params.alpha">
                                </div>
                            </div>

                            <div class="row justify-content-center text-center mt-4">
                                <div class="">
                                    <div class="me-3">
                                        PCs to use: <span class="text-lg text-bold text-primary">{{ params.max_pc }}</span> <show-modal tag="sdd_spagcn_perc_neigh_expr"></show-modal>
                                    </div>
                                    <input type="range" min="2" max="50" step="1" class="w-100" v-model="params.max_pc">
                                </div>
                            </div>



                            <!-- <div class="row justify-content-center text-center m-4">
                                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                    <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                                    <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
                                </div>
                            </div> -->

                        </div>

                        <div class="p-3 text-center my-4">
                            <send-job-button label="Run MILWRM" :disabled="processing" :project-id="project.id" job-name="MILWRM" @started="MILWRM_start" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                        </div>


                        <div v-if="!processing && !renaming && ('milwrm' in project.project_parameters)">

                            <ul class="nav nav-tabs" id="MILWRM_myTab" role="tablist">
                                <template v-for="(sample, index) in samples">
                                    <li v-if="showSample(sample.name)" class="nav-item" role="presentation">
                                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + '_MILWRM-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + '_MILWRM'" type="button" role="tab" :aria-controls="sample.name + '_MILWRM'" aria-selected="true">{{ sample.name }}</button>
                                    </li>
                                </template>
                            </ul>

                            <div class="tab-content" id="MILWRM_myTabContent">
                                <template v-for="(sample, index) in samples">
                                    <div v-if="showSample(sample.name)" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + '_MILWRM'" role="tabpanel" :aria-labelledby="sample.name + '_MILWRM-tab'">
                                        <div v-for="image in milwrm.plots">
                                            <template v-if="image.includes('spagcn') && image.includes(sample.name) && (image.endsWith('k' + k) || image.endsWith('k' + k + '_refined'))">
                                                <h4 class="text-center" v-if="image.includes('refined')">Refined clusters</h4>
                                                <show-plot :src="image" :show-image="Boolean(sample.has_image)" :sample="sample" :side-by-side="true"></show-plot>
                                                <stdiff-rename-annotations-clusters v-if="annotations !== null" :annotation="annotations[sample.name][getAnnotation(sample.name, image)]" :sample-name="sample.name" :file-path="image" prefix="spagcn_" suffix="_top_deg" @changes="annotationChanges"></stdiff-rename-annotations-clusters>
                                            </template>
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
        name: 'sddSpagcn',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sddMilwrmUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                milwrm: ('milwrm' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.milwrm) : {},

                processing: false,

                textOutput: '',

                params: {
                    alpha: 0.02,
                    max_pc: 12,
                    samples: [] //this.samples.map(sample => sample.name)
                },


                filter_variable: '',

                plots_visible: [],

                renaming: false


            }
        },

        async mounted() {
            // await this.loadAnnotations();
            // await this.loadSVG();
            //console.log(this.project.project_parameters.annotation_variables);
        },

        watch: {

            'params.alpha'(newValue) {
                if(this.params.alpha > 0.05)
                    this.params.alpha = 0.05;
                else if(this.params.alpha < 0.01)
                    this.params.alpha = 0.01;
            },

            'params.max_pc'(newValue) {
                if(this.params.max_pc > 50)
                    this.params.max_pc = 50;
                else if(this.params.max_pc < 2)
                    this.params.max_pc = 2;
            },

            /*spagcn: {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.spagcn)) {
                        this.plots_visible[gene] = [];
                        for (const [index, sample] of Object.entries(samples)) {
                            this.plots_visible[gene][index] = true;
                        }
                    }
                },
                immediate: true,
            }*/
        },

        methods: {

            async loadAnnotations() {
                this.annotations =  await this.$getProjectSTdiffAnnotationsBySample(this.project.id, 'spagcn');
                console.log(this.annotations);
            },

            getAnnotation(sampleName, reference) {

                let items = this.annotations[sampleName];

                for(let key in items) {
                    if(reference.includes(key)) {

                        let alreadyAdded = this.active_annotations.filter(item => item.sampleName === sampleName && item.annotation === key);
                        if(!alreadyAdded.length) {
                            this.active_annotations.push({sampleName: sampleName, annotation: key, changed: false});
                        }

                        return key;
                    }
                }

                return null;
            },

            annotationChanges(sampleName, annotation, changed) {

                this.annotations[sampleName][annotation.originalName]['newName'] = annotation.newName;
                this.annotations[sampleName][annotation.originalName]['changed'] = changed;

                // this.annotationChangesDetected = false;
                this.active_annotations.forEach(aa => {
                    if(aa.sampleName === sampleName && aa.annotation === annotation.originalName) {
                        aa.changed = changed
                    };
                });

                this.annotations_renamed = this.active_annotations.some(aa => aa.changed);
            },

            showSample(sampleName) {
                for(let plot in this.milwrm.plots) {
                    if(this.milwrm.plots[plot].includes(sampleName)) {
                        return true;
                    }
                }

                return false;
            },

            MILWRM_start() {
                this.processing = true;

                axios.post(this.sddMilwrmUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            async processCompleted() {
                //console.log(this.project.project_parameters);
                this.spagcn = ('spagcn' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn) : {};
                await this.loadAnnotations();
                this.processing = false;
                this.$enableWizardStep('differential-expression');
                this.$enableWizardStep('spatial-gradients');
            },


            SpaGCNRename() {

                this.renaming = true;

                let modified = [];
                this.active_annotations.map(item => {
                    if(item.changed) {
                        modified.push({
                            sampleName: item.sampleName,
                            originalName: item.annotation,
                            newName: this.annotations[item.sampleName][item.annotation]['newName'],
                            clusters: this.annotations[item.sampleName][item.annotation]['clusters']
                        });
                    }
                });

                let parameters = {
                    annotations: modified,
                };

                axios.post(this.sddSpagcnRenameUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.renaming = false;
                        console.log(error);
                    })

            },

            async SpaGCNRenameCompleted() {
                await this.loadAnnotations();
                this.renaming = false;
            },


            generatePlots() {
                this.processing = true;
                console.log(this.stplotExpressionSurfacePlotsUrl);
                axios.post(this.stplotExpressionSurfacePlotsUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.response);
                    })
            },

            SDD_SpaGCN_SVG() {
                this.processing_svg = true;

                let parameters = {
                    annotation_to_test: this.params_svg.annotation_to_test
                };

                axios.post(this.sddSpagcnSvgUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing_svg = false;
                        console.log(error.message);
                    })
            },

            processCompleted_SVG() {
                this.spagcn_svg = ('spagcn_svg' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn_svg) : {};
                this.processing_svg = false;
                this.loadSVG();
            },

            async loadSVG() {

                if(!('spagcn_svg' in this.project.project_parameters)) return;

                this.svg_data = {};

                Object.keys(this.spagcn_svg.json_files).forEach(sample => {
                    this.svg_data[sample] = [];
                    this.spagcn_svg.json_files[sample].forEach( file => {
                        axios.get(file + '?' + Date.now())
                            .then((response) => {
                                this.svg_data[sample].push(response.data);
                                //console.log(response.data);
                            })
                            .catch((error) => {
                                console.log(error.message);
                            })
                    });
                });

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
