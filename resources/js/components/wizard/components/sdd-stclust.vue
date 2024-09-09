<template>
<div class="m-4">
    <form>

        <div :class="processing || renaming ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Spatial Domain Detection with STclust
            </div>
            <div>
                The algorithm STclust detects spatial domains via hierarchical clustering of gene expression weighted by spot-to-spot distances. Users have the option of specifying a number of clusters (k) or use automatic detection using DynamicTreeCuts. The number of clusters inferred by DynamicTreeCuts can be controlled via DeepSplit.
            </div>





            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70  w-xxl-55">

                    <div class="row justify-content-center text-center mt-4">
                        <div class="">
                            <div class="me-3">
                                Spatial weight: <span class="text-lg text-bold text-primary">{{ params.ws }}</span> <show-modal tag="sdd_stclust_spatial_weight"></show-modal>
                            </div>
                            <input type="range" min="0" max="0.1" step="0.01" class="w-100" v-model="params.ws">
                        </div>
                    </div>

                    <div class="form-check mt-4">
<!--                        <input class="form-check-input" type="checkbox" v-model="dynamicTreeCuts" id="flexCheckDefault">-->
<!--                        <label class="form-check-label text-lg" for="flexCheckDefault">-->
<!--                            DynamicTreeCuts - using {{dynamicTreeCuts ? 'deep split' : 'number of domains'}}-->
<!--                        </label>-->
                        <label class="text-lg">
                            <input type="radio" name="method" value="ds" v-model="method"> Select a range of Ks <show-modal tag="sdd_stclust_range_of_ks"></show-modal>
                        </label>

                        <label class="text-lg ms-5">
                            <input type="radio" name="method" value="dtc" v-model="method"> Use DynamicTreeCuts <show-modal tag="sdd_stclust_dynamicTreeCuts"></show-modal>
                        </label>
                    </div>

<!--                    <div :class="method === 'dtc' ? '' : 'disabled-clicks'" class="row justify-content-center text-center mt-4">-->
                    <div v-if="method === 'dtc'" class="row justify-content-center text-center mt-4">
                        <div class="">
                            <div class="me-3">
                                DeepSplit: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.deepSplit"> <show-modal tag="sdd_stclust_deepsplit"></show-modal>
                                <!--                                DeepSplit: <span class="text-lg text-bold text-primary">{{ params.deepSplit }}</span>-->
                            </div>
                            <input type="range" min="0" max="4" step="0.5" class="w-100" v-model="params.deepSplit">
                        </div>
                    </div>

<!--                    <div :class="method === 'ds' ? '' : 'disabled-clicks'" class="mt-4">-->
                    <div v-if="method === 'ds'" class="mt-4">
                        <numeric-range title="Number of domains:" show-tool-tip="sdd_stclust_number_of_domains" title-class="" :min="2" :max="30" :step="1" :default-max="5" @updated="(min,max) => {params.number_of_domains_min = min; params.number_of_domains_max = max}"></numeric-range>
                    </div>



                    <div class="row justify-content-center text-center mt-5">
                        <div class="">
                            <div class="me-3">Number of most variable genes to use: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"> <show-modal tag="sdd_stclust_number_genes"></show-modal></div>
                            <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="500" class="w-100" v-model="params.n_genes">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button label="Run STclust" :disabled="processing || annotations_renamed || renaming" :project-id="project.id" job-name="STclust" @started="SDD_STclust" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
        </div>


        <div v-if="!processing && annotations_renamed">
            <div class="row mt-3">
                <div class="p-3 text-end">
                    <send-job-button label="Complete renaming" :disabled="processing || renaming || !params.col_pal.length" :project-id="project.id" job-name="STclustRename" @started="STclustRename" @completed="STclustRenameCompleted" :project="project" ></send-job-button>
                </div>
            </div>
        </div>


        <!-- Create tabs for each sample-->
        <div v-if="!processing && !renaming && ('stclust' in project.project_parameters) && stclust.parameters.ks.includes('dtc') && annotations !== null">
            <div>
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <template v-for="(sample, index) in samples">
                        <li v-if="showSample(sample.name)" class="nav-item" role="presentation">
                            <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name" type="button" role="tab" :aria-controls="sample.name" aria-selected="true">{{ sample.name }}</button>
                        </li>
                    </template>
                </ul>

                <div class="tab-content" id="myTabContent">
                    <template v-for="(sample, index) in samples">
                        <div v-if="showSample(sample.name)" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name" role="tabpanel" :aria-labelledby="sample.name + '-tab'">
                            <div v-for="image in stclust.plots">
                                <template v-if="image.includes(sample.name)">

                                    <show-plot :src="image" :show-image="Boolean(sample.has_image)" :sample="sample" :side-by-side="false"></show-plot>

                                    <stdiff-rename-annotations-clusters :annotation="annotations[sample.name][getAnnotation(sample.name, image)]" :sample-name="sample.name" :file-path="image" prefix="stclust_" suffix="_top_deg" @changes="annotationChanges"></stdiff-rename-annotations-clusters>

                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>


        <!-- Create tabs for each K value and sub-tabs for each sample -->
        <div v-if="!processing && !renaming && ('stclust' in project.project_parameters) && !stclust.parameters.ks.includes('dtc') && annotations !== null">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <template v-for="index in parseInt(stclust.parameters.number_of_domains_max)">
                    <template v-if="index >= parseInt(stclust.parameters.number_of_domains_min)">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" :class="index === parseInt(stclust.parameters.number_of_domains_min) ? 'active' : ''" :id="'K_' + index + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + 'K_' + index" type="button" role="tab" :aria-controls="'K_' + index" aria-selected="true">{{ 'K=' + index }}</button>
                        </li>
                    </template>
                </template>
            </ul>

            <div class="tab-content m-4" id="myTabContent">

                <div v-for="k in parseInt(stclust.parameters.number_of_domains_max)" class="tab-pane fade min-vh-50" :class="k === parseInt(stclust.parameters.number_of_domains_min) ? 'show active' : ''" :id="'K_' + k" role="tabpanel" :aria-labelledby="'K_' + k + '-tab'">

                    <ul class="nav nav-tabs" :id="'myTab' + k" role="tablist">
                        <template v-for="(sample, index) in samples">
                            <li v-if="showSample(sample.name)" class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'K_' + k + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'K_' + k" type="button" role="tab" :aria-controls="sample.name + 'K_' + k" aria-selected="true">{{ sample.name }}</button>
                            </li>
                        </template>
                    </ul>

                    <div class="tab-content" :id="'myTabContent' + k">
                        <template v-for="(sample, index) in samples">
                            <div v-if="showSample(sample.name)" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'K_' + k" role="tabpanel" :aria-labelledby="sample.name + 'K_' + k + '-tab'">
                                <div v-for="image in stclust.plots">
                                    <template v-if="image.includes('stclust') && image.includes(sample.name) && image.endsWith('k' + k)">
                                        <show-plot v-if="!('plot_data' in stclust)" :src="image" :show-image="Boolean(sample.has_image)" :sample="sample" :side-by-side="false"></show-plot>

                                        <template v-if="'plot_data' in stclust">
                                            <template v-for="(plotData, annotation) in plot_data[sample.name]">
                                                <template v-if="image.includes(sample.name + '_' + annotation)">
                                                    <div class="my-4" style="width: 100%; height: 700px">
                                                        <plots-component
                                                            :base="sample.image_file_url"
                                                            :csv="plotData.data"
                                                            :title="annotation"
                                                            plot-type="cluster"
                                                            :color-palette="plot_data[sample.name][annotation]['palette']"
                                                            :legend-min="0"
                                                            :legend-max="10"
                                                            :is-y-axis-inverted="project.project_platform_id === 3"
                                                            :is-grouped="true"
                                                            :p-key="sample.name + 'K_' + k + '_' + annotation.replace(' ', '').replace('.','')"
                                                        ></plots-component>
                                                    </div>
                                                </template>
                                            </template>
                                        </template>

                                        <stdiff-rename-annotations-clusters :annotation="annotations[sample.name][getAnnotation(sample.name, image)]" :sample-name="sample.name" :file-path="image" prefix="stclust_" suffix="_top_deg" @changes="annotationChanges"></stdiff-rename-annotations-clusters>
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
        name: 'sddStclust',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sddStclustUrl: String,
            sddStclustRenameUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                stclust: ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {},

                processing: false,
                renaming: false,

                textOutput: '',

                method: 'ds',
                dynamicTreeCuts: false,
                params: {
                    ws: 0.02,
                    number_of_domains_min: 2,
                    number_of_domains_max: 5,
                    deepSplit: 0,
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 3000) ? 3000 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,

                    genes: [],
                    col_pal: 'sunset',
                    data_type: 'tr'
                },

                filter_variable: '',

                plots_visible: [],

                annotations: null,
                active_annotations: [],
                annotations_renamed: false,

                loaded: false,
                plot_data: {},
                plot_show: {},


                // colorPalette: {'1': {'color': 'red', 'label': 'cluster 1'}, '2': {'color': 'blue', 'label': 'cluster 2'}, '3': {'color': 'orange', 'label': 'cluster 3'}, '4': {'color': 'yellow', 'label': 'cluster 4'}, '5': {'color': 'cyan', 'label': 'cluster 5'}},



            }
        },

        async mounted() {
            await this.loadAnnotations();
            //console.log(this.annotations);

            if(!('plot_data' in this.stclust)) {
                this.loaded = true;
                return;
            }

            for(let sample in this.stclust.plot_data) {
                let data = await axios.get(this.stclust.plot_data[sample]);
                this.processPlotFile(sample, data.data);
            }

            //console.log(this.plot_data);

            this.loaded = true;
        },

        watch: {

            'params.deepSplit'(newValue) {
                if(this.params.deepSplit > 4)
                    this.params.deepSplit = 4;
                else if(this.params.deepSplit < 0)
                    this.params.deepSplit = 0;
            },

            'params.ws'(newValue) {
                if(this.params.ws > 0.1)
                    this.params.ws = 0.1;
                else if(this.params.ws < 0)
                    this.params.ws = 0;
            },

            /*annotations: {
                handler(newValue, oldValue) {

                    this.rename_annotations = [];

                    if(!this.active_annotations.length) return;

                    this.active_annotations.forEach(item => {

                        let annotation = this.annotations[item.sampleName][item.annotation];

                        if(annotation.changed || annotation.clusters.some(cluster => cluster.changed)) {
                            this.rename_annotations.push({sample: item.sampleName, annotation: item.annotation, clusters: annotation.clusters});
                        }

                    });

                    console.log(this.rename_annotations);


                },
                deep: true
            }*/

            /*stclust: {
                handler: function(value) {
                    for (const [gene, samples] of Object.entries(this.stclust)) {
                        this.plots_visible[gene] = [];
                        for (const [index, sample] of Object.entries(samples)) {
                            this.plots_visible[gene][index] = true;
                        }
                    }
                },
                immediate: true,
            }*/
        },

        // computed: {
        //     annotationChangesDetected() {

        //         if(this.annotations === null) return false;

        //         return Object.keys(this.annotations).some(key => this.annotations[key].changed);

        //         // const filteredData = Object.keys(this.annotations)
        //         //     .filter(key => this.annotations[key].changed)
        //         //     .reduce((result, key) => {
        //         //         result[key] = this.annotations[key];
        //         //         return result;
        //         //     }, {});

        //         console.log(filteredData);


        //         if(this.annotations) {
        //             return this.annotations.some(item => item.changed);
        //         }
        //         return false;
        //     },
        // },

        methods: {

            getColorPalette(sampleName, annotation) {
                // console.log(sampleName, annotation);
                // console.log(this.annotations[sampleName][annotation]);

                const colors = ['red', 'green', 'blue', 'yellow', 'cyan'];

                let annotations = this.annotations[sampleName][annotation];
                let colorPalette = {};
                let i = 0;
                for(let clusterName in annotations.clusters) {
                    let cluster = annotations.clusters[clusterName];
                    colorPalette[cluster.originalName] = {label: ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName, color: colors[i]};
                    i++;
                }

                return colorPalette;
            },

            processPlotFile(sampleName, data) {
                this.plot_data[sampleName] = {};
                const columnNames = data.split('\n')[0].split(',');
                for(let i = 2; i < columnNames.length; i++) {
                    this.plot_data[sampleName][columnNames[i]] = {};
                    this.plot_data[sampleName][columnNames[i]]['data'] = this.extractColumnsFromCSV(data, [1, 2, i+1]);
                    this.plot_data[sampleName][columnNames[i]]['palette'] = this.getColorPalette(sampleName, columnNames[i]);

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
                this.annotations =  await this.$getProjectSTdiffAnnotationsBySample(this.project.id, 'stclust');
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

                this.plot_data[sampleName][annotation.originalName]['palette'] = this.getColorPalette(sampleName, annotation.originalName)
            },



            showSample(sampleName) {
                for(let plot in this.stclust.plots) {
                    if(this.stclust.plots[plot].includes(sampleName)) {
                        return true;
                    }
                }

                return false;
            },

            SDD_STclust() {
                this.processing = true;

                let parameters = {
                    ws: this.params.ws > 0 ? 'c(0,' + this.params.ws + ')' : '0',
                    ks: this.method === 'ds' ? 'c(' + this.params.number_of_domains_min + ':' + this.params.number_of_domains_max + ')' : "'dtc'",
                    topgenes: this.params.n_genes,
                    deepSplit: (this.method !== 'dtc' || this.params.deepSplit === 0) ? 'F' : this.params.deepSplit,
                    number_of_domains_min: this.params.number_of_domains_min,
                    number_of_domains_max: this.params.number_of_domains_max,
                    ws_value: this.params.ws
                };

                axios.post(this.sddStclustUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            async processCompleted() {
                //console.log(this.project.project_parameters);
                this.stclust = ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
                await this.loadAnnotations();
                this.processing = false;
                this.$enableWizardStep('differential-expression');
                this.$enableWizardStep('spatial-gradients');
            },

            STclustRename() {

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

                console.log(parameters);

                axios.post(this.sddStclustRenameUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.renaming = false;
                        console.log(error);
                    })

            },

            async STclustRenameCompleted() {
                //this.stclust = ('stclust' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
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

            hide_plot: function(gene, sample) {
                this.plots_visible[gene][sample] = false;
            },

            show_reset: function(gene) {
                for(let value in this.plots_visible[gene]) {
                    if (!this.plots_visible[gene][value]) return true;
                }
            },

            reset_plots: function(gene) {
                for(let value in this.plots_visible[gene]) {
                    this.plots_visible[gene][value] = true;
                }
            },

            searchGenes: async function(query) {

                const response = await fetch(
                    '/projects/' + this.project.id + '/search-genes?context=normalized&query=' + query
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
    /*--ms-dropdown-border-color: #3B82F6;*/
    --ms-tag-bg: #3B82F6;
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
