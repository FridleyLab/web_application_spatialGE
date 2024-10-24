<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_2</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">QC & Data Transformation</h6>
                            <show-vignette url="/documentation/vignettes/qc_data_transformation.pdf"></show-vignette>
                        </div>
                    </div>

                    <div class="card-body">

                        <ul class="text-justify text-sm">
                            <li><strong>Original summary:</strong> A table displaying the minimum, average, and maximum number of counts per ROI/spot/cell, as well as the minimum, average, and maximum number of genes per ROI/spot/cell. Genes count as expressed if at least one count was detected in at least one ROI/spot/cell.</li>
                            <li><strong>Filter data:</strong> Remove ROIs/spots/cells or genes from the data set. The filter can be executed on all samples or a subset. Internally, spatialGE executes the filter in the following order: 1. Samples specified in “Select samples to apply this filter”, 2. Genes specified by name in “Filter genes”, 3. ROIs/spots/cells and/or genes based on min and max counts.</li>
                            <li><strong>Normalize data:</strong> Transform gene expression counts, with log-normal or SCTransfom as options. The normalization procedure does not require filtering, but removal of low-count spots/cells or low count genes is recommended.</li>
                            <li><strong>Pseudobulk analysis:</strong> Explore sample-level gene expression. Pseudobulk analysis combines gene counts across all spots/cells and produce pseudo-RNAseq samples. This analysis allows exploration of sample-level transcriptional patterns. Does not require execution of the Filter Data or Normalize Data sections.</li>
                            <li><strong>Quilt plot:</strong> Generates spatial (quilt) plots showing spot/cell-level metadata. Users can assess QC metrics such as gene counts per spot or total counts per spot and detect potential technical artifacts.</li>
                        </ul>

                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">Original summary</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="filter-tab" data-bs-toggle="tab" data-bs-target="#filter" type="button" role="tab" aria-controls="filter" aria-selected="false">Filter data</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="normalize-tab" data-bs-toggle="tab" data-bs-target="#normalize" type="button" role="tab" aria-controls="normalize" aria-selected="false">Normalize data</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button :disabled="pca_disabled" class="nav-link" id="pca-tab" data-bs-toggle="tab" data-bs-target="#pca" type="button" role="tab" aria-controls="pca" aria-selected="false">Pseudo-bulk analysis</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="quilt-tab" data-bs-toggle="tab" data-bs-target="#quilt" type="button" role="tab" aria-controls="quilt" aria-selected="false">Quilt plot</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                                <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :allow-selection="false" :download-button="true"></project-summary-table>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="filter" role="tabpanel" aria-labelledby="filter-tab">
                                <qc-dt-filter :project="project" :samples="samples" :color-palettes="colorPalettes" :filter-url="filterUrl" :filter-url-plots="filterUrlPlots"></qc-dt-filter>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="normalize" role="tabpanel" aria-labelledby="normalize-tab">
                                <qc-dt-normalize :samples="samples" :project="project" :color-palettes="colorPalettes" :normalize-url="normalizeUrl" :normalize-url-plots="normalizeUrlPlots" :normalized-url-data="normalizedUrlData"></qc-dt-normalize>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="pca" role="tabpanel" aria-labelledby="pca-tab">
                                <qc-dt-pca :samples="samples" :project="project" :pca-url="pcaUrl" :pca-plots-url="pcaPlotsUrl" :color-palettes="colorPalettes"></qc-dt-pca>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="quilt" role="tabpanel" aria-labelledby="quilt-tab">
                                <qc-dt-quilt :samples="samples" :project="project" :quilt-url="quiltUrl" :color-palettes="colorPalettes"></qc-dt-quilt>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'qcDataTransformation',

        props: {
            project: Object,
            samples: Object,
            colorPalettes: Object,
            filterUrl: String,
            filterUrlPlots: String,
            normalizeUrl: String,
            normalizeUrlPlots: String,
            normalizedUrlData: String,
            pcaUrl: String,
            pcaPlotsUrl: String,
            quiltUrl: String,
        },

        data() {
            return {
                pca_disabled: !('pca_max_var_genes' in this.project.project_parameters),
            }
        },

        mounted() {
            this.emitter.on("allow-pca", allow_pca => {
                this.pca_disabled = !allow_pca;
            });
        }

    }
</script>
