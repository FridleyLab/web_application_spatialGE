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

                    <color-palettes></color-palettes>

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
                                        :title="sample.name"
                                        plot-type="cluster"
                                        :color-palette="plot_data[sample.name]['result']['palette']"
                                        :legend-min="0"
                                        :legend-max="10"
                                        :is-y-axis-inverted="project.project_platform_id === 3"
                                        :is-grouped="true"
                                        :p-key="sample.name + '_milwrm_plot'"
                                    ></plots-component>
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


            }
        },

        async mounted() {
            if(!('plot_data' in this.milwrm)) {
                this.loaded = true;
                return;
            }

            for(let sample in this.milwrm.plot_data) {
                let data = await axios.get(this.milwrm.plot_data[sample]);
                this.processPlotFile(sample, data.data);
            }

            this.loaded = true;
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

            getColorPalette(csvData) {

                const colors = ['#001260', '#001966', '#01226B', '#012A70', '#013275', '#013A7A', '#01417F', '#024A85', '#04528A', '#075C90', '#126697', '#1D6E9D', '#2979A4', '#3884AB', '#498DB1', '#5999B9', '#69A4C0', '#7AAEC7', '#8AB8CF', '#9CC2D5', '#ACCCDD', '#BCD6E3', '#CCDFE9', '#DBE8EC', '#E5EBEB', '#ECEBE4', '#ECE6D9', '#E9DFCB', '#E4D7BD', '#DECDAD', '#D7C49E', '#D1BC8F', '#CCB280', '#C5AB72', '#BFA165', '#B89856', '#B28F47', '#AC8639', '#A67D2B', '#9F721E', '#986711', '#915E06', '#8B5201', '#854A00', '#7F4000', '#783700', '#722E00', '#6C2500', '#661B00', '#601200'];

                // Split the CSV string into rows
                const rows = csvData.split('\n');
                // Extract the third column [cluster]
                const column3Values = rows.slice(1).map(row => row.split(',')[2]);
                // Get unique values using a Set
                const uniqueValues = [...new Set(column3Values)];

                let step = 1;
                if(uniqueValues.length <= colors.length/2) {
                    step = Math.trunc(colors.length / uniqueValues.length);
                }

                console.log(step);
                console.log(uniqueValues);

                let colorPalette = {};
                for(let i = 0; i < uniqueValues.length; i++) {
                    colorPalette[uniqueValues[i]] = {label: uniqueValues[i], color: colors[i*step]};
                }

                return colorPalette;
            },

            processPlotFile(sampleName, data) {
                this.plot_data[sampleName] = {};
                const columnNames = data.split('\n')[0].split(',');
                for(let i = 2; i < columnNames.length; i++) {
                    this.plot_data[sampleName]['result'] = {};
                    this.plot_data[sampleName]['result']['data'] = this.extractColumnsFromCSV(data, [1, 2, i+1]);
                    this.plot_data[sampleName]['result']['palette'] = this.getColorPalette(this.plot_data[sampleName]['result']['data']);

                    //console.log(this.plot_data[sampleName][columnNames[i]]);
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
                this.milwrm = ('milwrm' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.milwrm) : {};
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
