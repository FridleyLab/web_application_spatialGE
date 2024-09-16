#{HEADER}#

##
# spatialGE commands for spatial gradient detection - STgradient
#


# Load the package
library('spatialGE')
library('magrittr')
library('ComplexHeatmap')

# Load stclust/spagcn/insitutype STList
load("#{_stlist}#.RData")
stclust_stlist = #{_stlist}#

# Selection by user
annot_test = '#{annot}#'
samples_test = #{samples}#
topgenes = #{topgenes}#
ref = '#{ref}#'
exclude = #{exclude_string}#
out_rm = #{out_rm}#
limit = #{limit}#
distsumm = '#{distsumm}#'
min_nb = #{min_nb}#
robust = #{robust}#

if(is.null(samples_test)){
  samples_test = names(stclust_stlist@spatial_meta)
}

new_annot_test = c()
for(i in samples_test){
  if(!(annot_test %in% colnames(stclust_stlist@spatial_meta[[i]]))){
    master_ann = 'stdiff_annotation_variables_clusters.csv'
    master_ann = data.table::fread(master_ann, header=F) %>% dplyr::filter(V1 == i & V3 == annot_test)
    orig_annot = unique(master_ann[['V2']])
    for(j in orig_annot){
      annot_tmp = paste0(annot_test, '__&&__', j) # TO BE REMOVED ONCE RESTRICTIONS OF ANNOTATION NAMING ARE IN PLACE
      master_ann_tmp = master_ann[master_ann[['V2']] == j, c('V4', 'V5')]
      master_ann_tmp[['V4']] = as.character(master_ann_tmp[['V4']])
      colnames(master_ann_tmp) = c(j, annot_tmp)
      stclust_stlist@spatial_meta[[i]] = stclust_stlist@spatial_meta[[i]] %>%
        dplyr::left_join(., master_ann_tmp, by=j)

      new_annot_test = c(new_annot_test, annot_tmp)

      rm(master_ann_tmp, annot_tmp)
    }
  } else{
    new_annot_test = c(new_annot_test, annot_test)
  }
}
new_annot_test = unique(new_annot_test)

if(length(new_annot_test) == 0){
  stop('The requested annoations could not be found in any of the samples.')
}

all_res = tibble::tibble() # Store results temporarily for plotting
for(i_mod in new_annot_test){
  i = unlist(strsplit(i_mod, '__&&__', fixed=T))[1] # TO BE REMOVED ONCE RESTRICTIONS OF ANNOTATION NAMING ARE IN PLACE
  for(j in samples_test){
    if(i_mod %in% colnames(stclust_stlist@spatial_meta[[j]])){
      grad_res = STgradient(x=stclust_stlist,
                            samples=j,
                            topgenes=topgenes,
                            annot=i_mod,
                            ref=ref,
                            exclude=exclude,
                            out_rm=out_rm,
                            limit=limit,
                            distsumm=distsumm,
                            min_nb=min_nb,
                            robust=robust,
                            cores=1)

      ### Save non-spatial tests
      # Get workbook with results (samples in spreadsheets)
      openxlsx::write.xlsx(grad_res, file=paste0('./stgradients_results_', j, '.xlsx'))

      # Each sample as a CSV
      lapply(names(grad_res), function(i){
        write.csv(grad_res[[i]], paste0('./stgradients_', i, '.csv'), row.names=F, quote=F)
      })

      if(length(grad_res) > 0){
        # Compile all results in single table for plotting
        all_res = dplyr::bind_rows(all_res, grad_res[[1]])
      }
      rm(grad_res) # Clean env
    }
  }
}

# In case no results could be computed
if(nrow(all_res) == 0){
  warning('No results could be computed. Have you tried varying the min_nb?')
} else{
  # Remove avg|min from column names
  colnames(all_res) = gsub('^min_|^avg_', '', colnames(all_res))

  # Create heatmap of p-values
  # spearman_thr = 0.3 # FOR IMPLEMENTATION AS A USER OPTION IN THE FUTURE??
  hm_mtx = all_res %>%
    dplyr::filter(spearman_r_pval_adj < 0.05) %>%
    #dplyr::filter(spearman_r <= -spearman_thr | spearman_r >= spearman_thr) %>% # FOR IMPLEMENTATION AS A USER OPTION IN THE FUTURE??
    dplyr::select(sample_name, gene, spearman_r) %>%
    dplyr::arrange(sample_name) %>%
    tidyr::pivot_wider(names_from='sample_name', values_from='spearman_r') %>%
    tibble::column_to_rownames(var='gene') %>%
    as.matrix()

  # Order genes
  hm_mtx = hm_mtx[order(rowMeans(abs(hm_mtx), na.rm=T)), , drop=F]


  # Save matrix for heatmap
  hm_mtx = as.data.frame(hm_mtx)
  hm_mtx = hm_mtx %>% tibble::rownames_to_column('gene_name')
  write.csv(hm_mtx, 'stgradients_heatmap_matrix.csv', row.names=F, quote=F)

  # Create data frame for heatmap annotation
  df_tmp = stclust_stlist@sample_meta
  if(ncol(df_tmp) >= 2){
    colnames(df_tmp)[1] = 'x_sample_name'
  } else{
    df_tmp[['sample_names']] = df_tmp[[1]]
    colnames(df_tmp)[1] = 'x_sample_name'
  }
  df_tmp = df_tmp %>%
    dplyr::filter(x_sample_name %in% colnames(hm_mtx)) %>%
    dplyr::mutate(dplyr::across(dplyr::everything(), as.character)) %>%
    tibble::column_to_rownames(var='x_sample_name') %>%
    dplyr::arrange(.[[1]])

  # Make sure annotations and matrix are in the same order, the make annotation object
  hm_mtx = hm_mtx[, match(rownames(df_tmp), colnames(hm_mtx)), drop=F]
  hm_annot = ComplexHeatmap::HeatmapAnnotation(df=df_tmp)

  # Generate title for heatmap
  hm_title = paste0('Spearman coefficients (STgradient)\n', ifelse(distsumm == 'min', 'Minimum ', 'Average '), 'distance | p value < 0.05')

  # If too many genes in matrix, subset
  if(nrow(hm_mtx) > 30){
    hm_mtx = rbind(hm_mtx[1:15, , drop=F], hm_mtx[(nrow(hm_mtx)-14):nrow(hm_mtx), , drop=F])
    hm_title = paste0(hm_title, '\nTop and bottom 30 strongest correlations')
  }

  # Plot and save heatmap
  #pdf('../../../results_and_intermediate_files/stgradient/visium/stgradient_results_summary_heatmap.pdf', height=14)
  heatmap_plot = Heatmap(hm_mtx,
                         cluster_columns=F,
                         cluster_rows=F,
                         show_row_names=T,
                         column_title=hm_title,
                         heatmap_legend_param=list(title="Spearman's r"),
                         bottom_annotation=hm_annot)
  saveplot('stgradients_heatmap', heatmap_plot, 800, 1000)
  #dev.off()
}

