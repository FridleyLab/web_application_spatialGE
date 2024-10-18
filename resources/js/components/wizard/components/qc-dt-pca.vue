<template>
<div class="m-4">
    <form>

        <div :class="generating_plots || generating_pca ? 'disabled-clicks' : ''">

            <div class="my-3 text-bold">
                Principal Component Analysis - PCA
            </div>
            <div>
                The following PCA plot has been created by calculating the average expression of genes within each sample. This PCA does not incorporate any spatial component of the data.
                The PCA is calculated with a number of user-selected most variable genes based on standard deviation. <span class="text-bold">Note:</span> Pseudobulk analysis can only be performed on three samples or more.
            </div>


            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3 mb-3">Number of most variable genes to calculate PCA: <input type="number" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15" v-model="params.n_genes"><show-modal tag="qcpca_number_variable_genes"></show-modal></div>
                    <input type="range" min="0" :max="project.project_parameters.pca_max_var_genes" step="10" class="w-100" v-model="params.n_genes">
                </div>
            </div>

        </div>

        <div class="p-3 text-center">
            <send-job-button v-if="samples.length > 3" label="Calculate PCA" :disabled="generating_pca || generating_plots" :project-id="project.id" job-name="applyPca" @started="applyPca" @ongoing="generating_pca = true" @completed="processCompleted" :project="project" ></send-job-button>
            <div v-else class="text-warning text-xl-center">
                A minimum of 4 samples are needed to Calculate PCA
            </div>
        </div>



        <template v-if="false && !generating_pca && !generating_plots && 'qc_pca' in project.project_parameters">
            <div class="mt-4 justify-content-center text-center">
                <label class="form-label">Number of genes to display on heatmap:</label>
                <input type="number" class="ms-1 text-end text-sm border border-1 rounded w-25 w-md-20 w-lg-15 w-xxl-10" v-model="params.hm_display_genes">
                <show-modal tag="qcpca_number_genes_display_heatmap"></show-modal>
            </div>


            <div class="row mt-5 row-cols-2">
                <div class="col">
                    <div>Color palette <show-modal tag="qcpca_color_palette"></show-modal></div>
                    <div><Multiselect :options="colorPalettes" v-model="params.color_pal" :close-on-select="true" :searchable="true"></Multiselect></div>
                </div>

                <div class="col">
                    <div>Color by <show-modal tag="qcpca_color_by"></show-modal></div>
                    <div><Multiselect :options="plot_meta_options" v-model="params.plot_meta"></Multiselect></div>
                </div>

            </div>

        </template>
        <div v-if="false && !generating_pca && 'qc_pca' in project.project_parameters" class="row mt-3">
            <div class="p-3 text-end">
                <send-job-button label="Generate plots" :disabled="generating_pca || !params.color_pal.length /*|| !params.plot_meta.length*/" :project-id="project.id" job-name="pcaPlots" @started="pcaPlots" @ongoing="generating_plots = true" @completed="processPlotsCompleted" :project="project" ></send-job-button>
            </div>
        </div>


        <div class="mt-4" v-if="!generating_pca && !generating_plots && (('qc_pca_plots' in project.project_parameters) || ('pseudo_bulk_pca' in project.project_parameters))">
            <color-palettes @colors="changeColorPalette"></color-palettes>
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pcaplot-tab" data-bs-toggle="tab" data-bs-target="#pcaplot" type="button" role="tab" aria-controls="pcaplot" aria-selected="true">PCA</button>
                </li>
                <li v-if="'qc_pca_plots' in project.project_parameters" class="nav-item" role="presentation">
                    <button class="nav-link" id="umap-tab" data-bs-toggle="tab" data-bs-target="#umap" type="button" role="tab" aria-controls="umap" aria-selected="true">UMAP</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="heatmap-tab" data-bs-toggle="tab" data-bs-target="#heatmap" type="button" role="tab" aria-controls="heatmap" aria-selected="true">Heatmap</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="pcaplot" role="tabpanel" aria-labelledby="pcaplot-tab">
                    <div class="m-4">
                        <p><strong>Pseudo-bulk samples in a principal component analysis (PCA) plot.</strong> This analysis is intended to help users detect samples with unexpected gene expression patterns.</p>
                    </div>

                    <show-plot v-if="'pseudo_bulk_pca' in project.project_parameters" :src="project.project_parameters.pseudo_bulk_pca"></show-plot>

                    <plot-holder v-if="'qc_pca_plots' in project.project_parameters" :key="pcaComponentKey"
                    :csv="plot_data.base_path + plot_data.pca"
                    expression="PCA"
                    title="PCA Plot"
                    plot-type="pca"
                    :palette="pcaColorPalette"
                    :inverted="false"
                    ></plot-holder>


                </div>

                <div v-if="'qc_pca_plots' in project.project_parameters" class="tab-pane fade" id="umap" role="tabpanel" aria-labelledby="umap-tab">
                    <div class="m-4">
                        <p><strong>Pseudo-bulk samples in a Uniform Manifold Approximation and Projection (UMAP) plot. </strong>This analysis is intended to help users detect samples with unexpected gene expression patterns.</p>
                    </div>

                    <plot-holder :key="umapComponentKey"
                    :csv="plot_data.base_path + plot_data.umap"
                    expression="PCA"
                    title="PCA Plot"
                    plot-type="pca"
                    :palette="pcaColorPalette"
                    :inverted="false"
                    ></plot-holder>


                </div>


                <div class="tab-pane fade" id="heatmap" role="tabpanel" aria-labelledby="heatmap-tab">
                    <div class="m-4">
                        <p><strong>Heatmap of expression of top variable genes (rows) across pseudo-bulk samples (columns).</strong> This plot is intended to help users detect samples with unexpected gene expression patterns.</p>
                    </div>

                    <show-plot v-if="'pseudo_bulk_heatmap' in project.project_parameters" :src="project.project_parameters.pseudo_bulk_heatmap"></show-plot>


                    <div v-if="loaded && ('qc_pca_plots' in project.project_parameters)">
                        <div>
                            Sort samples by:
                            <select v-model="metadataSortedBy">
                                <option v-for="meta in metadataNames" :value="meta">{{meta}}</option>
                            </select>
                        </div>
                        <div class="my-4">
                            Number of rows to show: <span class="text-primary text-lg text-bold">{{ numberOfRowsToShow }}</span>
                            <input type="range" min="0" max="200" step="10" class="w-100" v-model="numberOfRowsToShow">
                        </div>
                        <div>
                            <heatmap :key="heatmapComponentKey"
                                :color-palette="['blue', 'white', 'red']"
                                :csv-file="plot_data.base_path + plot_data.heatmap"
                                heatmap-title="Aggregated gene expression (pseudobulk)"
                                csv-header-gene-name="gene_name"
                                :visible-samples="[/*'sample_093d', 'sample_396c'*/]"
                                :samples-order="metadataSorted[metadataSortedBy]"
                                :metadata-palette="metadataPalette"
                                :metadata-values="metadataValues"
                                :number-of-rows-to-show="parseInt(numberOfRowsToShow)"
                                show-rows-from="top"
                                gradient-label="scaled mean expr"
                            >
                            </heatmap>
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
        name: 'qcDtPca',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            pcaUrl: String,
            pcaPlotsUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                textOutput: '',

                params: {
                    color_pal: 'Spectral',
                    plot_meta: '',
                    n_genes: (('pca_max_var_genes' in this.project.project_parameters) && this.project.project_parameters.pca_max_var_genes >= 3000) ? 3000 : ('pca_max_var_genes' in this.project.project_parameters) ? this.project.project_parameters.pca_max_var_genes/2 : 0,
                    hm_display_genes: 30
                },

                plot_meta_options: 'metadata_names' in this.project.project_parameters ? [{'label': 'select a metadata (optional)', 'value': ''} ,...this.project.project_parameters.metadata_names] : [{'label': 'select a metadata (optional)', 'value': ''}],

                generating_pca: false,
                generating_plots: false,

                colorPalette: [],

                plot_data: 'qc_pca_plots' in this.project.project_parameters ? JSON.parse(this.project.project_parameters.qc_pca_plots) : null,

                pcaColorPalette: {},


                metadataNames: [],
                metadataSorted: {},
                metadataPalette: {},
                metadataValues: {},
                metadataSortedBy: '',

                numberOfRowsToShow: 30,
                heatmapComponentKey: 0,
                umapComponentKey: 1,
                pcaComponentKey: 2,

                loaded: false,
            }
        },

        mounted() {
            // console.log(this.project.project_parameters);
            // console.log(JSON.parse(this.project.project_parameters.qc_pca_plots));

            this.pcaColorPalette = this.getColorPalette();

            this.processMetadata();
        },

        watch: {
            'params.n_genes'(newValue) {
                if(this.params.n_genes > this.project.project_parameters.pca_max_var_genes)
                    this.params.n_genes = this.project.project_parameters.pca_max_var_genes;
                if(this.params.n_genes < 2)
                    this.params.n_genes = 2;
            },

            'params.hm_display_genes'(newValue) {
                if(this.params.hm_display_genes > this.params.n_genes)
                    this.params.hm_display_genes = this.params.n_genes;
                if(this.params.hm_display_genes < 0)
                    this.params.hm_display_genes = 0;
            }
        },



        methods: {

            applyPca() {
                this.generating_pca = true;
                axios.post(this.pcaUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        //this.generating_pca = false;
                        console.log(error.message)
                    })
            },

            changeColorPalette(colors) {

                if(!this.loaded) return;

                this.colorPalette = colors;

                this.pcaColorPalette = this.getColorPalette();

                this.umapComponentKey++;
                this.heatmapComponentKey++;
                this.pcaComponentKey++;

            },

            getColorPalette() {

                const colors = this.colorPalette.length ? this.colorPalette : ['#E8ECFB', '#E0DEF2', '#D8D0EA', '#D0C0E0', '#C7AFD5', '#BD9ECB', '#B48EC1', '#AB7EB8', '#A26FAE', '#9A60A6', '#8F539C', '#804D99', '#6D4D9C', '#6355A5', '#5B5FAF', '#5469B9', '#4F75C2', '#4D80C5', '#4D8BC4', '#4D93BE', '#5099B7', '#549FB1', '#58A3AA', '#5CA7A3', '#61AB9B', '#67B092', '#70B486', '#7AB779', '#88BB6B', '#99BD5D', '#AABD51', '#BBBC49', '#C8B844', '#D3B23F', '#DBAB3C', '#E1A23A', '#E49838', '#E68D35', '#E68033', '#E57330', '#E4642D', '#E05229', '#DD3D26', '#DA2322', '#C4221F', '#AD211D', '#95211B', '#7E1F18', '#671C15', '#521A13'];

                //Get unique labels list and their colors
                let step = 1;
                if(this.samples.length <= colors.length/2) {
                    step = Math.trunc(colors.length / this.samples.length);
                }
                let colorPalette = {};
                for(let i = 0; i < this.samples.length; i++) {
                    colorPalette[this.samples[i].name] = colors[i*step];
                }

                console.log(JSON.stringify(colorPalette));

                return colorPalette;
            },

            pcaPlots() {
                this.generating_plots = true;
                axios.post(this.pcaPlotsUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        console.log(error.message)
                    })
            },

            processCompleted() {
                this.plot_data = 'qc_pca_plots' in this.project.project_parameters ? JSON.parse(this.project.project_parameters.qc_pca_plots) : null;
                this.generating_pca = false;
            },

            processPlotsCompleted() {
                this.generating_plots = false;
            },


            getColorPaletteHeatmap(values, ini, total) {
                let colors = ['#A26FAE', '#9A60A6', '#8F539C', '#804D99', '#6D4D9C', '#6355A5', '#5B5FAF', '#5469B9', '#4F75C2', '#4D80C5', '#4D8BC4', '#4D93BE', '#5099B7', '#549FB1', '#58A3AA', '#5CA7A3', '#61AB9B', '#67B092', '#70B486', '#7AB779', '#88BB6B', '#99BD5D', '#AABD51', '#BBBC49', '#C8B844', '#D3B23F', '#DBAB3C', '#E1A23A', '#E49838', '#E68D35', '#E68033', '#E57330', '#E4642D', '#E05229', '#DD3D26', '#DA2322', '#C4221F', '#AD211D', '#95211B', '#7E1F18', '#671C15', '#521A13'];
                let step = 1;
                if(total <= colors.length/2) {
                    step = Math.trunc(colors.length / total);
                }
                let palette = [];
                for(let i = 0; i < values.length; i++) {
                    let element = {};
                    element[values[i]] = colors[(ini+i)*step];
                    palette.push(element);
                }
                return palette;
            },

            processMetadata() {
                if(this.project.project_parameters && this.project.project_parameters.metadata && this.project.project_parameters.metadata.length) {
                    let metadata = JSON.parse(this.project.project_parameters.metadata)
                    let totalValues = 0;
                    metadata.forEach( meta => {

                        //Create a list of metadata names
                        this.metadataNames.push(meta.name);

                        this.metadataValues[meta.name] = [];
                        let values = [];
                        for(let val in meta.values) {
                            values.push(meta.values[val]);
                            let element = {};
                            element[val] = meta.values[val];
                            this.metadataValues[meta.name].push(element);
                        };

                        //Assign a color to each metadata value
                        values = [...new Set(values)];
                        this.metadataPalette[meta.name] = values;
                        totalValues += values.length;

                        //Create a list of metadata names with their samplenames sorted
                        const sortedValues = values.sort();
                        this.metadataSorted[meta.name] = [];
                        sortedValues.forEach(metadataName => {
                            this.metadataValues[meta.name].forEach((val, key) => {
                                const sampleName = Object.keys(val)[0];
                                const value = val[sampleName];
                                if(value === metadataName) this.metadataSorted[meta.name].push(sampleName);
                            });
                        });

                    });

                    let iValues = 0;
                    metadata.forEach( meta => {
                        let tmpValues = this.metadataPalette[meta.name].length;
                        console.log(iValues);
                        this.metadataPalette[meta.name] = this.getColorPaletteHeatmap(this.metadataPalette[meta.name], iValues, totalValues);
                        console.log(this.metadataPalette[meta.name]);
                        iValues += tmpValues;
                    });

                    this.metadataSortedBy = this.metadataNames.length ? this.metadataNames[0] : '';
                }

                this.loaded = true;
            },
        },



    }
</script>
