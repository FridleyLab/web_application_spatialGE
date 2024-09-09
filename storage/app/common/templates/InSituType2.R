##
# Insitutype (Part 2 - STdiff for selected FOVs accompanying plots)
#

genesis_t = Sys.time() # Log time

library('spatialGE')
library('magrittr')
# Select samples for plotting and differential expression analysis
samplenames = #{_samples}#

# Load Insitutype stclust STlist
load("stclust_stlist.RData")

STlist = stclust_stlist

# Differentially expressed genes to assist with manual annotation
start_t = Sys.time()
file_list <- list()
deg_res = STdiff(STlist, samples=samplenames, annot='insitutype_cell_types', topgenes=50, test_type='wilcoxon')
for(j in names(deg_res)){
    deg_tmp1 = deg_res[[j]] %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc)) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp2 = deg_res[[j]] %>% dplyr::filter(adj_p_val < 0.05) %>% dplyr::arrange(cluster_1, adj_p_val, avg_log2fc) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp = rbind(deg_tmp1, deg_tmp2) %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc))
    deg_tmp = deg_tmp[!duplicated(deg_tmp), ]
    file_name <- paste0('insitutype_cell_types_insitutype_plot_spatial_', j, '_top_deg')
    write.csv(deg_tmp, paste0(file_name, '.csv'), quote=F, row.names=F)
    file_list <- c(file_list, list(file_name))
    rm(deg_tmp, deg_tmp1, deg_tmp2) # Clean env
}

write.table(file_list, 'insitutype_cell_types_top_deg.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

apocalypse_t = difftime(Sys.time(), genesis_t, units='min')
cat(paste0('Differential expression analysis completed in ', round(apocalypse_t, 2), ' min.'))

