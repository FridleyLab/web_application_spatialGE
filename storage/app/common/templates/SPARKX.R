##
# Spatially variable gene detection with SPARK-X
#

# To install SPARK-X
# devtools::install_github("xzhoulab/SPARK")


# User arguments
samples = #{samples}#
thr = #{thr}# #### Slider 0 to 1

#### SPARK-X ANALYSIS BEGINS

start_t = Sys.time()

# Load libraries
library('magrittr')
library('spatialGE')
library('SPARK')

# Load STlist
load('normalized_stlist.RData')
stlist = normalized_stlist

# Define sample names to test if NULL
if(is.null(samples)){
  samples = names(stlist@counts)
}

# Extract expression data from STlist
cts_ls = stlist@counts

# Subset genes by expression in case user requests it
if(!is.null(thr)){
  for(i in samples){
    # expr_thr = as.vector(quantile(stlist@gene_meta[[i]][['gene_mean']], probs=thr))
    # vargenes_tmp = stlist@gene_meta[[i]][[1]][ stlist@gene_meta[[i]][['gene_mean']] >= expr_thr ]

    expr_thr = as.vector(quantile(stlist@gene_meta[[i]][['gene_stdevs']], probs=thr))
    vargenes_tmp = stlist@gene_meta[[i]][[1]][ stlist@gene_meta[[i]][['gene_stdevs']] >= expr_thr ]

    cts_ls[[i]] = cts_ls[[i]][rownames(cts_ls[[i]]) %in% vargenes_tmp, , drop=F]
  }
}

# Extract coordinates data from STlist
coords_ls = lapply(samples, function(j){
  df_tmp = as.data.frame(stlist@spatial_meta[[j]][, c(1:4)])
  colnames(df_tmp)[2:3] = c('y', 'x')
  df_tmp = df_tmp[, c(1,3,2,4)]
  rownames(df_tmp) = df_tmp[[1]]
  df_tmp = df_tmp[, -1]

  return(df_tmp)
})
names(coords_ls) = samples

# Clean env
rm(stlist)
invisible(gc(full=T))

# Run SPARK analysis:
# Create SPARK objects
# Fit spatial model (n0 hypothesis)
# Test genes
spark_obj = lapply(samples, function(i){
  spk_tmp = CreateSPARKObject(counts=cts_ls[[i]], location=coords_ls[[i]][, c(1:2)], percentage=0.1, min_total_counts=10)
  spk_tmp@lib_size = as.vector(coords_ls[[i]][[3]])
  spk_tmp = spark.vc(spk_tmp, covariates=NULL, lib_size=spk_tmp@lib_size, num_core=1, verbose=F)
  spk_tmp = spark.test(spk_tmp, check_positive=T, verbose=F)

  return(spk_tmp)
})
names(spark_obj) = samples

# Save results
for(i in samples){
  df_tmp = spark_obj[[i]]@res_mtest %>% tibble::rownames_to_column('gene_name')
  write.csv(df_tmp, paste0("sparkx_", i, "_sparkx_spatially_var_genes.csv"), row.names=F, quote=F)
  rm(df_tmp)
}

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('SPARK-X finished. ', round(as.vector(end_t), 2), ' min\n'))

