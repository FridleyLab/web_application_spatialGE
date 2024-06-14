
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


# Read previsouly generated UMAP reduction
load('insitutype_umap_object.RData')

# Create data frame to plot UMAP embeddings
umap_df = as.data.frame(umap_obj) %>%
  dplyr::rename(UMAP1=1, UMAP2=2) %>%
  tibble::rownames_to_column('complete_cell_id') %>%
  dplyr::left_join(., as.data.frame(sup[['clust']]) %>%
                     dplyr::rename(insitutype_cell_types=1) %>%
                     tibble::rownames_to_column('complete_cell_id'), by='complete_cell_id')

# Create UMAP plot
umap_p = ggplot2::ggplot(umap_df) +
  ggplot2::geom_point(ggplot2::aes(x=UMAP1, y=UMAP2, color=insitutype_cell_types), size=0.1) +
  ggplot2::scale_color_manual(values=master_color_p) +
  ggplot2::guides(color=ggplot2::guide_legend(override.aes=list(size=1))) +
  ggplot2::labs(x='UMAP1', y='UMAP2') +
  ggplot2::theme(panel.background=ggplot2::element_rect(fill=NA, color=NA),
                 legend.key=ggplot2::element_rect(colour=NA, fill=NA),
                 axis.text.x=ggplot2::element_blank(), axis.ticks.x=ggplot2::element_blank(),
                 axis.text.y=ggplot2::element_blank(), axis.ticks.y=ggplot2::element_blank())

# Create flightpath plot
fl_res = InSituType::flightpath_layout(logliks=sup[['logliks']])
fp_col = data.frame(celltype=fl_res[['clust']]) %>%
  dplyr::left_join(., as.data.frame(master_color_p) %>% tibble::rownames_to_column('celltype'), by='celltype') %>%
  dplyr::select(2) %>% unlist() %>% as.vector()

fl_p = InSituType::flightpath_plot(flightpath_result=fl_res, col=fp_col) +
  ggplot2::labs(x='Dimension 1', y='Dimension 2', title='Clustering of cell type assignment probabilities (Flightpath plot)') +
  ggplot2::theme(panel.background=ggplot2::element_rect(fill=NA, color=NA),
                 axis.text.x=ggplot2::element_blank(), axis.ticks.x=ggplot2::element_blank(),
                 axis.text.y=ggplot2::element_blank(), axis.ticks.y=ggplot2::element_blank())
# OR
# fl_p = InSituType::flightpath_plot(insitutype_result=sup, col=master_color_p) # When data set contains NegPrb

saveplot('insitutype_umap', umap_p, 1400, 600)
saveplot('insitutype_flightpath', fl_p, 1400, 600)

apocalypse_t = difftime(Sys.time(), genesis_t, units='min')
cat(paste0('Plotting completed in ', round(apocalypse_t, 2), ' min.\n'))
