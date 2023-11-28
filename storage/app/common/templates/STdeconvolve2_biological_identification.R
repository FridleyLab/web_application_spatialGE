#{HEADER}#

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
celltype_markers = readRDS('#{celltype_markers}#.RDS')
# OR
# Take CSV from user
#celltype_markers_csv = read.csv('#{celltype_markers}#.csv', check.names=F)
#celltype_markers = lapply(1:ncol(celltype_markers_csv), function(i){
#  return(celltype_markers_csv[[i]][celltype_markers_csv[[i]] != ''])})
#names(celltype_markers) = colnames(celltype_markers_csv)
#rm(celltype_markers_csv) # Clean env

# Read suggested K
# Capture user-selected Ks and replace values as needed
suggested_k = read.csv('./stdeconvolve_selected_k.csv')
all_ldas = readRDS('./stdeconvolve_lda_models.RDS')

col_pal = as.vector(khroma::color(color_pal, force=T)(length(celltype_markers)))
names(col_pal) = names(celltype_markers)
col_pal = c(col_pal, unknown='gray40')

ps = list()
sctr_p = list()
topic_ann = list()
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
    celltype_annotations[['results']][[j]] %>% dplyr::select(-edge) %>%
      tibble::rownames_to_column('gene_set') %>%
      write.csv(., paste0('./gsea_results_', i, '_', j, '.csv'), row.names=F)
  })

  # Change NAs to 'unknown'
  celltype_annotations[['predictions']][is.na(celltype_annotations$predictions)] = 'unknown'

  # Save selected annotation per topic
  topic_ann = tibble::enframe(celltype_annotations[['predictions']])
  colnames(topic_ann) = c('topic', 'annotation')
  write.csv(topic_ann, paste0('./topic_annotations_', i, '.csv'), row.names=F)

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
  col_pal_tmp = unlist(lapply(1:length(celltype_annotations[['predictions']]), function(t){
    topic_col = col_pal[names(col_pal) == celltype_annotations[['predictions']][t]]
    names(topic_col) = paste0(names(celltype_annotations[['predictions']][t]), ' (', names(topic_col), ')')
    return(topic_col)
  }))

  cols_prop = colnames(decon_prop_sctr %>% dplyr::select(-c('libname', 'dummycol', 'xpos', 'ypos', 'radius')))
  sctr_p[[i]] = ggplot() +
    scatterpie::geom_scatterpie(data=decon_prop_sctr, aes(x=xpos, y=ypos, group=libname, r=radius), color=NA, cols=cols_prop) +
    ggtitle(i) +
    scale_fill_manual(values=col_pal_tmp) +
    guides(fill=guide_legend(nrow=3)) +
    scale_y_reverse() +
    coord_equal() +
    theme_void() +
    theme(legend.position="bottom",
          legend.title=element_blank())

 rm(cols_prop, col_pal_tmp, decon_prop_sctr, celltype_annotations) # Clean env
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

write.table(plotnames, 'stdeconvolve2_scatterpie_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
