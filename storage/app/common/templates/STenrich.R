#{HEADER}#

##
# Spatial gene set enrichment (STenrich)
#


# User options
permutations = #{permutations}#
num_sds = #{num_sds}#
min_spots = #{min_spots}#
min_genes = #{min_genes}#
seed = #{seed}#

# Load the package
library('spatialGE')
library('magrittr')
library('ComplexHeatmap')

# This first part to parse the input files containing gene sets
# These lines produce a named list to be passed to the `gene_sets` parameter
# in the STenrich function
fp = '#{gene_sets_file}#'
pws_raw = readLines(fp)
pws = lapply(pws_raw, function(i){
  pw_tmp = unlist(strsplit(i, split='\\t'))
  pw_name_tmp = pw_tmp[1]
  pw_genes_tmp = pw_tmp[-c(1:2)]
  return(list(pw_name=pw_name_tmp,
              pw_genes=pw_genes_tmp))
})
rm(pws_raw, fp)

pws_names = c()
for(i in 1:length(pws)){
  pws_names = append(pws_names, pws[[i]][['pw_name']])
  pws[[i]] = pws[[i]][['pw_genes']]
}
names(pws) = pws_names

# Load normalized STList
load("#{_stlist}#.RData")
normalized_stlist = #{_stlist}#

# Run STenrich
sp_enrichment = STenrich(normalized_stlist,
                         gene_sets=pws,
                         reps=permutations,
                         num_sds=num_sds,
                         min_units=min_spots,
                         min_genes=min_genes,
                         seed=seed,
                         cores=NULL)

# Get workbook with results (samples in spreadsheets)
# Similar output to STdiff
openxlsx::write.xlsx(sp_enrichment, file='stenrich_results.xlsx')

# Each sample as a CSV
lapply(names(sp_enrichment), function(i){
  write.csv(sp_enrichment[[i]], paste0('stenrich_', i, '.csv'), row.names=T, quote=F)
})

# Make dataframe with all combined results
sp_enrichment = dplyr::bind_rows(sp_enrichment)

# Create heatmap of p-values
hm_mtx = sp_enrichment %>%
  dplyr::select(c('sample_name', 'gene_set', 'adj_p_value')) %>%
  dplyr::arrange(sample_name) %>%
  tidyr::pivot_wider(names_from='sample_name', values_from='adj_p_value') %>%
  tibble::column_to_rownames(var='gene_set') %>%
  as.matrix()

# Order by median p-values
hm_mtx = hm_mtx[order(apply(hm_mtx, 1, mean, na.rm=T), decreasing=F), , drop=F]
hm_mtx = as.data.frame(hm_mtx)
hm_mtx = hm_mtx %>% tibble::rownames_to_column(var='gene_set')

# Save matrix for heatmap
write.csv(hm_mtx, file='stenrich_heatmap_matrix.csv', row.names=F, quote=F)


# If too many gene sets, then select those with the most standard deviation in p-values
if(nrow(hm_mtx) <= 30){
  hm_mtx = hm_mtx[order(apply(hm_mtx, 1, median, na.rm=T)), , drop=F]
} else{
  hm_mtx = hm_mtx[order(apply(hm_mtx, 1, sd, na.rm=T), decreasing=T), , drop=F]
  hm_mtx = hm_mtx[1:30, , drop=F]
  hm_mtx = hm_mtx[order(apply(hm_mtx, 1, median, na.rm=T)), , drop=F]
}

# Create data frame for heatmap annotation
df_tmp = normalized_stlist@sample_meta
if(ncol(df_tmp) >= 2){
  colnames(df_tmp)[1] = 'x_sample_name'
} else{
  df_tmp[['sample_names']] = df_tmp[[1]]
  colnames(df_tmp)[1] = 'x_sample_name'
}
df_tmp = df_tmp %>%
  dplyr::filter(x_sample_name %in% colnames(hm_mtx)) %>%
  dplyr::mutate(dplyr::across(dplyr::everything(), as.character)) %>%
  tibble::column_to_rownames(var='x_sample_name') %>%
  dplyr::arrange(.[[1]])

hm_mtx = hm_mtx[, match(rownames(df_tmp), colnames(hm_mtx)), drop=F]
hm_annot = ComplexHeatmap::HeatmapAnnotation(df=df_tmp)

# Generate title for heatmap
hm_title = paste0('STgradient FDR-adjusted p-values')

# Generate heatmap
hm = ComplexHeatmap::Heatmap(hm_mtx,
                             cluster_columns=F,
                             cluster_rows=F,
                             show_row_names=T,
                             column_title=hm_title,
                             col=circlize::colorRamp2(c(0, 0.05, 1), c("firebrick1", "deepskyblue2", "deepskyblue2")),
                             bottom_annotation=hm_annot,
                             heatmap_legend_param=list(title="STenrich\nFDR"))

# Save heatmap
#pdf('../../../results_and_intermediate_files/spatial_gene_set_enrichment/visium/stenrich_results_summary_heatmap.pdf', width=10, height=12)
hm_plot = draw(hm, padding=unit(c(2, 2, 2, 60), "mm"),
     annotation_legend_side="bottom",
     heatmap_legend_side="bottom",
     merge_legend=T)
#dev.off()

saveplot('stenrich_heatmap', hm_plot, 800, 1000)
