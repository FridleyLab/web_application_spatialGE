#{HEADER}#

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
test_type = '#{test_type}#'
pairwise = #{pairwise}#
clusters = #{clusters}#

if(is.null(samples_test)){
  samples_test = names(stclust_stlist@spatial_meta)
}

new_annot_test = c()
samples_test_tmp = c()
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
      samples_test_tmp = c(samples_test_tmp, i)

      rm(master_ann_tmp, annot_tmp)
    }
  }
}

if(length(samples_test_tmp) > 0){
  samples_test = samples_test_tmp
}

for(i in new_annot_test){
  for(j in samples_test){
    if(i %in% colnames(stclust_stlist@spatial_meta[[j]])){
      de_genes_results = STdiff(stclust_stlist, #### NORMALIZED STList
                                samples=j,   #### Users should be able to select which samples to include in analysis
                                annot=i,  #### Name of variable to use in analysis... Dropdown to select one of `annot_variables`
                                topgenes=topgenes, #### !!! Defines a lot of the speed. 100 are too few genes. Minimally would like 5000 but is SLOW. Can be a slider as in pseudobulk
                                test_type=test_type, #### Other options are 't_test' and 'mm',
                                pairwise=pairwise, #### Check box
                                clusters=clusters, #### Need ideas for this one. Values in `cluster_values` and after user selected value in annot dropdown
                                sp_topgenes=0, ## NEED TO SET TO ZERO
                                cores=4) #### You know, the more the merrier

      ### Save non-spatial tests
      # Get workbook with results (samples in spreadsheets)
      openxlsx::write.xlsx(de_genes_results, file=paste0('./stdiff_ns_results', i, '.xlsx'))

      # Each sample as a CSV
      lapply(names(de_genes_results), function(i){
        write.csv(de_genes_results[[i]], paste0('./stdiff_ns_', i, '.csv'), row.names=F, quote=F)
      })

      # Create volcano plots
      ps = STdiff_volcano(de_genes_results, samples=samples_test, clusters=clusters, pval_thr=0.05, color_pal=NULL)
      write.table(names(ps), 'stdiff_ns_volcano_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)


      # Save plots to file
      lapply(names(ps), function(s){
        saveplot(paste0('./stdiff_ns_vp_', s, '_', i, '.pdf'), ps[[paste0(s, '_', i)]])
      })

    }
  }
}

