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

                    <color-palettes @colors="changeColorPalette"></color-palettes>

                </div>

                <div class="p-3 text-center my-4">
                    <send-job-button label="Run MILWRM" :disabled="processing" :project-id="project.id" job-name="MILWRM" @started="MILWRM_start" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                </div>


                <div v-if="loaded && !processing && ('milwrm' in project.project_parameters)">

                    <ul class="nav nav-tabs" id="MILWRM_myTab" role="tablist">
                        <template v-for="(sample, index) in samples">
                            <li v-if="showSample(sample.name)" class="nav-item" role="presentation">
                                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + '_MILWRM-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + '_MILWRM'" type="button" role="tab" :aria-controls="sample.name + '_MILWRM'" aria-selected="true">{{ sample.name }}</button>
                            </li>
                        </template>
                    </ul>

                    <div class="tab-content m-4" id="MILWRM_myTabContent">
                        <template v-for="(sample, index) in samples">
                            <div v-if="showSample(sample.name)" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + '_MILWRM'" role="tabpanel" :aria-labelledby="sample.name + '_MILWRM-tab'">

                                <div class="my-4" style="width: 100%; height: 700px">
                                    <plots-component
                                        :base="sample.image_file_url"
                                        :csv="plot_data[sample.name]['result'].data"
                                        :title=" sample.name + ' - ' + plot_data[sample.name]['result']['title']"
                                        plot-type="cluster"
                                        :color-palette="plot_data[sample.name]['result']['palette']"
                                        :legend-min="0"
                                        :legend-max="10"
                                        :is-y-axis-inverted="project.project_platform_id === 3"
                                        :is-grouped="true"
                                        :p-key="sample.name + '_milwrm_plot'"
                                    ></plots-component>
                                </div>
                                <!-- <stdiff-rename-annotations-clusters :annotation="annotations[sample.name]['result']" :sample-name="sample.name" :file-path="milwrm.base_path + sample.name + '_' + annotation" prefix="stclust_" suffix="_top_deg" @changes="annotationChanges"></stdiff-rename-annotations-clusters> -->

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

// import Multiselect from '@vueform/multiselect';

    export default {
        name: 'sddSpagcn',

        components: {
            // Multiselect,
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

                renaming: false,

                plot_data: {},
                loaded: false,


                colorPalette: [],


            }
        },

        async mounted() {
            await this.loadResults();
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

        },

        methods: {

            async loadResults() {
                this.loaded = false;
                if(!('plot_data' in this.milwrm)) {
                    this.loaded = true;
                    return;
                }

                for(let sample in this.milwrm.plot_data) {
                    let data = await axios.get(this.milwrm.plot_data[sample]);
                    this.processPlotFile(sample, data.data);
                }

                this.getColorPalette();

                this.loaded = true;
            },

            changeColorPalette(colors) {

                if(!this.loaded) return;

                this.colorPalette = colors;

                this.getColorPalette();

                // for(let sampleName in this.milwrm.plot_data) {
                //     this.plot_data[sampleName]['result']['palette'] = this.getColorPalette(sampleName);
                // }
            },

            getColorPalette() {

                const colors = this.colorPalette.length ? this.colorPalette : ['#E8ECFB', '#E0DEF2', '#D8D0EA', '#D0C0E0', '#C7AFD5', '#BD9ECB', '#B48EC1', '#AB7EB8', '#A26FAE', '#9A60A6', '#8F539C', '#804D99', '#6D4D9C', '#6355A5', '#5B5FAF', '#5469B9', '#4F75C2', '#4D80C5', '#4D8BC4', '#4D93BE', '#5099B7', '#549FB1', '#58A3AA', '#5CA7A3', '#61AB9B', '#67B092', '#70B486', '#7AB779', '#88BB6B', '#99BD5D', '#AABD51', '#BBBC49', '#C8B844', '#D3B23F', '#DBAB3C', '#E1A23A', '#E49838', '#E68D35', '#E68033', '#E57330', '#E4642D', '#E05229', '#DD3D26', '#DA2322', '#C4221F', '#AD211D', '#95211B', '#7E1F18', '#671C15', '#521A13'];

                let labelsBySample = {};
                let allLabels = [];
                for(let sampleName in this.plot_data) {
                    // Split the CSV string into rows
                    const rows = this.plot_data[sampleName]['result']['data'].split('\n');
                    // Extract the third column [cluster]
                    const labels = rows.slice(1).map(row => row.split(',')[2]);
                    allLabels.push(...labels);
                    labelsBySample[sampleName] = [...new Set(labels)];
                }
                // Get unique values using a Set
                const uniqueValues = [...new Set(allLabels)];

                let step = 1;
                if(uniqueValues.length <= colors.length/2) {
                    step = Math.trunc(colors.length / uniqueValues.length);
                }

                let colorPalette = {};
                for(let i = 0; i < uniqueValues.length; i++) {
                    colorPalette[uniqueValues[i]] = {label: uniqueValues[i], color: colors[i*step]};
                }

                for(let sampleName in this.plot_data) {
                    let samplePalette = {};
                    for(let i = 0; i < labelsBySample[sampleName].length; i++) {
                        let label = labelsBySample[sampleName][i];
                        samplePalette[label] = colorPalette[label];
                    }
                    this.plot_data[sampleName]['result']['palette'] = samplePalette;

                }
            },

            processPlotFile(sampleName, data) {
                this.plot_data[sampleName] = {};
                const columnNames = data.split('\n')[0].split(',');
                for(let i = 2; i < columnNames.length; i++) {
                    this.plot_data[sampleName]['result'] = {};
                    this.plot_data[sampleName]['result']['data'] = this.extractColumnsFromCSV(data, [1, 2, i+1]);
                    this.plot_data[sampleName]['result']['title'] = columnNames[i];
                }
            },

            extractColumnsFromCSV(csv, columns) {
                const rows = csv.trim().split('\n');
                const extractedRows = rows.map(row => {
                    const cells = row.split(',');
                    const extractedCells = columns.map(index => cells[index - 1]);
                    return extractedCells.join(',');
                });
                return extractedRows.join('\n');
            },

            // processPlotFile(sampleName, data) {
            //     this.plot_data[sampleName] = {};
            //     this.plot_data[sampleName]['data'] = data;

            //     // Split the CSV data into lines
            //     const lines = data.trim().split('\n');
            //     // Skip the header line
            //     const dataLines = lines.slice(1);

            //     // Set to store unique values from the third column
            //     const uniqueClusters = new Set();

            //     // Extract the third column and find the maximum value
            //     const clusterCount = dataLines.reduce((max, line) => {
            //         const columns = line.split(',');
            //         const score = parseInt(columns[2]); // Get the value from the third column
            //         uniqueClusters.add(score); // Add score to the set of unique values
            //         return Math.max(max, score);
            //     }, Number.MIN_SAFE_INTEGER);

            //     console.log(clusterCount);
            //     const uniqueClustersArray = Array.from(uniqueClusters);
            //     console.log(uniqueClustersArray);

            //     this.plot_data[sampleName]['palette'] = this.getColorPalette(uniqueClustersArray);

            // },



            showSample(sampleName) {
                return ('plot_data' in this.milwrm) && (sampleName in this.milwrm.plot_data);
            },

            MILWRM_start() {
                this.processing = true;
                this.loaded = fals;

                axios.post(this.sddMilwrmUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            async processCompleted() {
                this.milwrm = ('milwrm' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.milwrm) : {};
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
