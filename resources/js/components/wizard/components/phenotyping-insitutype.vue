<template>
<div v-if="loaded" class="m-4">
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

                <div class="w-100 w-lg-90 w-xxl-85" :class="(processing || processing2 || renaming) ? 'disabled-clicks' : ''">

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
                    <send-job-button label="Run InSituType" :disabled="!params.cell_profile || processing || processing2 || renaming" :project-id="project.id" job-name="InSituType" @started="runInSituType" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
                </div>


                <div v-if="'InSituType' in this.project.project_parameters" class="w-100 w-lg-90 w-xxl-85" :class="(processing || processing2 || renaming) ? 'disabled-clicks' : ''">
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

                    <!-- <div class="row justify-content-center text-center m-4">
                        <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                            <div>Color palette <show-modal tag="sdd_spagcn_color_palette"></show-modal></div>
                            <div><Multiselect :options="colorPalettes" v-model="params2.color_pal"></Multiselect></div>
                        </div>
                    </div> -->

                    <div class="my-4">
                        <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :selected-keys="visibleSamples" @selected="(keys) => visibleSamples = keys"></project-summary-table>
                    </div>

                </div>

                <div v-if="'InSituType' in this.project.project_parameters" class="text-center mt-3">
                    <send-job-button label="Generate Plots" :disabled="processing || processing2 || renaming || !visibleSamples.length" :project-id="project.id" job-name="InSituType2" @started="runInSituType2" @ongoing="processing2 = true" @completed="processCompleted2" :project="project" ></send-job-button>
                </div>


                <div v-if="!processing && !processing2 && annotations_renamed" class="row mt-3">
                    <div class="p-3 text-end">
                        <send-job-button label="Complete renaming" :disabled="processing || renaming" :project-id="project.id" job-name="InSituTypeRename" @started="runInSituTypeRename" @completed="runInSituTypeRenameCompleted" :project="project" ></send-job-button>
                    </div>
                </div>


                <color-palettes @colors="changeColorPalette"></color-palettes>


                <div class="mt-4" v-if="('InSituType2' in this.project.project_parameters) && !processing && !processing2 && !renaming && annotations !== null">

                    <ul class="nav nav-tabs" id="inSituTypePlots" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="inSituType-SpatialPlots-tab'" data-bs-toggle="tab" data-bs-target="#inSituType-SpatialPlots" type="button" role="tab" aria-controls="inSituType-SpatialPlots" aria-selected="true">Spatial plots</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inSituType-OtherPlots-tab'" data-bs-toggle="tab" data-bs-target="#inSituType-OtherPlots" type="button" role="tab" aria-controls="inSituType-OtherPlots" aria-selected="true">UMAP & Flight plot</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="inSituTypePlotsContent">

                        <div class="tab-pane fade show active mt-4" id="inSituType-SpatialPlots" role="tabpanel" aria-labelledby="inSituType-SpatialPlots-tab">
                            <ul class="nav nav-tabs" id="inSituTypeSpatialPlots" role="tablist">
                                <template v-for="(csvUrl, sampleName, index) in inSituType2.plot_data">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="'inSituType-' + sampleName + '-tab'" data-bs-toggle="tab" :data-bs-target="'#inSituType-' + sampleName" type="button" role="tab" :aria-controls="'inSituType-' + sampleName" aria-selected="true">{{ sampleName }}</button>
                                    </li>
                                </template>
                            </ul>
                            <div class="tab-content" id="inSituTypeSpatialPlotsContent">

                                <template v-for="(csvUrl, sampleName, index) in inSituType2.plot_data">
                                <div class="tab-pane fade" :class="index === 0 ? 'show active' : ''" :id="'inSituType-' + sampleName" role="tabpanel" :aria-labelledby="'inSituType-' + sampleName + '-tab'">


                                    <template v-for="(plotData, annotation) in plot_data[sampleName]">
                                        <div class="my-4" style="width: 100%; height: 700px">
                                            <plots-component
                                                :base="getSampleByName(sampleName).image_file_url"
                                                :csv="plotData.data"
                                                :title="plot_data[sampleName][annotation]['title']"
                                                plot-type="cluster"
                                                :color-palette="plot_data[sampleName][annotation]['palette']"
                                                :legend-min="0"
                                                :legend-max="10"
                                                :is-y-axis-inverted="project.project_platform_id === 3"
                                                :is-grouped="true"
                                                :p-key="sampleName.replaceAll(' ', '').replaceAll('.','') + '_insitutype_' + annotation.replaceAll(' ', '').replaceAll('.','')"
                                            ></plots-component>
                                        </div>
                                        <stdiff-rename-annotations-clusters :annotation="annotations[sampleName][annotation]" :sample-name="sampleName" :file-path="inSituType2.base_path + sampleName" prefix="insitutype_cell_types_insitutype_plot_spatial_" suffix="_top_deg" :rename-url="inSituTypeRenameUrl" @changes="annotationChanges"></stdiff-rename-annotations-clusters>
                                    </template>


                                </div>
                                </template>


                                <!-- <template v-for="(image, sample, index) in inSituType2.plots">
                                    <div v-if="!['insitutype_umap', 'insitutype_flightpath'].includes(sample) /*visibleSamples.includes(sample)*/" class="tab-pane fade" :class="index === 0 ? 'show active' : ''"
                                         :id="'inSituType-' + sample" role="tabpanel" :aria-labelledby="'inSituType-' + sample + '-tab'">
                                        <div>
                                            <show-plot :src="image" :show-image="Boolean(getSampleByName(sample))" :sample="getSampleByName(sample)" :side-by-side="true" side-by-side-tool-tip="vis_quilt_plot_side_by_side"></show-plot>
                                            <stdiff-rename-annotations-clusters :annotation="annotations[sample][getAnnotation(sample, image)]" :sample-name="sample" :file-path="image" prefix="insitutype_cell_types_" suffix="_top_deg" @changes="annotationChanges"></stdiff-rename-annotations-clusters>
                                            <stdiff-rename-annotations-clusters :annotation="getAnnotation(sample, image)" :file-path="image" prefix="insitutype_" suffix="_top_deg"></stdiff-rename-annotations-clusters>
                                        </div>
                                    </div>
                                </template> -->
                            </div>
                        </div>

                        <div class="tab-pane fade mt-4" id="inSituType-OtherPlots" role="tabpanel" aria-labelledby="inSituType-OtherPlots-tab">

                            <ul class="nav nav-tabs" id="inSituTypeAdditionalPlots" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="inSituType-UMAP-plot-tab'" data-bs-toggle="tab" data-bs-target="#inSituType-UMAP-plot" type="button" role="tab" aria-controls="inSituType-UMAP-plot" aria-selected="true">UMAP plot</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="inSituType-Flight-plot-tab'" data-bs-toggle="tab" data-bs-target="#inSituType-Flight-plot" type="button" role="tab" aria-controls="inSituType-Flight-plot" aria-selected="true">Flight plot</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="inSituTypeAdditionalPlotsContent">

                                <div class="tab-pane fade show active mt-4" id="inSituType-UMAP-plot" role="tabpanel" aria-labelledby="inSituType-UMAP-plot-tab">

                                    <div>
                                        <plot-holder
                                            :csv="inSituType.plot_data.umap"
                                            expression="Umap"
                                            title="UMAP Plot"
                                            plot-type="umap"
                                            :inverted="false"
                                            :palette="{'mast.cell': '#0a4b1c', 'muscle.cell': '#128c08', 'lymph.vessel.cell': '#0eb887', 'CD4+.T.cell': '#f0a97e', 'alveolar.epithelial.cell.type.1': '#4da0de', 'plasma.cell': '#d8b1ab', 'regulatory.T.cell': '#6934fb', 'B.cell': '#50f662', 'undefined': '#a94be1', 'monocyte': '#e7a662', 'activated.dendritic.cell': '#a866cf', 'alveolar.epithelial.cell.type.2': '#eca8db', 'dendritic.cell.type.2': '#86852f', 'ciliated.cell': '#a6104b', 'MARCOpos.macrophage': '#c5be06', 'natural.killer.cell': '#4ee933', 'MARCOneg.macrophage': '#b61410', 'plasmacytoid.dendritic.cell': '#151da5', 'CD8+.cytotoxic.T.cell': '#cb655c', 'dendritic.cell.type.1': '#18dfd3', 'blood.vessel.cell': '#f9cd72', 'fibroblast': '#9e03fe'}"
                                        ></plot-holder>
                                    </div>

                                    <!-- <show-plot :src="inSituType2.plots['insitutype_umap']" file-extension="png" :show-image="false"></show-plot> -->
                                </div>

                                <div class="tab-pane fade mt-4" id="inSituType-Flight-plot" role="tabpanel" aria-labelledby="inSituType-Flight-plot-tab">

                                    <div width="800" height="800">
                                        <plot-holder
                                            :csv="inSituType.plot_data.flightpath"
                                            expression="Flight"
                                            title="Flight Path"
                                            plot-type="flight"
                                            :label-csv="inSituType.plot_data.flightpath"
                                            :palette="{'mast.cell': '#0a4b1c', 'muscle.cell': '#128c08', 'lymph.vessel.cell': '#0eb887', 'CD4+.T.cell': '#f0a97e', 'alveolar.epithelial.cell.type.1': '#4da0de', 'plasma.cell': '#d8b1ab', 'regulatory.T.cell': '#6934fb', 'B.cell': '#50f662', 'undefined': '#a94be1', 'monocyte': '#e7a662', 'activated.dendritic.cell': '#a866cf', 'alveolar.epithelial.cell.type.2': '#eca8db', 'dendritic.cell.type.2': '#86852f', 'ciliated.cell': '#a6104b', 'MARCOpos.macrophage': '#c5be06', 'natural.killer.cell': '#4ee933', 'MARCOneg.macrophage': '#b61410', 'plasmacytoid.dendritic.cell': '#151da5', 'CD8+.cytotoxic.T.cell': '#cb655c', 'dendritic.cell.type.1': '#18dfd3', 'blood.vessel.cell': '#f9cd72', 'fibroblast': '#9e03fe'}"
                                            :inverted="false"
                                        ></plot-holder>
                                    </div>

                                    <!-- <show-plot :src="inSituType2.plots['insitutype_flightpath']" file-extension="png" :show-image="false"></show-plot> -->
                                </div>

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
        name: 'stdeconvolve',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            inSituTypeUrl: String,
            inSituType2Url: String,
            inSituTypeRenameUrl: String,
            colorPalettes: Object,
        },

        data() {
            return {

                visibleSamples: [],

                inSituType: ('InSituType' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType) : {},
                inSituType2: ('InSituType2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType2) : {},

                processing: false,
                processing2: false,
                renaming: false,

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
                    ptsize: 1,
                    color_pal: 'discreterainbow'
                },

                annotations: null,

                active_annotations: [],

                annotations: null,
                active_annotations: [],
                annotations_renamed: false,

                plot_data: {},

                loaded: false,

                colorPalette: [],

            }
        },

        async mounted() {
            await this.loadAnnotations();

            if(!('plot_data' in this.inSituType2)) {
                this.loaded = true;
                return;
            }

            for(let sample in this.inSituType2.plot_data) {
                let data = await axios.get(this.inSituType2.plot_data[sample]);
                this.processPlotFile(sample, data.data);
            }

            //console.log(this.plot_data);

            this.loaded = true;
        },

        methods: {

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
                let labels = [];
                for(let sampleName in this.annotations) {
                    for(let _annotation in this.annotations[sampleName]) {
                        labels.push(...this.annotations[sampleName][_annotation].clusters.map(cluster => ('newName' in cluster && cluster.newName.length) ? cluster.newName : cluster.modifiedName));
                    }
                }
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

            processPlotFile(sampleName, data) {
                this.plot_data[sampleName] = {};
                const columnNames = data.split('\n')[0].split(',');
                for(let i = 2; i < columnNames.length; i++) {
                    this.plot_data[sampleName][columnNames[i]] = {};
                    this.plot_data[sampleName][columnNames[i]]['data'] = this.extractColumnsFromCSV(data, [1, 2, i+1]);
                    this.plot_data[sampleName][columnNames[i]]['palette'] = this.getColorPalette(sampleName, columnNames[i]);
                    this.plot_data[sampleName][columnNames[i]]['title'] = this.annotations[sampleName][columnNames[i]]['modifiedName'];

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
                this.annotations =  await this.$getProjectSTdiffAnnotationsBySample(this.project.id, 'insitutype');
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

                this.params2['samples'] = this.visibleSamples;

                axios.post(this.inSituType2Url, this.params2)
                    .then((response) => {})
                    .catch((error) => {
                        this.processing2 = false;
                        console.log(error.message);
                    })
            },

            async processCompleted2() {
                //console.log(this.project.project_parameters);
                this.inSituType2 = ('InSituType2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.InSituType2) : {};
                await this.loadAnnotations();
                this.processing2 = false;
            },


            runInSituTypeRename() {

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

                axios.post(this.inSituTypeRenameUrl, parameters)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.renaming = false;
                        console.log(error);
                    })

            },

            async runInSituTypeRenameCompleted() {
                //this.inSituType2 = ('InSituType2' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.stclust) : {};
                await this.loadAnnotations();
                this.renaming = false;
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
