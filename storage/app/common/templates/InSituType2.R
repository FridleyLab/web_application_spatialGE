
#{HEADER}#

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
master_color_p = get_color_palette(color_pal, length(unique(sup[["clust"]])))
names(master_color_p) = unique(sup[["clust"]])

# Create cell type spatial plots
ctype_p = STplot(insitutype_stlist, plot_meta="insitutype_cell_types", ptsize=ptsize, color_pal=master_color_p)

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

# Iterate over STlist and merge transformed counts... (Hoping this doesnt burn the memory)
# Subset genes to those in Insitutype object cell profiles
i = names(insitutype_stlist@tr_counts)[1]
merged_cts = insitutype_stlist@tr_counts[[i]]
merged_cts = merged_cts[rownames(sup[['profiles']]), ]
colnames(merged_cts) = paste0(gsub('_fov[0-9]+$', '', i), '_', colnames(merged_cts)) # Add slide IDs
if(length(insitutype_stlist@tr_counts) > 1){
  for(i in names(insitutype_stlist@tr_counts)[-1]){
    df_tmp = insitutype_stlist@tr_counts[[i]]
    colnames(df_tmp) = paste0(gsub('_fov[0-9]+$', '', i), '_', colnames(df_tmp))
    common_tmp = intersect(rownames(merged_cts), rownames(df_tmp))
    merged_cts = merged_cts[common_tmp, ]
    df_tmp = df_tmp[common_tmp, ]
    merged_cts = cbind(merged_cts, df_tmp)

    rm(df_tmp, common_tmp) # Clean env
  }
}

# Scale genes before PCA input
merged_cts_scl = scale(Matrix::t(merged_cts))

# Calculate principal components
pca_obj = prcomp(merged_cts_scl, scale.=F, center=F)

# Calculate UMAP embeddings
umap_obj = umap(pca_obj[['x']][, 1:30])

# Create data frame to plot UMAP embeddings
umap_df = as.data.frame(umap_obj[['layout']]) %>%
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
fl_p = InSituType::flightpath_plot(flightpath_result=fl_res, col=master_color_p) +
  ggplot2::labs(x='Dimension 1', y='Dimension 2', title='Clustering of cell type assignment probabilities (Flightpath plot)') +
  ggplot2::theme(panel.background=ggplot2::element_rect(fill=NA, color=NA),
                 axis.text.x=ggplot2::element_blank(), axis.ticks.x=ggplot2::element_blank(),
                 axis.text.y=ggplot2::element_blank(), axis.ticks.y=ggplot2::element_blank())
# OR
# fl_p = InSituType::flightpath_plot(insitutype_result=sup, col=master_color_p) # When data set contains NegPrb

####### UMAP - TAB 2
# Save plots
####### UMAP - SUBTAB 1
saveplot('insitutype_plot_umap_', umap_p)
#ggpubr::ggexport(filename='umap_insitutype.pdf', umap_p, width=1400, height=600)
####### UMAP - SUBTAB 2
saveplot('insitutype_plot_flightpath_', fl_p)
#ggpubr::ggexport(filename='flightpath_plot_insitutype.pdf', fl_p, width=14, height=6)


