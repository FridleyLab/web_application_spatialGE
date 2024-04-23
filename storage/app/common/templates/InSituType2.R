
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

