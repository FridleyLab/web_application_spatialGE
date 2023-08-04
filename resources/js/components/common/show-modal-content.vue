<template>


    <div v-if="visible" :class="visible ? 'popup-center' : ''">
        <div class="card" style="width: 36rem;">
            <div class="card-body">
                <h5 class="card-title text-muted">{{ title() }}</h5>
                <p class="card-text">{{ toolTip() }}</p>
                <div class="text-center">
                    <a href="#" class="card-link text-info" @click="hideContent">Close</a>
                </div>
            </div>
        </div>
    </div>

</template>

<script>

export default {
    name: 'showModalContent',

    props: {

    },

    data() {
        return {
            visible: false,

            tag: '',
            tool_tips: {
                //New project
                "new_project_platform": {"title": "Platform selection", "text": "The spatial transcriptomics platform used to generate the data. Currently only one platform can be used per project."},
                "new_project_name": {"title": "Project name", "text": "Required. The name of the project."},
                "new_project_description": {"title": "Project description", "text": "Optional. A short description of the project."},


                //Import data
                "importdata_sample_name": {"title": "Sample name", "text": "The name of the sample. Names without spaces are preferred, or with spaces replaced by underscores (“_”). If no sample name is provided, the name “SampleXX” will be automatically used, with “XX” being a consecutive number."},
                "importdata_gene_expression_file": {"title": "Gene expression file", "text": "Required. Gene expression file. The file containing the gene counts for the samples. Different files are accepted depending on the spatial transcriptomics platform used. For example, for Visium samples, an .h5 file could be provided."},
                "importdata_coordinates_file": {"title": "Coordinates file", "text": "Required. Coordinates file. The file containing the ROI/spot/cell coordinates for the same sample as in “Gene expression”."},
                "importdata_tissue_image_file": {"title": "Tissue image file", "text": "Optional. An image of the tissue profiled with spatial transcriptomics. If Visium, please make sure the “hires” (high resolution) image is used."},
                "importdata_scale_factor_file": {"title": "Scale factor file", "text": "Required if image input. For Visium, a JSON file resulting from Space Ranger that contains the image scaling factor."},
                "importdata_metadata_file": {"title": "Upload metadata file (csv/excel)", "text": "Upload an Excel or text file (comma- or tab-separated) containing sample-level data such as therapy, tissue type, age of donor. Please refer to “How to get started” for information on the required format of this file."},
                "importdata_metadata_manually": {"title": "Add metadata manually", "text": "Add sample-level metadata manually. Once this option is selected, click on “ADD NEW METADATA COLUMN” and provide a name for the metadata column in the table displayed below."},

                //QC & data transformation Filter
                "qcfilter_samples": {"title": "Sample selection", "text": "Select the samples that this filtering procedure will affect. Samples not selected will not be removed from the analysis, however, gene counts will not be affected. Most users will likely apply the filter to all samples."},
                "qcfilter_spots_cells": {"title": "Filter spots/cells", "text": "Remove ROIs/spots/cells based on gene counts or number of expressed genes."},
                "qcfilter_genes": {"title": "Filter genes", "text": "Remove genes based on counts or number of ROIs/spots/cells where genes are expressed."},
                "qcfilter_spots_cells_number_counts": {"title": "Keep spots/cells with this number of counts", "text": "Keep ROIs/spots/cells if their total counts falls within the specified range. The use of this filter will depend on the read/count depth of the experiment. A very conservative suggestion is to keep ROIs/spots/cells with more than 500 counts."},
                "qcfilter_spots_cells_number_expressed_counts": {"title": "Keep spots/cells with this number of expressed genes", "text": "Keep ROIs/spots/cells if their number of expressed genes falls within the specified range. A gene is considered expressed if at least one count was detected for that spot. The use of this filter will depend on the read/count depth of the experiment. A very conservative suggestion is to keep ROIs/spots/cells with more than 100 expressed genes."},
                "qcfilter_spots_cells_percentage_counts": {"title": "Keep spots/cells by percentage of counts", "text": "Keep ROIs/spots/cells based on their percentage of counts assigned to mitochondrial or ribosomal genes. This filter looks for gene names that begin with “MT-” or “RP” followed by an L or S (e.g., Visium). If the user’s data does not contain gene names matching those criteria, the filter will not work. The use of this filter will depend on the read/count depth of the experiment. A very conservative suggestion is to keep ROIs/spots/cells with total counts between 0-20% from mitochondrial or ribosomal genes."},
                "qcfilter_spots_cells_percentage_counts_regexp": {"title": "Using regular expression", "text": "Keep ROIs/spots/cells based on their percentage of counts assigned to gene matching a token (or regular expression). For example, specifying a token such as “^LNC” will filter based on the percentage of long non-coding RNA genes. This filter is not intended for the vast majority of cases."},
                "qcfilter_genes_excluded": {"title": "Excluded genes", "text": "Remove all counts originating from these genes. Rarely this filter is used, but it can be used to remove, for example, negative probes (as those used in CosMx)."},
                "qcfilter_genes_remove_mito_ribo": {"title": "Remove mitochondrial and/or ribosomal genes", "text": "These options will remove all the counts originating from mitochondrial or ribosomal genes. This is a rarely used filter. One example of its use is when a sample has abundant necrotic tissue, potentially rich in mitochondrial gene expression obscuring the expression of other genes. In that case, users might want to remove all mitochondrial gene counts."},
                "qcfilter_genes_remove_regexp": {"title": "Remove genes using a regular expression", "text": "Remove all the counts originating from gene with names matching a token (or regular expression). This is a rarely used filter. One example of its use is when a user wishes to remove all long non-coding RNA counts, which can be done by entering the token “^LNC”."},
                "qcfilter_genes_counts_between": {"title": "Keep genes with counts between", "text": "Keep genes with total counts (across all ROIs/spots/cells) within the specified range. The use of this filter will depend on the read/count depth of the experiment. A very conservative suggestion is to keep genes with at least 20 counts across all ROIs/spots/cells."},
                "qcfilter_genes_expressed_in": {"title": "Keep genes expressed in", "text": "Keep genes expressed in the specified percentage of ROIs/spots/cells. A gene is considered expressed if at least one count was recorded. Although not a commonly used filter, users with low read/count depth might want to remove genes poorly detected (e.g. keep genes detected in at least 10% of all ROIs/spots/cells)."},
                //QC & data transformation - normalization
                "qcnormalization_log_scaling_factor": {"title": "Scaling factor", "text": "The scaling factor used in the normalization procedure. Roughly, should be set to a value representing the expected number of counts in each ROI/spot/cell. As an example, for Visium 10000 is a reasonable value, but for single cell spatial transcriptomics, lower values should be specified due to less counts per cell."},
                "qcnormalization_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html)"},
                "qcnormalization_gene_selection": {"title": "Gene selection", "text": "Specify the name of a single gene to visualize the distribution of counts per spot."},
                //QC & data transformation - PCA
                "qcpca_number_variable_genes": {"title": "Number of variable genes", "text": "Number of genes used in the calculation of PCs. The most variable genes are selected using the standard deviation across samples as criterion. Values between 3000 and 5000 should be appropriate for most studies."},
                "qcpca_number_genes_display_heatmap": {"title": "Number of genes for heatmap", "text": "The number of genes (rows) to show in the heatmap. The genes are selected using the standard deviation across samples as criterion. No differential expression analysis is involved here."},
                "qcpca_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html)"},
                "qcpca_color_by": {"title": "Metadata selection", "text": "Optional. Name of a sample-level metadata variable. Points in the PCA plot will be colored according to this variable. If no variable is selected, points are colored according to the sample names."},

                //STgradient
                "stgradient_samples": {"title": "Sample selection", "text": "Select the samples to run STgradient"},
                "stgradient_genes": {"title": "Number of most variable genes to use", "text": "Maximum number of genes per sample to test for spatial gradients. The genes to be tested are selected based on standard deviation"},
                "stgradient_annotation_to_test": {"title": "Annotation to test", "text": "The tissue niches to run STgradient. After running STclust (see “Spatial domain detection”), users can choose one of the results generated by that algorithm."},
                "stgradient_reference_cluster": {"title": "Reference cluster", "text": "Select the reference cluster from which spatial gradients are to be tested. The reference cluster could be, for example, a tumor region detected by STclust."},
                "stgradient_clusters_to_exclude": {"title": "Cluster(s) to exclude (optional)", "text": "Optional. Exclude regions/niches from the analysis. By specifying one or more niches, the distances of those spots/cells will not be calculated and will be excluded from the analyses. This option could be useful when removing tissue niches that show necrosis and could add noise to the Spearman’s coefficients."},
                "stgradient_robust_regression": {"title": "Robust regression", "text": "Whether to use robust regression. The functionality is provided by the f.robftest function from the sfsmisc R package. In robust regression, outliers are given less weight towards the calculation of the regression coefficient."},
                "stgradient_ignore_outliers": {"title": "Ignore outliers", "text": "Whether to ignore outliers. This option is automatically disabled if robust regression is selected. If outliers are ignored, traditional linear regression is carried out after removing spots/cells defined as outliers by the interquartile range method. If unchecked, all spots are considered in the analysis."},
                "stgradient_minimum_number_of_neighbors": {"title": "Minimum number of neighbors", "text": "The minimum number of immediate neighbors a reference spot/cell must have to be included in the analysis. This parameter intends to reduce the effect of isolated spots/cells in the calculation of the correlation coefficients. Unsupervised clustering algorithms (such as STclust) can assign niches to isolated spots. Reference spots with less neighbors than specified, will be omitted from the analysis, and hence distances from that spot will not be calculated."},
                "stgradient_restrict_correlation_to_this_limit": {"title": "Restrict correlation to this limit", "text": "A distance value to restrict the correlation analysis. Some tissues can be very heterogeneous in composition. As a result it could be reasonable to test for spatial gradients within a restricted area of the tissue."},
                "stgradient_distance_summary_metric": {"title": "Distance summary metric", "text": "The method to summarize the distances. STgradient calculates the distances each spot/cell to all the spots/cells in the reference niche. A single distance value is calculated for each spot/cell by taking the minimum or average distance. The users are encouraged to try both methods and look for the approach that provides biologically insightful results."},

            },
        }
    },

    mounted() {
        this.emitter.on("show-tooltip", tag => {
            this.showContent(tag);
        });
    },

    methods: {
        showContent(tag) {
            this.tag = tag;
            this.visible = true;
            document.getElementById('_body').classList.add('disabled-clicks');
        },

        hideContent() {
            this.visible = false;
            document.getElementById('_body').classList.remove('disabled-clicks');
        },

        toolTip() {
            return this.tag in this.tool_tips ? this.tool_tips[this.tag].text : '';
        },

        title() {
            return this.tag in this.tool_tips ? this.tool_tips[this.tag].title : '';
        }
    },
}
</script>
