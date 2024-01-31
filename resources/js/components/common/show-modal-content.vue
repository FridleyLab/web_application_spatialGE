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


                "running_other_process": {"title": "Running other process", "text": "There's a process currently queued/running. When completed you will be able to submit another."},

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
                //QC & data transformation - Quilt
                "qcquilt_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html)"},
                "qcquilt_variable": {"title": "Variable", "text": "The ROI/spot/cell-level variable to plot. Currently, only total_counts and total_genes are available."},
                "qcquilt_first_sample": {"title": "First sample", "text": "Select a sample to generate a quilt plot."},
                "qcquilt_second_sample": {"title": "Second sample", "text": "Select a sample to generate a quilt plot. It must be a different sample."},

                //Visualization
                "vis_quilt_plot_genes": {"title": "Search and select genes", "text": "Enter one or more genes to generate quilt plots. The textbox has auto-complete capabilities. Gene names in spatialGE are case-sensitive."},
                "vis_quilt_plot_point_size": {"title": "Point size", "text": "The size of the point in the plots representing each Region of Interest (ROI), spot, or cell. The default (2) works for most situations."},
                "vis_quilt_plot_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html). Diverging and sequential palettes are recommended (see Khroma and RColorBrewer documentation)."},
                "vis_quilt_plot_data_type": {"title": "Data type", "text": "Whether to plot the raw or normalized expression."},
                "vis_quilt_plot_side_by_side": {"title": "Side by side", "text": "Whether to add the tissue image next to the quilt plot in the downloadable files."},
                "vis_expr_surf_genes": {"title": "Search and select genes", "text": "Enter one or more genes to estimate expression surfaces. The textbox has auto-complete capabilities. Gene names in spatialGE are case-sensitive. Spatial interpolation via “kriging” is computationally intensive, hence the more genes to be interpolated, the more time it takes to complete."},
                "vis_expr_surf_estimate": {"title": "Estimate button", "text": "Click this button to estimate expression surfaces. This button starts the estimation of expression surfaces which is a necessary step prior to the plotting of expression surfaces."},
                "vis_expr_surf_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html). Diverging and sequential palettes are recommended (see Khroma and RColorBrewer documentation)."},
                "vis_expr_surf_generate": {"title": "Generate plots button", "text": "Once expression surfaces have been estimated, this button allows to re-plot expression surfaces if a different color palette is desired."},

                //Spatial heterogeneity
                "sthet_plot_genes": {"title": "Search and select genes", "text": "Enter one or more genes to calculate spatial heterogeneity/autocorrelation statistics. This textbox features autocomplete. By typing the initial letters of a gene name, matching gene names appear in the list. Click on the matching genes to calculate statistics. Gene names are case-sensitive."},
                "sthet_plot_methods": {"title": "Methods", "text": "The statistics to be calculated. One of Moran’s I, Geary’s C, or both can be requested."},
                "sthet_plot_genes_plot": {"title": "Genes to plot", "text": "Select one or more genes to generate comparative plots. Only genes for which spatial statistics have been calculated have been calculated will appear here."},
                "sthet_plot_color_by": {"title": "Color by metadata", "text": "One of the sample metadata columns input during data import. Each point in the plot (samples) will be colored according to the selected metadata variable."},
                "sthet_plot_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots. Names are derived from the R packages Khroma (https://cran.r-project.org/web/packages/khroma/vignettes/tol.html) and RColorBrewer (https://r-graph-gallery.com/38-rcolorbrewers-palettes.html). Qualitative and some of the sequential palettes are recommended (see Khroma and RColorBrewer documentation)."},
                "sthet_plot_download_stats": {"title": "Download statistics", "text": "Click this button to download a file containing a table with the calculated spatial statistics. Useful for plotting with other software or reporting in scientific articles."},

                //Spatial Domain Detection
                "sdd_stclust_spatial_weight": {"title": "Spatial weight", "text": "Select a spatial weight to use in “shrinkage” of gene expression differences among ROIs/spots/cells. Spatial weights larger than 0.1 often result in non-informative clusters."},
                "sdd_stclust_range_of_ks": {"title": "Range of Ks", "text": "Define domains over a range of K values. STclust will assign ROIs/spots/cells to as many domains as each of the K in the selected range. This option allows for deeper exploration of data as users can select the K value that better explains differences among domains."},
                "sdd_stclust_dynamicTreeCuts": {"title": "DynamicTreeCuts", "text": "Allow the number of domains per sample to be automatically detected via DynamicTreeCuts. The algorithm uses an iterative approach looking for the most stable number of domains."},
                "sdd_stclust_number_of_domains": {"title": "Number of domains", "text": "Select a range of K values to define domains in each sample. STclust will define as many domains in the samples as each of the values in the range."},
                "sdd_stclust_deepsplit": {"title": "DeepSplit", "text": "The DeepSplit parameter allows rough control over the number of detected domains when using DynamicTreeCuts. The larger DeepSplit is, the more domains are detected in each sample."},
                "sdd_stclust_number_genes": {"title": "Number of most variable genes to use", "text": "Use this many high-variance genes to estimate gene expression differences among ROIs/spots/cells. The genes are selected using the vst function from the R package Seurat. In spatial transcriptomics data, more than 5000 genes often do not result in better defined clusters due to zero-inflation."},
                //SpaGCN
                "sdd_spagcn_perc_neigh_expr": {"title": "Percentage of neighborhood expression", "text": "The parameter p is used during the construction of the graph. It controls how much the gene expression of neighboring spots/cells affects each spot/cell. The parameter is used to estimate the rapidness at which the spatial weight decays with distance. For Visium data sets, p=0.5 is recommended. For single-cell level data, apply larger values of p."},
                "sdd_spagcn_seed_number": {"title": "Seed number (permutation)", "text": "Seed number used during SpaGCN parameter estimation. Repeating the analysis with different seed numbers is encouraged to check for consistency."},
                "sdd_spagcn_refine_clusters": {"title": "Refine clusters?", "text": "Whether to refine domain assignments. Spots with more than half of their neighbors assigned to a different domain are assigned its neighbors’ domain. Available only for gridded technologies (i.e., Visium data sets)."},
                "sdd_spagcn_number_of_domains": {"title": "Number of domains", "text": "Select a range of K values to define domains in each sample. SpaGCN will define as many domains in the samples as each of the values in the range."},
                "sdd_spagcn_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the plots."},
                //SpaGCN-SpatiallyVarGenes
                "spagcn_spavargenes_annotation": {"title": "Annotation to test", "text": "The clustering solution, usually but not necessarily generated by SpaGCN (Spatial domain detection module). To select the best option here, refer to the Spatial domain detection module to see which clustering solution best represents the tissue domains in the samples. If different solutions fit different samples, then the differential expression should be run more than once, and results saved each time as spreadsheets."},

                //STenrich
                "stenrich_select_gene_set": {"title": "Select a gene set database", "text": "Select a gene set database to test for spatial enrichment. Future implementations will allow input of custom gene sets."},
                "stenrich_select_measure": {"title": "Measure of gene set expression", "text": "Currently disabled. Select the measure of gene set expression. Either the average gene set expression or GSEA score. The calculation of GSEA scores is currently not supported."},
                "stenrich_permutations": {"title": "Number of permutations", "text": "The number of permutations to estimate the null distribution (no-spatial pattern). The more permutations, the longer STenrich takes to complete, but p-values will be more accurate."},
                "stenrich_seed_number": {"title": "Seed number", "text": "A seed number to replicate results. It is advisable to run STenrich with different seed values to check for consistency. Different seed values could yield slightly different p-values."},
                "stenrich_minimum_spots": {"title": "Minimum number of spots", "text": "The minimum number of high expression ROIs/spots/cells required for a gene set to be tested. If a sample has less than this number of high expression ROIs/spots/cells, the gene set is not tested in that sample."},
                "stenrich_minimum_genes": {"title": "Minimum number of genes", "text": "The minimum number of genes of a set required to be present in a sample, for that gene set to be tested in that sample. If a sample has less genes of a set than this number, the gene set is ignored in that sample."},
                "stenrich_standard_deviations": {"title": "Number of standard deviations", "text": "The number of standard deviations to define the high expression threshold. If an ROI/spot/cell has average gene set expression larger than the entire sample average plus this many standard deviations, it will be considered a high-expression ROI/spot/cell."},

                //STDiff
                "stdiff_non_spatial_samples": {"title": "Select samples", "text": "Select or de-select the samples on which to run the differential expression analysis. Most users might want to keep all samples selected. In some cases, users might want to run the analysis on subsets of samples due to different tissue domain annotations."},
                "stdiff_non_spatial_type_of_test": {"title": "Type of test", "text": "The type of statistical test to apply when identifying differentially expressed genes. The Wilcoxon’s test is a non-parametric option commonly used in single-cell differential expression analysis. The T-test and mixed model options are best suited for cases when each domain has high number of ROIs/spots/cells. (tens to thousands)."},
                "stdiff_non_spatial_pairwise": {"title": "Pairwise comparisons", "text": "If checked, gene expression from only ROIs/spots/cells from two domains are tested a time (“pairwise testing” mode). If unchecked, the “marked detection” mode is activated, where ROIs/spots/cells from each domain are compared against the spots from the rest of the sample. Most users might want to leave this unchecked to find genes that “transcriptomically define” each domain."},
                "stdiff_non_spatial_annotation": {"title": "Annotation to test", "text": "The clustering solution generated by STclust (Spatial domain detection module). To select the best option here, refer to the Spatial domain detection module to see which clustering solution best represents the tissue domains in the samples. If different solutions fit different samples, then the differential expression should be run more than once, and results saved each time as spreadsheets."},
                "stdiff_non_spatial_cluster": {"title": "Cluster annotations to test", "text": "The annotations/domains to be included in the analysis. Domains not specified here, will not have differentially expressed genes in the results."},
                "stdiff_non_spatial_genes": {"title": "Number of most variable genes to use", "text": "The number of genes to be tested for differential expression. The genes are selected using the vst function from the R package Seurat. The results will contain at most this number of genes per comparison."},

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

                //PhenotypingSTdeconvolveLDA
                "stdeconvolve_ldatopics": {"title": "Number of topics", "text": "Select a range of LDA topics to test. For example, selecting from 5 to 10 results in 6 different LDA models with 5, 6, 7, 8, 9, and 10 topics."},
                "stdeconvolve_remove_mito": {"title": "Remove mitochondrial genes?", "text": "Whether to remove mitochondrial genes from the data set during LDA model fitting. This option only works if mitochondrial gene names start with the letters “MT-” (e.g., Visium). The option only affects STdeconvolve."},
                "stdeconvolve_remove_ribo": {"title": "Remove ribosomal genes?", "text": "Whether to remove ribosomal genes from the data set during LDA model fitting. This option only works if ribosomal gene names start with the letters “RP”, followed by an “L” or “S” (e.g., Visium). The option only affects STdeconvolve."},
                "stdeconvolve_n_variablegenes": {"title": "Number of variable genes", "text": "Used this many variable genes to fit LDA models."},

                //PhenotypingBioIdentitiesSuggestedK
                "FORSUGGESTEDKBOXANDACCORDIONBUTTON": {"title": "Change suggested LDA model", "text": "If the suggested LDA model does not represent the expected number, enter here the number of topics in the model that best represents the sample."},
                //PhenotypingBioIdentities
                "stdeconvolve_genesignature": {"title": "Gene signature collection", "text": "Select a built-in collection of gene signatures to use in GSEA and biologically identify topics. The built-in collections contain gene sets to identify major cell types."},
                "stdeconvolve_qvalue": {"title": "GSEA q-value", "text": "Gene sets with GSEA q-value higher than this threshold will not be displayed in the results."},
                "stdeconvolve_sctrpie_r": {"title": "Size of piecharts", "text": "Controls the size of the individual piecharts in the spatial plot."},
                "stdeconvolve_color_palette": {"title": "Color palette", "text": "Select the name of a color palette for the spatial plots."},

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
