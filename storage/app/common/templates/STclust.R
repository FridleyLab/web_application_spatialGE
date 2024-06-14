#{HEADER}#

##
# Spatial domain detection - STclust
#

# Load the package
library('spatialGE')
library('magrittr')

# User arguments
user_ws = #{ws}#
user_ks = #{ks}#
user_topgenes = #{topgenes}#
user_deepsplit = #{deepSplit}#
samplenames = #{samples}#

# Load STList
load("#{_stlist}#.RData")
STlist = #{_stlist}#

stclust_stlist = STclust(x=STlist,
                         ws=user_ws,
                         ks=user_ks,
                         topgenes=user_topgenes,
                         deepSplit=user_deepsplit)

# annot_variables used for differential expression and STgradient analyses
annot_variables = lapply(names(stclust_stlist@spatial_meta), function(i){
  #var_cols=grep('spagcn_|stclust_|insitutype_cell_types', colnames(stclust_stlist@spatial_meta[[i]]), value=T)
  var_cols=colnames(stclust_stlist@spatial_meta[[i]])[-c(1:5)]
  df_tmp = tibble::tibble()
  for(v in var_cols){
    cluster_values = unique(stclust_stlist@spatial_meta[[i]][[v]])
    df_tmp = dplyr::bind_rows(df_tmp, tibble::tibble(V1=i, V2=v, V3=v, V4=cluster_values, V5=cluster_values))
  }
  return(df_tmp) })
annot_variables = dplyr::bind_rows(annot_variables)

# Check if annot_variables file already exists, then keep annotations from other methods but remove those from STclust
if(file.exists('stdiff_annotation_variables_clusters.csv')){
  annot_variables_tmp = data.table::fread('stdiff_annotation_variables_clusters.csv', header=F)
  annot_variables_tmp = annot_variables_tmp[annot_variables_tmp[[2]] != annot_variables_tmp[[3]], ]
  if(nrow(annot_variables_tmp) > 0){
    annot_variables_tmp = annot_variables_tmp[!grepl('stclust_spw', annot_variables_tmp[[2]]), ]
    annot_variables = rbind(annot_variables, annot_variables_tmp)
  }
  rm(annot_variables_tmp) # Clean env
}
write.table(annot_variables, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')
save(stclust_stlist, file='stclust_stlist.RData')

#samplenames = names(stclust_stlist@spatial_meta)

ps = STplot(x=stclust_stlist, ks=user_ks, ws=user_ws, ptsize=2, txsize=14, color_pal='smoothrainbow', samples=samplenames)
n_plots = names(ps)
write.table(n_plots, 'stclust_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
for(p in n_plots) {
    saveplot(p, ps[[p]])
}

# Run STdiff to get DEGs and help manual annotation
start_t = Sys.time()
file_list <- list()
annot_test = unique(gsub(paste0('^', samplenames, '_', collapse='|'), '', names(ps)))
for(i in annot_test){
  deg_res = STdiff(stclust_stlist, samples=samplenames, annot=i, topgenes=50, test_type='wilcoxon')
  for(j in names(deg_res)){
    deg_tmp1 = deg_res[[j]] %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc)) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp2 = deg_res[[j]] %>% dplyr::filter(adj_p_val < 0.05) %>% dplyr::arrange(cluster_1, adj_p_val, avg_log2fc) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp = rbind(deg_tmp1, deg_tmp2) %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc))
    deg_tmp = deg_tmp[!duplicated(deg_tmp), ]
    file_name <- paste0('stclust_', j, '_', i, '_top_deg')
    write.csv(deg_tmp, paste0(file_name, '.csv'), quote=F, row.names=F)
    file_list <- c(file_list, list(file_name))
    rm(deg_tmp, deg_tmp1, deg_tmp2) # Clean env
  }
}
write.table(file_list, 'stclust_top_deg.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('Differential expression analysis completed in ', round(end_t, 2), ' min.'))
