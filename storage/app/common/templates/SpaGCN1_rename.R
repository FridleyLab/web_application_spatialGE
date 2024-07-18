#{HEADER}#

##
# Renaming plots - SpaGCN
#

# Load the package
library('spatialGE')
library('magrittr')


# Affected plots
renamed_samples = #{_samples}#
renamed_ann = #{_annotations}#

# Load STList
load("#{_stlist}#.RData")
stclust_stlist = #{_stlist}#

# Read master annotation file
master_ann = 'stdiff_annotation_variables_clusters.csv'
#master_ann = '../../../results_and_intermediate_files/stclust/cosmx/stdiff_annotation_variables_clusters.csv'
master_ann = data.table::fread(master_ann, header=F)
master_ann = master_ann[ master_ann[[1]] %in% renamed_samples & master_ann[[2]] %in% renamed_ann & master_ann[[2]] != master_ann[[3]], ]

# ERROR IF COULDNT DETECT CHANGES IN ANNOTATION FILE
if(nrow(master_ann) == 0){
  stop('No new annotations were found...')
}

# Add new annotations to STlist
for(i in unique(master_ann[[1]])){
  df_tmp = master_ann[master_ann[[1]] == i, ]
  for(j in unique(df_tmp[[2]])){
    df_tmp2 = df_tmp[df_tmp[[2]] == j, ]
    colnames(df_tmp2)[c(4,5)] = c(j, unique(df_tmp2[[3]]))
    df_tmp2[[4]] = as.character(df_tmp2[[4]])
    df_tmp2[[5]] = as.character(df_tmp2[[5]])
    df_tmp2 = df_tmp2[, c(4,5)]

    spatial_meta_tmp = stclust_stlist@spatial_meta[[i]]
    if(colnames(df_tmp2)[2] %in% colnames(spatial_meta_tmp)){
      spatial_meta_tmp = spatial_meta_tmp %>% dplyr::select(-c(colnames(df_tmp2)[2]))
    }
    spatial_meta_tmp = spatial_meta_tmp %>%
      dplyr::left_join(., df_tmp2, by=colnames(df_tmp2)[1])
    if(nrow(spatial_meta_tmp) != nrow(stclust_stlist@spatial_meta[[i]])){
      stop('Something went wrong with the new annotation match...') # ERROR
    }
    stclust_stlist@spatial_meta[[i]] = spatial_meta_tmp

    ps = STplot(x=stclust_stlist, plot_meta=colnames(df_tmp2)[2], ptsize=2, txsize=14, color_pal='smoothrainbow', samples=i)

    for(p in names(ps)){
      saveplot(paste(i, '_', j, sep=''), ps[[p]])
    }

    rm(spatial_meta_tmp, df_tmp2) # Clean env
  }
  rm(df_tmp) # Clean env
}


