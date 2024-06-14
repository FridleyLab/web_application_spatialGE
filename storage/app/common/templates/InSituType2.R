
#{HEADER}#

genesis_t = Sys.time() # Log time

#{_color_palette_function}#

# USER PARAMETERS:
# Color palette
color_pal = "#{color_pal}#"
# Point size
ptsize = #{ptsize}#

library('InSituType')
library('spatialGE')
library('umap')
library('magrittr')

# Load Insitutype stclust STlist
load("stclust_stlist.RData")
# Load Insitutype results
load("insitutype_results.RData")

# Create color palette to share across quilt plots, UMAP, and other viz
master_color_p = get_color_palette(color_pal, length(unique(sup[['clust']])))
names(master_color_p) = unique(sup[['clust']])
master_color_p = append(master_color_p, c(unknown='gray50')) # Color for unclassified cells

# Create cell type spatial plots
ctype_p = STplot(stclust_stlist, samples=#{_samples}#, plot_meta="insitutype_cell_types", ptsize=ptsize, color_pal=master_color_p)

# List f available plots
list_of_plots = names(ctype_p)

####### SPATIAL PLOTS - TAB 1
# Print each plot
for(i in list_of_plots){
  # Save to file quilt plot + tissue image
  if(!is.null(ctype_p[[i]])){
    # COMMENTED BECAUSE WE SAID TISSUE IMAGES IN COSMX COULD BE PRINTED OUTSIDE OF R
    #tp = cowplot::ggdraw() + cowplot::draw_image(paste0('../../../results_and_intermediate_files/insitutype/spatial_plot_insitutype_', i, '.pdf'))
    #qptp = ggpubr::ggarrange(qp$MYC_sample_120d, tp, ncol=2)

    saveplot(paste0('insitutype_plot_spatial_', i), ctype_p[[i]])
    #ggpubr::ggexport(filename=paste0('../../../results_and_intermediate_files/insitutype/spatial_plot_insitutype_', i, '.pdf'), ctype_p[[i]], width=1400, height=600)
    #ggpubr::ggexport(filename=paste0('../../../results_and_intermediate_files/insitutype/spatial_plot_insitutype_', i, '.pdf'), ctype_p[[i]], width=14, height=6)
  }
}

# Differetially expressed genes to assist with manual annotation
start_t = Sys.time()
annot_test = unique(gsub(paste0('^', #{_samples}#, '_', collapse='|'), '', names(ctype_p)))
file_list <- list()
for(i in annot_test){
  deg_res = STdiff(stclust_stlist, samples=#{_samples}#, annot=i, topgenes=50, test_type='wilcoxon')
  for(j in names(deg_res)){
    deg_tmp1 = deg_res[[j]] %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc)) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp2 = deg_res[[j]] %>% dplyr::filter(adj_p_val < 0.05) %>% dplyr::arrange(cluster_1, adj_p_val, avg_log2fc) %>% dplyr::group_by(cluster_1) %>% dplyr::slice_head(n=10) %>% dplyr::ungroup()
    deg_tmp = rbind(deg_tmp1, deg_tmp2) %>% dplyr::arrange(cluster_1, adj_p_val, desc(avg_log2fc))
    deg_tmp = deg_tmp[!duplicated(deg_tmp), ]
    file_name <- paste0('insitutype_cell_types_insitutype_plot_spatial_', j, '_', i, '_top_deg')
    write.csv(deg_tmp, paste0(file_name, '.csv'), quote=F, row.names=F)
    file_list <- c(file_list, list(file_name))
    rm(deg_tmp, deg_tmp1, deg_tmp2) # Clean env
  }
}
write.table(file_list, 'insitutype_cell_types_top_deg.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('Differential expression analysis completed in ', round(end_t, 2), ' min.'))

