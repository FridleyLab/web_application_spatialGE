#{HEADER}#


start_t = Sys.time()

library('STdeconvolve')
library('magrittr')
library('ggplot2')
library('ggrepel')
library('khroma')
library('spatialGE')

color_pal = 'discreterainbow' # Same as in Step 2, no dropdown needed for the moment
col_pal = readRDS('./general_color_palette_stdeconvolve.RDS')

# Read logFC plots
ps = readRDS('./topic_logfc_all_plots.RDS')

# Read topic proportions
topic_props = readRDS('./topic_proportions_all_samples.RDS')

# Read color palette
sample_col_pal = readRDS('./color_palette_stdeconvolve.RDS')

# Read annotations and process changes
sample_annots = list.files('./', pattern='topic_annotations_', full.names=T)

sctr_p = list()
for(i in sample_annots){
  # Read annotation
  topic_annot = read.csv(i)
  topic_annot = topic_annot[which(topic_annot[[2]] != topic_annot[[3]]), ]
  if(nrow(topic_annot) > 0){
    # Get sample name
    sample_name = gsub('[_\\.\\/a-zA-Z0-9]+topic_annotations_', '', i) %>% gsub('\\.csv', '', .)

    # Make changes in logFC plot and scatterpie color palette
    for(topic in topic_annot[['topic']]){
      # Change name in sample color palette
      new_topic_name = paste0(topic, ' (', topic_annot[[3]][topic_annot[[1]] == topic], ')')

      # Use color of biological ID is possible
      bio_id = topic_annot[[3]][ topic_annot[[1]] == topic ]
      bio_id_present = grep(paste0('^', bio_id, '$'), names(col_pal), value=T)
      if(length(bio_id_present) > 0){
        sample_col_pal[[sample_name]][new_topic_name] = as.vector(col_pal[bio_id_present])
      } else if(any(grepl(paste0('^Topic_[0-9]+ \\(', bio_id, '\\)'), names(sample_col_pal[[sample_name]])))){ # See if ID already in sample palette
        # Get only first hit (other topics could have same ID)
        existing_tmp = grep(paste0('^Topic_[0-9]+ \\(', bio_id, '\\)'), names(sample_col_pal[[sample_name]]), value=T)
        sample_col_pal[[sample_name]][new_topic_name] = sample_col_pal[[sample_name]][existing_tmp][[1]]
        rm(existing_tmp) # Clean env
      } else{
        sample_col_pal[[sample_name]][new_topic_name] = sample(khroma::color(color_pal, force=T)(100), 1)
      }

      # Change title in logFC plot
      ps[[sample_name]][[topic]] = ps[[sample_name]][[topic]] + ggtitle(paste0(topic, ' (', topic_annot[[3]][topic_annot[[1]] == topic], ')'))

      # Change column name in proportion matrix
      colnames(topic_props[[sample_name]])[grep(paste0('^', topic, ' '), colnames(topic_props[[sample_name]]))] = new_topic_name

      rm(new_topic_name, bio_id, bio_id_present) # Clean env
    }

    # Names of columns in proportion matrix
    cols_prop = colnames(topic_props[[sample_name]] %>% dplyr::select(-c('libname', 'dummycol', 'xpos', 'ypos', 'radius')))
    # Plot scatterpies
    sctr_p[[sample_name]] = ggplot() +
      scatterpie::geom_scatterpie(data=topic_props[[sample_name]], aes(x=xpos, y=ypos, group=libname, r=radius), color=NA, cols=cols_prop) +
      ggtitle(sample_name) +
      scale_fill_manual(values=sample_col_pal[[sample_name]]) +
      guides(fill=guide_legend(ncol=3)) +
      scale_y_reverse() +
      coord_equal() +
      theme_void() +
      theme(legend.position="bottom", legend.title=element_blank())

    rm(cols_prop) # Clean env
  }
  rm(topic_annot) # Clean env
}

# Save log-fold change plots
plotnames = list()
for (i in names(ps)){
  for (j in names(ps[[i]])){
    plotname = paste0('topic_logfc_', i, '_', j)
    saveplot(plotname, ps[[i]][[j]])
    plotnames = c(plotnames, list(plotname))
  }
}
#write.table(plotnames, 'stdeconvolve2_logfold_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Save scatterpie plots
plotnames = list()
for (i in names(sctr_p)) {
  plotname <- paste0("spatial_topic_proportions_", i)
  saveplot(plotname, sctr_p[[i]])
  plotnames <- c(plotnames, list(plotname))
}
#write.table(plotnames, 'stdeconvolve2_scatterpie_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Scatterpie AND tissue images
#for(sample in list($samples_with_tissue)) {
for(i in list(samples_with_tissue)) {
  if(grepl(i, names(sctr_p), fixed=TRUE)) {
    tp = cowplot::ggdraw() + cowplot::draw_image(paste0(i,'/spatial/', i, '.png'))
    ptp = ggpubr::ggarrange(sctr_p[[i]], tp, ncol=2)
    #{$this->getExportFilesCommands("paste0(p, '-sbs')", 'ptp', 1400, 600)}
  }
}
  
# Read logFC plots
saveRDS(ps, './topic_logfc_all_plots.RDS')

# Save modified color palette
saveRDS(sample_col_pal, './color_palette_stdeconvolve.RDS')

# Save modified proportion matrix
saveRDS(topic_props, './topic_proportions_all_samples.RDS')

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('STdeconvolve [part 2] finished. ', round(as.vector(end_t), 2), ' min\n'))
