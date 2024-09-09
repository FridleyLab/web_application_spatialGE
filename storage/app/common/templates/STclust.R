#{HEADER}#

##
# Spatial domain detection - STclust
#

# Load the package
library('spatialGE')
library('magrittr')

##
# data_quilt_cluster: Extracts data for quilt plots of spatial domains or cell types
# x an STlist
# ks a vector with k values or 'dtc' (only if STclust)
# ws a vector with w values (only if STclust)
# deepSplit a number indicating the deepSplit value used (only if STclust)
# meta a vector with clustering solution names from x@spatial_meta
# samples a vector of numbers indicating the ST samples to plot, or their
# sample names. If vector of numbers, it follow the order of `names(x@counts)`.
# If NULL, the function plots all samples
#
# @return a list with data frames containing the data
#
data_quilt_cluster = function(x=NULL, ks='dtc', ws=NULL, annot=NULL, samples=NULL){
  # The parameters ws and ks will be ignored if annot is specified
  # If annot is empty and k and w are specified, construct column name
  if(!is.null(annot) & (!is.null(ks) | !is.null(ws))){
    warning('The arguments k, w, and annot have been specified. Only annot will be used.')
  } else if(is.null(annot) & (is.null(ks) | is.null(ws))){
    stop('spatialGE_web -> data_quilt_cluster: The argument annot is empty. Please specify both k and w (STclust parameters).')
  } else{
    annot = annot_from_kws(w=ws, k=ks, deepSplit=deepSplit)
  }
  # Reset k, w, and deepSplit values (avoid potential problems downstream by relying only on annot)
  ks = NULL
  ws = NULL
  deepSplit = NULL

  # Define which samples to plot
  if(is.null(samples)){
    samples = names(x@spatial_meta)
  } else{
    if(is.numeric(samples)){
      samples = names(x@spatial_meta)[samples]
    }
    if(length(grep(paste0(samples, collapse='|'), names(x@spatial_meta))) == 0){
      stop('spatialGE_web -> data_quilt_cluster: The requested samples are not present in the STlist spatial metadata.') # Error if samples were not found in STlist
    }
  }

  # Extract spatial meta data
  spatial_meta = x@spatial_meta[samples]

  x = NULL # Offload STlist

  # Extract genes and transpose expression data (to get genes in columns)
  # Also join coordinate data
  for(i in samples){
    annot_tmp = colnames(spatial_meta[[i]])[colnames(spatial_meta[[i]]) %in% annot]
    annot_tmp = c(colnames(spatial_meta[[i]])[c(3,2)], annot_tmp)
    if(length(annot_tmp) >= 3){
      spatial_meta[[i]] = spatial_meta[[i]][, colnames(spatial_meta[[i]]) %in% annot_tmp]
    } else{
      # Remove samples for which none of the requested genes are available
      spatial_meta = spatial_meta[grep(i, names(spatial_meta), value=T, invert=T)]
      warning(paste0('The requested annotations are not available for sample ', i, '.'))
    }
    rm(annot_tmp) # Clean env
  }

  # Test if requested data slot is available.
  if(rlang::is_empty(spatial_meta)) {
    stop("spatialGE_web -> The requested data was not found in this STlist.")
  }

  return(spatial_meta)
}

##
# get_annot_from_kw
# Construct column name to be queried in @spatial_meta
#
annot_from_kws = function(ws=NULL, ks=NULL, deepSplit=NULL){
  annot = c()
  for(k in ks){
    for(w in ws){
      # Construct column name from w and k
      annot_tmp = paste0('stclust_spw', as.character(w))
      if(k == 'dtc'){
        # If dtc, the deepSplit is required
        if(is.null(deepSplit)){
          stop('spatialGE_web -> annot_from_kws: If k=\"dtc\", then specify deepSplit.')
        } else if(is.logical(deepSplit)){
          if(deepSplit){
            annot_tmp = paste0(annot_tmp, '_dsplTrue')
          } else{
            annot_tmp = paste0(annot_tmp, '_dsplFalse')
          }
        } else if(is.numeric(deepSplit)){
          annot_tmp = paste0(annot_tmp, '_dspl', deepSplit)
        } else{
          stop('spatialGE_web -> annot_from_kws: Please enter a valid deepSplit value.')
        }
      } else if(!is.numeric(k)){
        stop('spatialGE_web -> annot_from_kws: The specified k value is not numeric.')
      } else{
        annot_tmp = paste0(annot_tmp, '_k', as.character(k))
      }
      annot = c(annot, annot_tmp)
    }
  }
  return(annot)
}


# User arguments
user_ws = #{ws}#
user_ks = #{ks}#
user_topgenes = #{topgenes}#
user_deepsplit = #{deepSplit}#
samplenames = #{samples}#

# Load STlist
load("#{_stlist}#.RData")
STlist = #{_stlist}#

stclust_stlist = STclust(x=STlist,
                         samples=samplenames,
                         ws=user_ws,
                         ks=user_ks,
                         topgenes=user_topgenes,
                         deepSplit=user_deepsplit)

# annot_variables used for differential expression and STdiff/STgradient analyses
annot_variables = lapply(names(stclust_stlist@spatial_meta), function(i){
  var_cols=colnames(stclust_stlist@spatial_meta[[i]])[-c(1:5)]
  df_tmp = tibble::tibble()
  for(v in var_cols){
    cluster_values = unique(stclust_stlist@spatial_meta[[i]][[v]])
    df_tmp = dplyr::bind_rows(df_tmp, tibble::tibble(V1=i, V2=v, V3=v, V4=cluster_values, V5=cluster_values))
  }
  return(df_tmp) })
annot_variables = dplyr::bind_rows(annot_variables) %>% dplyr::filter(stringr::str_detect(V2, '^stclust_spw'))

# Check if annot_variables file already exists, then keep annotations from other methods but remove those from STclust
if(file.exists('stdiff_annotation_variables_clusters.csv')){
  annot_variables_tmp = data.table::fread('stdiff_annotation_variables_clusters.csv', header=F)
  annot_variables_tmp = annot_variables_tmp[!grepl('^stclust_spw', annot_variables_tmp[['V2']]), ]
  if(nrow(annot_variables_tmp) > 0){
    annot_variables = rbind(annot_variables_tmp, annot_variables)
  }
  rm(annot_variables_tmp) # Clean env
}
write.table(annot_variables, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')
save(stclust_stlist, file='stclust_stlist.RData')

#samplenames = names(stclust_stlist@spatial_meta)

#ps = STplot(x=stclust_stlist, ks=user_ks, ws=user_ws, ptsize=2, txsize=14, color_pal='smoothrainbow', samples=samplenames)
# n_plots = names(ps)
# write.table(n_plots, 'stclust_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
# for(p in n_plots) {
#     saveplot(p, ps[[p]])
# }

# Extract data frames
stplot_dfs = data_quilt_cluster(x=stclust_stlist, ks=user_ks, ws=user_ws, samples=samplenames)
# Save to text file
# file_list <- list()
lapply(1:length(stplot_dfs), function(i){
  file_name = paste0(names(stplot_dfs)[i], '_stclust_quilt_data.csv')
  data.table::fwrite(stplot_dfs[[i]], file=file_name, quote=F, row.names=F)
#   file_list <- c(file_list, list(file_name))
})
# write.table(file_list, 'stclust_quilt_data.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Run STdiff to get DEGs and help manual annotation
start_t = Sys.time()
file_list <- list()
annot_test = annot_from_kws(ws=user_ws, ks=user_ks, deepSplit=deepSplit)
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
