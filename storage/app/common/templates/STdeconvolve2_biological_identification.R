#{HEADER}#


start_t = Sys.time()

library('STdeconvolve')
library('magrittr')
library('ggplot2')
library('ggrepel')
library('khroma')
library('spatialGE')

# User parameters
q_val = #{q_val}#
user_radius = #{user_radius}#
color_pal = '#{color_pal}#'

# USE NORMALIZED
load(file='normalized_stlist.RData')

# Read cell type markers (This file saved in spatialGE server and used by all users/projects)
#{included_celltype_markers}#celltype_markers = readRDS('#{celltype_markers}#.RDS')
# OR
# Take CSV or XLSX from user
#{uploaded_celltype_markers_csv}#celltype_markers_csv = read.csv('#{celltype_markers}#.csv', check.names=F)
#{uploaded_celltype_markers_xlsx}#celltype_markers_csv = openxlsx::read.xlsx('#{celltype_markers}#.xlsx', sep.names=' ')
#{uploaded_celltype_markers}#celltype_markers = lapply(1:ncol(celltype_markers_csv), function(i){
#{uploaded_celltype_markers}#  return(as.vector(na.omit(celltype_markers_csv[[i]][celltype_markers_csv[[i]] != ''])))})
#{uploaded_celltype_markers}#names(celltype_markers) = colnames(celltype_markers_csv)
#{uploaded_celltype_markers}#rm(celltype_markers_csv) # Clean env

# Read suggested K
# Capture user-selected Ks and replace values as needed
suggested_k = read.csv('./stdeconvolve_selected_k.csv')
all_ldas = readRDS('./stdeconvolve_lda_models.RDS')

ps = list()
sctr_p = list()
topic_props = list()
#sample_col_pal = list()
possible_celltypes = c()
for(i in suggested_k[['sample_name']]){
  opt_k = suggested_k[['suggested_k']][ suggested_k[['sample_name']] == i ]

  # Extract model with selected K
  optlda = optimalModel(all_ldas[[i]], opt=opt_k)

  # Get topic proportions and gene probabilities
  results = getBetaTheta(optlda, perc.filt=0.05, betaScale=1000)
  decon_prop = results[['theta']]
  colnames(decon_prop) = paste0('Topic_', colnames(decon_prop))
  decon_expr = results[['beta']]
  rownames(decon_expr) = paste0('Topic_', rownames(decon_expr))

  rm(opt_k, optlda, results) # Clean env

  set.seed(12345)
  # Apply GSEA to topics
  celltype_annotations = annotateCellTypesGSEA(beta=decon_expr, gset=celltype_markers, qval=q_val)

  # Save GSEA results to display
  lapply(names(celltype_annotations[['results']]), function(j){
    celltype_annotations[['results']][[j]] %>% dplyr::select(-edge) %>% dplyr::filter(sscore > 0) %>%
      tibble::rownames_to_column('gene_set') %>%
      write.csv(., paste0('gsea_results_', i, '_', j, '.csv'), row.names=F)
    # Save all possible cell types according to GSEA results
    #possible_celltypes = unique(append(possible_celltypes, rownames(celltype_annotations[['results']][[j]])))
  })

  # Change NAs to 'unknown'
  celltype_annotations[['predictions']][is.na(celltype_annotations$predictions)] = 'unknown'

  # Save all possible cell types according to GSEA results to create color palette
  possible_celltypes = unique(append(possible_celltypes,
                                     unlist(lapply(names(celltype_annotations[['predictions']]), function(j){
                                       return(celltype_annotations[['predictions']][[j]])
                                     }))))

  # Save selected annotation per topic
  topic_ann = tibble::enframe(celltype_annotations[['predictions']])
  colnames(topic_ann) = c('topic', 'annotation')
  topic_ann[['new_annotation']] = topic_ann[['annotation']]
  write.csv(topic_ann, paste0('topic_annotations_', i, '.csv'), row.names=F)

  # Find gene markers defined as those with highest log-fold change per topic from
  # the comparison of a given topic to the others' average
  ps[[i]] = list()
  for(celltype in colnames(decon_prop)){
    ## highly expressed in cell-type of interest (expression > 1)
    highgexp = names(which(decon_expr[celltype,] > 1))
    ## high log2(fold-change) compared to other deconvolved cell-types
    log2fc = sort(log2(decon_expr[celltype, highgexp]/colMeans(decon_expr[-which(rownames(decon_expr) == celltype), highgexp])), decreasing=TRUE)

    ## visualize the transcriptional profile
    dat = data.frame(values=as.vector(log2fc), genes=names(log2fc), order=seq(length(log2fc)))
    # If there are more than 30 genes identified as markers, then select the top 15 and bottom 15
    if(nrow(dat) > 30){
      dat_top = rbind(dat[1:15, ], dat[(nrow(dat)-14):nrow(dat), ])
      # Re-assign order of genes (as given by logFC)
      dat_top$order = seq(nrow(dat_top))
    } else{
      dat_top = dat
    }
    # Assign color to genes for plotting
    dat_top = dat_top %>%
      dplyr::mutate(col=dplyr::case_when(values < 0 ~ 'Up', values > 0 ~ 'Down', TRUE ~ 'black'))

    ps[[i]][[celltype]] = ggplot(data=dat_top) +
      geom_point(aes(x=order, y=values, color=col)) +
      geom_hline(yintercept=0, linetype="dashed") +
      ggtitle(paste0(celltype, ' (', celltype_annotations[['predictions']][ names(celltype_annotations[['predictions']]) == celltype], ')')) +
      labs(x="Gene expression rank", y="log2(FC)") +
      geom_text_repel(ggplot2::aes(x=order, y=values, label=genes), max.overlaps=20) +
      scale_y_continuous(expand=c(0, 0), limits=c(min(log2fc)-0.3, max(log2fc)+0.3)) +
      ylim((min(dat$value)-0.5), (max(dat$value)+0.5)) +
      scale_color_manual(values=c('blue', 'red')) +
      theme(plot.title=element_text(size=15, face="bold"), legend.position='none',
            panel.background=element_rect(color='black', fill=NULL))

    rm(highgexp, log2fc, dat, dat_top) # Clean env
  }

  # Create scatterpies
  # Add dummy column if only one cell type present in sample
  decon_prop_sctr = as.data.frame(decon_prop)
  # Assign cell names instead of topics
  colnames(decon_prop_sctr) = as.vector(unlist(lapply(colnames(decon_prop_sctr), function(t_name){
    c_name = celltype_annotations[['predictions']][names(celltype_annotations[['predictions']]) == t_name]
    tc_name = paste0(t_name, ' (', c_name, ')')
    return(tc_name)})))

  decon_prop_sctr = decon_prop_sctr %>%
    tibble::rownames_to_column(var='libname') %>%
    tibble::add_column(dummycol=0) %>%
    tibble::add_column(radius=as.numeric(user_radius)) %>%
    dplyr::left_join(normalized_stlist@spatial_meta[[i]] %>%
                       dplyr::select(c('libname', 'ypos', 'xpos')), by='libname')

  # Change color palette names
  # col_pal_tmp = unlist(lapply(1:length(celltype_annotations[['predictions']]), function(t){
  #   topic_col = col_pal[names(col_pal) == celltype_annotations[['predictions']][t]]
  #   names(topic_col) = paste0(names(celltype_annotations[['predictions']][t]), ' (', names(topic_col), ')')
  #   return(topic_col)
  # }))

  cols_prop = colnames(decon_prop_sctr %>% dplyr::select(-c('libname', 'dummycol', 'xpos', 'ypos', 'radius')))
  sctr_p[[i]] = ggplot() +
    scatterpie::geom_scatterpie(data=decon_prop_sctr, aes(x=xpos, y=ypos, group=libname, r=radius), color=NA, cols=cols_prop) +
    ggtitle(i) +
    #scale_fill_manual(values=col_pal_tmp) +
    guides(fill=guide_legend(ncol=3)) +
    scale_y_reverse() +
    coord_equal() +
    theme_void() +
    theme(legend.position="bottom", legend.title=element_blank())

  # Distribute rows of topics if more than 9 (so that they dont get cropped out
  # if(length(celltype_annotations[['predictions']]) > 9 & length(celltype_annotations[['predictions']]) <= 12){
  #   sctr_p[[i]] = sctr_p[[i]] +
  #     guides(fill=guide_legend(nrow=4))
  # } else if(length(celltype_annotations[['predictions']]) > 12 ){
  #   sctr_p[[i]] = sctr_p[[i]] +
  #     guides(fill=guide_legend(nrow=5))
  # }

  # Save per-spot topic proportions in case user decides to change plots
  topic_props[[i]] = decon_prop_sctr
  # Save color palette
  #sample_col_pal[[i]] = col_pal_tmp

  rm(decon_expr, decon_prop, cols_prop, #col_pal_tmp,
     decon_prop_sctr, celltype_annotations, topic_ann) # Clean env
}

# Create base color palette
col_pal = sample(as.vector(khroma::color(color_pal, force=T)(length(possible_celltypes))))
names(col_pal) = possible_celltypes
if(any(names(col_pal) == 'unknown')){
  col_pal[['unknown']] = 'gray40'
} else{
  col_pal = c(col_pal, unknown='gray40')
}
# Save general color palette
saveRDS(col_pal, 'general_color_palette_stdeconvolve.RDS')

# Change color palette names per sample
# Also change colors in scatterpies and save sample color palette
sample_col_pal = list()
for(i in names(topic_props)){
  topics_tmp = colnames(topic_props[[i]] %>% dplyr::select(-c('libname', 'dummycol', 'radius', 'ypos', 'xpos')))
  sample_col_pal[[i]] = c()
  for(j in topics_tmp){
    topic_cell_tmp = gsub('^Topic_[0-9]+ \\(', '', j) %>% gsub('\\)$', '', .)
    sample_col_pal[[i]] = append(sample_col_pal[[i]], col_pal[[topic_cell_tmp]])
    rm(topic_cell_tmp) # Clean env
  }
  names(sample_col_pal[[i]]) = topics_tmp
  rm(topics_tmp) # Clean env

  sctr_p[[i]] = sctr_p[[i]] + scale_fill_manual(values=sample_col_pal[[i]])
}



# Save log-fold change plots
plotnames = list()
for(i in names(ps)){
  for(j in names(ps[[i]])){
    plotname = paste0('topic_logfc_', i, '_', j)
    saveplot(plotname, ps[[i]][[j]])
    plotnames = c(plotnames, list(plotname))
  }
}
write.table(plotnames, 'stdeconvolve2_logfold_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Save scatterpie plots
plotnames = list()
for(i in names(sctr_p)){
    plotname = paste0('spatial_topic_proportions_', i)
    saveplot(plotname, sctr_p[[i]])
    plotnames = c(plotnames, list(plotname))
}

# Scatterpie AND tissue images
#for(sample in list($samples_with_tissue)) {
#for(i in list(samples_with_tissue)) {
#  if(grepl(i, names(sctr_p), fixed=TRUE)) {
#    tp = cowplot::ggdraw() + cowplot::draw_image(paste0(i,'/spatial/', i, '.png'))
#    ptp = ggpubr::ggarrange(sctr_p[[i]], tp, ncol=2)
    #{$this->getExportFilesCommands("paste0(p, '-sbs')", 'ptp', 1400, 600)}
#  }
#}

write.table(plotnames, 'stdeconvolve2_scatterpie_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Save logFC plots in case changes to title are required
saveRDS(ps, './topic_logfc_all_plots.RDS')

saveRDS(sample_col_pal, './color_palette_stdeconvolve.RDS')

# Save per-spot topic proportions in case user decides to change plots
saveRDS(topic_props, './topic_proportions_all_samples.RDS')

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('STdeconvolve [part 2] finished. ', round(as.vector(end_t), 2), ' min\n'))
