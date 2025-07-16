
options(future.globals.maxSize=(4000*1024^2))

# User ARGS:
method='#{method}#'
scale_f=#{scale_f}#

# Load the package
library('spatialGE')

setwd('/spatialGE')

# Load filtered STList
load(file='#{stlist}#.RData')

normalized_stlist = transform_data(#{stlist}#, method=method, scale_f=scale_f, cores = 4)

save(normalized_stlist, file='normalized_stlist.RData')

gene_names = unique(unlist(lapply(normalized_stlist@counts, function(i){ genes_tmp = rownames(i) })))
write.table(gene_names, 'genesNormalized.csv', sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

#max_var_genes PCA
pca_max_var_genes = min(unlist(lapply(normalized_stlist@counts, nrow)))
write.table(pca_max_var_genes, 'pca_max_var_genes.csv', sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Extract data for plots
source('./Normalization_plot_counts_data.R')
plot_data = plot_summary_counts_data(x=normalized_stlist, samples=NULL, data_type='tr')
plot_data_raw = plot_summary_counts_data(x=normalized_stlist, samples=NULL, data_type='raw')

# Write data to text
lapply(1:length(plot_data), function(i){
  # Save counts discriminated by gene
  name_tmp = names(plot_data)[i]
  data.table::fwrite(plot_data[[name_tmp]],
                     paste0('normalized_plots_per_gene_counts_data_sample_', name_tmp, '.csv'),
                     sep=',', row.names=FALSE, quote=T)
  data.table::fwrite(plot_data_raw[[name_tmp]],
                     paste0('raw_plots_per_gene_counts_data_sample_', name_tmp, '.csv'),
                     sep=',', row.names=FALSE, quote=T)
  # Save total counts by expression
  plot_data[[name_tmp]] %>%
    dplyr::group_by(value) %>%
    dplyr::summarize(count=sum(count)) %>%
    dplyr::ungroup() %>%
    dplyr::select(c('value', 'count')) %>%
    data.table::fwrite(.,
                       paste0('normalized_plots_counts_data_sample_', name_tmp, '.csv'),
                       sep=',', row.names=FALSE, quote=T)
  plot_data_raw[[name_tmp]] %>%
    dplyr::group_by(value) %>%
    dplyr::summarize(count=sum(count)) %>%
    dplyr::ungroup() %>%
    dplyr::select(c('value', 'count')) %>%
    data.table::fwrite(.,
                       paste0('raw_plots_counts_data_sample_', name_tmp, '.csv'),
                       sep=',', row.names=FALSE, quote=T)
})

