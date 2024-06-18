##
# spatialGE commands for spatial gradient detection - STgradient
#


# Load the package
library('spatialGE')
library('SeuratObject')
library('magrittr')


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
    master_ann = data.table::fread(master_ann) %>% dplyr::filter(V1 == i & V3 == annot_test)
    orig_annot = unique(master_ann[['V2']])
    for(j in orig_annot){
      annot_tmp = paste0(annot_test, '_', j)
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

if(length(new_annot_test) == 0){
  stop('The requested annoations could not be found in any of the samples.')
}

for(i in new_annot_test){
  for(j in samples_test){
    if(i %in% colnames(stclust_stlist@spatial_meta[[j]])){
      grad_res = STgradient(x=stclust_stlist,
                            samples=j,
                            topgenes=topgenes,
                            annot=i,
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

    }
  }
}

