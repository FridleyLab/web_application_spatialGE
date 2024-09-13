<template>
<div class="m-4">
    <form>

        <div>
            <div class="d-flex my-3 text-bold">
                SpaGCN
                <!-- &nbsp;<show-vignette url="/documentation/vignettes/spatial_domain_detection_spagcn.pdf"></show-vignette> -->
            </div>
            <div>
                The domain detection method <a href="https://www.nature.com/articles/s41592-021-01255-8" class="text-info" target="_blank">SpaGCN</a> (Hu et al. 2021) implements a graph convolutional neural (GCN) network approach to integrate spatial gene expression with the accompanying spatial coordinates and optionally, tissue imaging. The GCNs are used to condense the information from the different data modalities, followed by Louvain clustering to clasify the spots or cells into tissue domains.
            </div>



            <ul class="nav nav-tabs mt-4" id="myTabsSpaGCN" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="domain-detection-tab" data-bs-toggle="tab" data-bs-target="#domain-detection" type="button" role="tab" aria-controls="domain-detection" aria-selected="true">Domain detection</button>
                </li>
                <li v-if="!processing && ('spagcn' in project.project_parameters)" class="nav-item" role="presentation">
                    <button class="nav-link" id="spatially-variable-genes-tab" data-bs-toggle="tab" data-bs-target="#spatially-variable-genes" type="button" role="tab" aria-controls="spatially-variable-genes" aria-selected="false">Spatially variable genes</button>
                </li>
            </ul>


            <div class="tab-content" id="myTabsSpaGCNContent">
                <div class="tab-pane fade show active min-vh-50" id="domain-detection" role="tabpanel" aria-labelledby="domain-detection-tab">
                    <div class="row justify-content-center text-center m-3">

                        <div class="w-100 w-md-80 w-lg-70  w-xxl-55" :class="processing ? 'disabled-clicks' : ''">

                            <div class="row justify-content-center text-center mt-4">
                                <div class="">
                                    <div class="me-3">
                                        Percentage of neighborhood expression: <span class="text-lg text-bold text-primary">{{ params.p }}</span> <show-modal tag="sdd_spagcn_perc_neigh_expr"></show-modal>
                                    </div>
                                    <input type="range" min="0.05" max="1" step="0.05" class="w-100" v-model="params.p">
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="me-3">Seed number (permutation): <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-20 w-xxl-10" v-model="params.user_seed"> <show-modal tag="sdd_spagcn_seed_number"></show-modal></div>

                                <div v-if="project.platform_name === 'VISIUM'" class="mt-3">
                                    <label class="me-3 text-md">
                                        <input type="checkbox" v-model="params.refine_clusters"> Refine clusters? <show-modal tag="sdd_spagcn_refine_clusters"></show-modal>
                                    </label>
                                </div>

                            </div>

                            <div class="mt-4">
                                <numeric-range title="Number of domains:" show-tool-tip="sdd_spagcn_number_of_domains" title-class="" :min="2" :max="30" :step="1" :default-max="5" @updated="(min,max) => {params.number_of_domains_min = min; params.number_of_domains_max = max}"></numeric-range>
                            </div>


                            <div class="row justify-content-center text-center m-4">
                                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                                    <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                                    <div><Multiselect :options="colorPalettes" v-model="params.col_pal"></Multiselect></div>
                                </div>
                            </div>

                        </div>

                        <div class="p-3 text-center my-4">
                            <send-job-button label="Run SpaGCN" :disabled="processing" :project-id="project.id" job-name="SpaGCN" @started="SDD_SpaGCN" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                        </div>

                        <color-palettes @colors="changeColorPalette"></color-palettes>


                        <div v-if="!processing && annotations_renamed">
                            <div class="row mt-3">
                                <div class="p-3 text-end">
                                    <send-job-button label="Complete renaming" :disabled="processing || renaming || !params.col_pal.length" :project-id="project.id" job-name="SpaGCNRename" @started="SpaGCNRename" @completed="SpaGCNRenameCompleted" :project="project" ></send-job-button>
                                </div>
                            </div>
                        </div>

                        <!-- Create tabs for each K value and sub-tabs for each sample -->
                        <div v-if="!processing && !renaming && ('spagcn' in project.project_parameters)">

                <!--            <div class="">-->
                <!--                <a :href="project.assets_url + 'SpaGCN.zip'" class="float-end btn btn-sm btn-outline-info" download>Download all results (ZIP)</a>-->
                <!--            </div>-->

                            <div>
                                <ul class="nav nav-tabs" id="SPAGCN_myTab" role="tablist">
                                    <template v-for="index in parseInt(spagcn.parameters.number_of_domains_max)">
                                        <template v-if="index >= parseInt(spagcn.parameters.number_of_domains_min)">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" :class="index === parseInt(spagcn.parameters.number_of_domains_min) ? 'active' : ''" :id="'SPAGCN_K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + 'SPAGCN_K_' + index" type="button" role="tab" :aria-controls="'SPAGCN_K_' + index" aria-selected="true">{{ 'K=' + index }}</button>
                                            </li>
                                        </template>
                                    </template>
                                    <!-- <a :href="project.assets_url + 'SpaGCN.zip'" class="ms-3 btn btn-sm btn-outline-info" download>Download all results (ZIP)</a> -->
                                </ul>

                                <div class="tab-content m-4" id="SPAGCN_myTabContent">

                                    <div v-for="k in parseInt(spagcn.parameters.number_of_domains_max)" class="tab-pane fade min-vh-50" :class="k === parseInt(spagcn.parameters.number_of_domains_min) ? 'show active' : ''" :id="'SPAGCN_K_' + k" role="tabpanel" :aria-labelledby="'SPAGCN_K_' + k + '-tab'">

                                        <ul class="nav nav-tabs" :id="'SPAGCN_myTab' + k" role="tablist">
                                            <template v-for="(sample, index) in samples">
                                                <li v-if="showSample(sample.name)" class="nav-item" role="presentation">
                                                    <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'SPAGCN_K_' + k + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'SPAGCN_K_' + k" type="button" role="tab" :aria-controls="sample.name + 'SPAGCN_K_' + k" aria-selected="true">{{ sample.name }}</button>
                                                </li>
                                            </template>
                                        </ul>

                                        <div class="tab-content" :id="'SPAGCN_myTabContent' + k">
                                            <template v-for="(sample, index) in samples">
                                                <div v-if="showSample(sample.name)" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'SPAGCN_K_' + k" role="tabpanel" :aria-labelledby="sample.name + 'SPAGCN_K_' + k + '-tab'">
                                                    <!-- <div v-for="image in spagcn.plots"> -->
                                                        <!-- <template v-if="image.includes('spagcn') && image.includes(sample.name) && (image.endsWith('k' + k) || image.endsWith('k' + k + '_refined'))"> -->
                                                            <!-- <h4 class="pt-4 text-center" v-if="image.includes('refined')">Refined clusters</h4> -->
                                                            <show-plot v-if="!('plot_data' in spagcn)" :src="image" :show-image="Boolean(sample.has_image)" :sample="sample" :side-by-side="true"></show-plot>

                                                            <template v-if="'plot_data' in spagcn">
                                                                <template v-for="(plotData, annotation) in plot_data[sample.name]">
                                                                    <template v-if="annotation.endsWith('k' + k) || annotation.endsWith('k' + k + '_refined')">
                                                                        <h4 v-if="annotation.includes('refined')" class="pt-4 text-center">Refined clusters</h4>
                                                                        <div class="my-4" style="width: 100%; height: 700px">
                                                                            <plots-component
                                                                                :base="sample.image_file_url"
                                                                                :csv="plotData.data"
                                                                                :title="plot_data[sample.name][annotation]['title']"
                                                                                plot-type="cluster"
                                                                                :color-palette="plot_data[sample.name][annotation]['palette']"
                                                                                :legend-min="0"
                                                                                :legend-max="10"
                                                                                :is-y-axis-inverted="project.project_platform_id === 3"
                                                                                :is-grouped="true"
                                                                                :p-key="sample.name.replaceAll(' ', '').replaceAll('.','') + 'K_' + k + '_' + annotation.replaceAll(' ', '').replaceAll('.','')"
                                                                            ></plots-component>
                                                                        </div>
                                                                        <stdiff-rename-annotations-clusters v-if="annotations !== null" :annotation="annotations[sample.name][annotation]" :sample-name="sample.name" :file-path="spagcn.base_path + sample.name + '_' + annotation" prefix="spagcn_" suffix="_top_deg" :rename-url="this.sddSpagcnRenameUrl" @changes="annotationChanges"></stdiff-rename-annotations-clusters>
                                                                    </template>
                                                                </template>
                                                            </template>
                                                        <!-- </template> -->
                                                    <!-- </div> -->
                                                </div>
                                            </template>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!processing && ('spagcn' in project.project_parameters)" class="tab-pane fade min-vh-50" id="spatially-variable-genes" role="tabpanel" aria-labelledby="spatially-variable-genes-tab">

                    <div class="row justify-content-center text-center mt-5" :class="processing_svg ? 'disabled-clicks' : ''">
                        <div class="w-100 w-md-90 w-lg-80 w-xxl-65">
                            <div>Annotation to test <show-modal tag="spagcn_spavargenes_annotation"></show-modal></div>
                            <div>
                                <span>
                                    <Multiselect id="multiselect_annotation_variables" :options="annotation_variables.filter(ann => ann.value.startsWith('spagcn'))" v-model="params_svg.annotation_to_test"></Multiselect>
                                </span>
                            </div>
                        </div>
                    </div>


                    <div class="p-3 text-center my-4">
                        <send-job-button label="SpaGCN - Spatially variable genes" :disabled="processing_svg" :project-id="project.id" job-name="SpaGCN_SVG" @started="SDD_SpaGCN_SVG" @ongoing="processing_svg = true" @completed="processCompleted_SVG" :project="project" ></send-job-button>
                    </div>



                    <!-- Create tabs for each K value and sub-tabs for each sample -->
                    <div v-if="!processing_svg && ('spagcn_svg' in project.project_parameters)">


                        <div class="text-justify">
                            <div class="fs-5">Explanation of results:</div>
                            <ul>
                                <li><strong>Gene:</strong> The name of the gene tested. If the gene name is clicked, the corresponding GeneCards record is opened.</li>
                                <li><strong>In-group fraction:</strong> The proportion of spots/cells within the tested domain that express the gene.</li>
                                <li><strong>Out-group fraction:</strong> The proportion of spots/cells outside the tested domain that express the gene.</li>
                                <li><strong>In/out-group ratio:</strong> The ratio between the proportion of spots/cells that express the gene within and outside the tested domain.</li>
                                <li><strong>In-group mean expr.:</strong> The average expression of the gene within the tested domain.</li>
                                <li><strong>Out-group mean expr.:</strong> The average expression of the gene outside the tested domain.</li>
                                <li><strong>Fold change:</strong> The difference in expression of the gene between the tested domain and the rest of the sample.</li>
                                <li><strong>Adjusted p-value:</strong> The False Discovery Rate (FDR) adjusted p-value</li>
                            </ul>
                        </div>


                        <div>
                            <ul class="nav nav-tabs" id="SPAGCN_SVG_MyTab" role="tablist">
                                <template v-for="(sample, indexSample) in samples">
                                    <li v-if="sample.name in svg_data" class="nav-item" role="presentation">
                                        <button class="nav-link" :class="indexSample === 0 ? 'active' : ''" :id="sample.name + 'SPAGCN_SVG-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'SPAGCN_SVG'" type="button" role="tab" :aria-controls="sample.name + 'SPAGCN_SVG'" aria-selected="true">{{ sample.name }}</button>
                                    </li>
                                </template>
                            </ul>

                            <div class="tab-content m-4" id="SPAGCN_SVG_MyTabContent">
                                <template v-for="(sample, indexSample) in samples">
                                    <div v-if="sample.name in svg_data" class="tab-pane fade min-vh-50" :class="indexSample === 0 ? 'show active' : ''" :id="sample.name + 'SPAGCN_SVG'" role="tabpanel" :aria-labelledby="sample.name + 'SPAGCN_SVG-tab'">

                                        <ul class="nav nav-tabs" :id="sample.name + 'SPAGCN_SVG_MyTab'" role="tablist">
                                            <template v-for="(i, index) in parseInt(spagcn_svg.k)">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'SPAGCN_SVG_K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'SPAGCN_SVG_K_' + index" type="button" role="tab" :aria-controls="sample.name + 'SPAGCN_SVG_K_' + index" aria-selected="true">Domain {{ index + 1 }}</button>
                                                </li>
                                            </template>
                                        </ul>

                                        <div class="tab-content" :id="sample.name + 'SPAGCN_SVG_MyTabContent'">
                                            <div v-for="(i, index) in parseInt(spagcn_svg.k)" class="tab-pane fade min-vh-50 mt-4" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'SPAGCN_SVG_K_' + index" role="tabpanel" :aria-labelledby="sample.name + 'SPAGCN_SVG_K_' + index + '-tab'">
                                                <data-grid v-if="sample.name in svg_data && svg_data[sample.name].length > index" :headers="svg_data[sample.name][index].headers" :data="svg_data[sample.name][index].items"></data-grid>
                                            </div>
                                        </div>

                                    </div>
                                </template>
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
        name: 'sddSpagcn',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sddSpagcnUrl: String,
            sddSpagcnSvgUrl: String,
            sddSpagcnRenameUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                spagcn: ('spagcn' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn) : {},
                spagcn_svg: ('spagcn_svg' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spagcn_svg) : {},

                processing: false,
                processing_svg: false,
                renaming: false,

                textOutput: '',

                dynamicTreeCuts: false,
                params: {
                    p: this.project.platform_name === 'COSMX' ? 0.7 : 0.5,
                    user_seed: 12345,
                    refine_clusters: this.project.platform_name === 'VISIUM',
                    number_of_domains_min: 2,
                    number_of_domains_max: 5,
                    col_pal: 'smoothrainbow'
                },

                params_svg: {
                    annotation_to_test: '',
                },

                filter_variable: '',

                plots_visible: [],

                svg_data: {},

                annotations: null,
                active_annotations: [],
                annotations_renamed: false,

                annotation_variables: [],

                loaded: false,
                plot_data: {},
                plot_show: {},

                colorPalette: [],
            }
        },

        async mounted() {

            await this.loadResults();

        },

        watch: {

            'params.p'(newValue) {
                if(this.params.p > 1)
                    this.params.p = 1;
                else if(this.params.p < 0.05)
                    this.params.p = 0.05;
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

            async loadResults() {
                this.loaded = false;
                await this.loadAnnotations();
                await this.loadSVG();

                let stdiff = await this.$getProjectSTdiffAnnotations(this.project.id);
                this.annotation_variables = stdiff['annotation_variables'];

                if(!('plot_data' in this.spagcn)) {
                    this.loaded = true;
                    return;
                }
                for(let sample in this.spagcn.plot_data) {
                    let data = await axios.get(this.spagcn.plot_data[sample]);
                    this.processPlotFile(sample, data.data);
                }
                this.loaded = true;
            },

            changeColorPalette(colors) {

                if(!this.loaded) return;

                this.colorPalette = colors;

                for(let sampleName in this.plot_data) {
                    for(let annotation in this.plot_data[sampleName]) {
                        this.plot_data[sampleName][annotation]['palette'] = this.getColorPalette(sampleName, annotation);
                    }
                }
            },

                getColorPalette(sampleName, annotation) {

                // const colors = ['red', 'green', 'blue', 'yellow', 'cyan'];

                const colors = this.colorPalette.length ? this.colorPalette : ['#E8ECFB', '#E0DEF2', '#D8D0EA', '#D0C0E0', '#C7AFD5', '#BD9ECB', '#B48EC1', '#AB7EB8', '#A26FAE', '#9A60A6', '#8F539C', '#804D99', '#6D4D9C', '#6355A5', '#5B5FAF', '#5469B9', '#4F75C2', '#4D80C5', '#4D8BC4', '#4D93BE', '#5099B7', '#549FB1', '#58A3AA', '#5CA7A3', '#61AB9B', '#67B092', '#70B486', '#7AB779', '#88BB6B', '#99BD5D', '#AABD51', '#BBBC49', '#C8B844', '#D3B23F', '#DBAB3C', '#E1A23A', '#E49838', '#E68D35', '#E68033', '#E57330', '#E4642D', '#E05229', '#DD3D26', '#DA2322', '#C4221F', '#AD211D', '#95211B', '#7E1F18', '#671C15', '#521A13'];

                //Get a list of all labels across all samples
                // let labels = [];
                // for(let sampleName in this.annotations) {
                //     for(let _annotation in this.annotations[sampleName]) {
                //         labels.push(...this.annotations[sampleName][_annotation].clusters.map(cluster => ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName));
                //     }
                // }

                let labels = this.annotations[sampleName][annotation].clusters.map(cluster => ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName);
                //Get unique labels list and their colors
                const uniqueLabels = [...new Set(labels)];
                let step = 1;
                if(uniqueLabels.length <= colors.length/2) {
                    step = Math.trunc(colors.length / uniqueLabels.length);
                }
                let labelColors = {};
                for(let i = 0; i < uniqueLabels.length; i++) {
                    labelColors[uniqueLabels[i]] = {label: uniqueLabels[i], color: colors[i*step]};
                }

                let colorPalette = {};
                let i = 0;
                let annotations = this.annotations[sampleName][annotation];
                for(let clusterName in annotations.clusters) {
                    let cluster = annotations.clusters[clusterName];
                    let label = ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName;
                    colorPalette[cluster.originalName] = labelColors[label];
                    i++;
                }

                return colorPalette;
            },

            // getColorPalette(sampleName, annotation) {
            //     // console.log(sampleName, annotation);
            //     // console.log(this.annotations[sampleName][annotation]);

            //     const colors = ['red', 'green', 'blue', 'yellow', 'cyan'];

            //     let annotations = this.annotations[sampleName][annotation];
            //     let colorPalette = {};
            //     let i = 0;
            //     for(let clusterName in annotations.clusters) {
            //         let cluster = annotations.clusters[clusterName];
            //         colorPalette[cluster.originalName] = {label: ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName, color: colors[i]};
            //         i++;
            //     }

            //     return colorPalette;
            // },

            processPlotFile(sampleName, data) {
                this.plot_data[sampleName] = {};
                const columnNames = data.split('\n')[0].split(',');
                for(let i = 2; i < columnNames.length; i++) {
                    this.plot_data[sampleName][columnNames[i]] = {};
                    this.plot_data[sampleName][columnNames[i]]['data'] = this.extractColumnsFromCSV(data, [1, 2, i+1]);
                    this.plot_data[sampleName][columnNames[i]]['palette'] = this.getColorPalette(sampleName, columnNames[i]);
                    this.plot_data[sampleName][columnNames[i]]['title'] = this.annotations[sampleName][columnNames[i]]['modifiedName']

                    //console.log(this.plot_data[sampleName][columnNames[i]]);
                }
            },

            extractColumnsFromCSV(csv, columns) {
                const rows = csv.split('\n');
                const extractedRows = rows.map(row => {
                    const cells = row.split(',');
                    const extractedCells = columns.map(index => cells[index - 1]);
                    return extractedCells.join(',');
                });
                return extractedRows.join('\n');
            },

            async loadAnnotations() {
                this.annotations =  await this.$getProjectSTdiffAnnotationsBySample(this.project.id, 'spagcn');
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

                this.plot_data[sampleName][annotation.originalName]['title'] = annotation.newName;

                // this.annotationChangesDetected = false;
                this.active_annotations.forEach(aa => {
                    if(aa.sampleName === sampleName && aa.annotation === annotation.originalName) {
                        aa.changed = changed
                    };
                });

                this.annotations_renamed = this.active_annotations.some(aa => aa.changed);

                this.plot_data[sampleName][annotation.originalName]['palette'] = this.getColorPalette(sampleName, annotation.originalName)
            },

            showSample(sampleName) {

                return sampleName in this.plot_data;

                for(let plot in this.spagcn.plots) {
                    if(this.spagcn.plots[plot].includes(sampleName)) {
                        return true;
                    }
                }

                return false;
            },

            SDD_SpaGCN() {
                this.processing = true;
                this.loaded = false;

                let parameters = {
                    p: this.params.p,
                    user_seed: this.params.user_seed,
                    number_of_domains_min: this.params.number_of_domains_min,
                    number_of_domains_max: this.params.number_of_domains_max,
                    refine_clusters: this.params.refine_clusters ? 'True' : 'False',
                    col_pal: this.params.col_pal,
                };

                axios.post(this.sddSpagcnUrl, parameters)
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
                // await this.loadAnnotations();
                this.loadResults();
                this.processing = false;
                this.$enableWizardStep('differential-expression');
                this.$enableWizardStep('spatial-gradients');
            },


            // SpaGCNRename() {

            //     this.renaming = true;

            //     let modified = [];
            //     this.active_annotations.map(item => {
            //         if(item.changed) {
            //             modified.push({
            //                 sampleName: item.sampleName,
            //                 originalName: item.annotation,
            //                 newName: this.annotations[item.sampleName][item.annotation]['newName'],
            //                 clusters: this.annotations[item.sampleName][item.annotation]['clusters']
            //             });
            //         }
            //     });

            //     let parameters = {
            //         annotations: modified,
            //     };

            //     axios.post(this.sddSpagcnRenameUrl, parameters)
            //         .then((response) => {
            //         })
            //         .catch((error) => {
            //             this.renaming = false;
            //             console.log(error);
            //         })

            // },

            // async SpaGCNRenameCompleted() {
            //     await this.loadAnnotations();
            //     this.renaming = false;
            // },


            // generatePlots() {
            //     this.processing = true;
            //     console.log(this.stplotExpressionSurfacePlotsUrl);
            //     axios.post(this.stplotExpressionSurfacePlotsUrl, this.params)
            //         .then((response) => {
            //         })
            //         .catch((error) => {
            //             this.processing = false;
            //             console.log(error.response);
            //         })
            // },

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
