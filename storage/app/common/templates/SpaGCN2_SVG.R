##
# Prepare annotations for SVG prediction wiht SpaGCN
#


# Load the package
library('spatialGE')
library('magrittr')


# Selection by user
annot_test = '#{annotation_to_test}#'
samples_test = c(#{sample_list}#)

# Load STclust/spagcn/insitutype STList
load("#{_stlist}#.RData")
stclust_stlist = #{_stlist}#

if(is.null(samples_test)){
  samples_test = names(stclust_stlist@spatial_meta)
}

for(i in samples_test){
  if(!(annot_test %in% colnames(stclust_stlist@spatial_meta[[i]]))){
    master_ann = 'stdiff_annotation_variables_clusters.csv'
    master_ann = data.table::fread(master_ann, header=F) %>% dplyr::filter(V1 == i & V3 == annot_test)
    orig_annot = unique(master_ann[['V2']])

    # If no data matching the provided annotation in the master file, skip sample
    if(nrow(master_ann) == 0){
      warning(paste0('Skipping sample ', i, ' because the requested annotation was not found.'))
      next()
    }

    # A modified annotation must correspond to a single original annotation for a given sample
    if(length(orig_annot) > 1){
      orig_annot = orig_annot[1]
      master_ann = master_ann[master_ann[['V2']] == orig_annot, ]
      warning(paste0("The requested annotation derives from more than one original annotation. Using annotations derived only from ", orig_annot, '...'))
    }

    master_ann = master_ann[, c('V4', 'V5')]
    master_ann = as.data.frame(apply(master_ann, 2, as.character))
    colnames(master_ann) = c(orig_annot, annot_test)

    df_tmp = stclust_stlist@spatial_meta[[i]][, c('libname', orig_annot)] %>%
      dplyr::left_join(., master_ann, by=orig_annot)
    df_tmp = df_tmp[, c('libname', annot_test)]

    rm(master_ann, orig_annot) # Clean env

  } else{
    df_tmp = stclust_stlist@spatial_meta[[i]][, c('libname', annot_test)]
  }

  write.csv(df_tmp, paste0('spagcn_svg_annotations_sample_', i, '.csv'), quote=T, row.names=F)
  rm(df_tmp) # Clean env
}

