##
# Diagnostic Evidence GAuge of Single cells (DEGAS)
#

# Python modules to install
# conda create -n degas_env python=3.9
# conda activate degas_env
# pip3 install tensorflow==1.9.0
#### APPARENTLY COMES BY DEFAULT WITH PYTHON:
####pip3 install functools
####pip3 install math

# In R install:
# devtools::install_github("tsteelejohnson91/DEGAS")
#### MIGHT NEED TO INSTALL:
####install.packages("Rtsne")
####install.packages("ggplot2")

# USER ARGUMENTS:
# Clinical variable (first column of clinical key)
clin_var = '#{tcga_feature}#'
# Category within "clin_var" (second column of clinical key)
clin_cat = '#{tcga_category}#'
# One of the annotations generated in Spatial Domain Detection
ann_test = '#{annotation}#'
# Text box, integer - Number of CN layers
cnn_layers = #{number_of_layers}#
# Text box, integer - Number of model bootstraps
bootstraps = #{bootstraps}#
# Zero gene count threshold (Slided 0-1)
zero_thr = #{zero_thr}#
# Top variable genes percentile (slider 0-1)
top_var = #{top_var}#


########### ANALYSIS BEGINS: ###########

library('magrittr')
library('tibble')
library('DEGAS')

# Load TCGA data
clinical_dat = readRDS('user_tcga_clinical_data.RDS')
molecular_dat = readRDS('user_tcga_expression_data.RDS')

# Check if a shift is needed due to negative values
if(any(molecular_dat < 0)){
  molecular_dat = molecular_dat + abs(min(molecular_dat, na.rm=T))
}

# Select high variance genes in TCGA data
pt_vars = apply(molecular_dat, 1, sd, na.rm=T)
molecular_dat = molecular_dat[pt_vars > quantile(pt_vars, 0.9, na.rm=T), ]

# Scale TCGA data
tcga_proc = t(apply(t(molecular_dat), 1, DEGAS::scaleFunc))

rm(molecular_dat, pt_vars) # Clean env

# Make one-hot labels for TCGA data
tcga_labels = ifelse(clinical_dat[[clin_var]] == clin_cat, "risk_cat", "other")
tcga_labels[is.na(tcga_labels)] = 'other'
tcga_labels = toOneHot(tcga_labels)
# Make first column "risk_cat"
tcga_labels = tcga_labels[, c("risk_cat", "other")]

# Load STlist
load('stclust_stlist.RData')
stlist = stclust_stlist

# Release memory
stclust_stlist = NULL
gc(full=T)

# write sample names to file
write.table(names(stlist@counts), 'degas_sample_names.csv', quote=F, row.names=F, col.names=F, sep=",")


# Process each sample
st_counts = lapply(names(stlist@counts), function(i){
  # Extract counts from STlist
  cd_tmp = stlist@counts[[i]]

  # Select top variable and top expressed features
  st_prcnonzero = Matrix::rowSums(cd_tmp > 0)/ncol(cd_tmp)
  st_vars = apply(cd_tmp, 1, var)
  st_selected = rownames(cd_tmp)[ (st_prcnonzero > zero_thr & st_vars > quantile(st_vars, top_var)) ]
  final_features = intersect(colnames(tcga_proc), st_selected)

  cd_tmp = as.data.frame(as.matrix(cd_tmp[final_features, ]))

  return(cd_tmp)
})

# Extract coordinates
st_coords = lapply(stlist@spatial_meta, function(i){i[, c(1:3)]})

# Extract clusters if requested
st_labels = NULL
if(!is.null(ann_test)){
  st_annots = lapply(stlist@spatial_meta, function(i){i[, c('libname', ann_test)]})
  # Create One-hot labels based on clusters
  st_labels = lapply(st_annots, function(i){lab_tmp = toOneHot(i[[2]])})
}

# Save ST sample names
snames = names(stlist@counts)

# Release memory
rm(stlist)
gc(full=T)

# Preprocessing data (log, normalization, scale)
st_counts_proc = lapply(st_counts, function(i){
  cd_tmp = preprocessCounts(i)
})

rm(st_counts) # Clean env

# To solve NA issue
tcga_proc[is.na(tcga_proc)] = 0

# Train models
initDEGAS()
setPython('/opt/conda/envs/degas_env/bin/python')
tmpDir = './tmp/'
DEGAS_model = lapply(1:length(st_counts_proc), function(i){
  set_seed_term(12345)
  tcga_proc_tmp = tcga_proc[, colnames(tcga_proc) %in% colnames(st_counts_proc[[i]])]
  mod_tmp = runCCMTLBag(scExp=st_counts_proc[[i]], scLab=st_labels[[i]],
                        patExp=tcga_proc_tmp, patLab=tcga_labels,
                        tmpDir,
                        'ClassClass', 'DenseNet',
                        cnn_layers, bootstraps)
  return(mod_tmp)
})

# Calculate label probabilities
DEGAS_preds = lapply(1:length(DEGAS_model), function(i){
  preds_tmp = predClassBag(DEGAS_model[[i]], st_counts_proc[[i]], "pat")
  return(preds_tmp)
})

# Create table with results for plotting
plot_ls = lapply(1:length(DEGAS_preds), function(i){
  corrs_tmp = toCorrCoeff(DEGAS_preds[[i]][, 1])
  df_tmp = st_coords[[i]] %>%
    tibble::add_column(pred_corr=corrs_tmp) %>%
    tibble::add_column(pred_corr_spatial_smooth=knnSmooth(corrs_tmp, as.matrix(st_coords[[i]][c(2, 3)])))
})
names(plot_ls) = snames

# Save results to file
lapply(snames, function(i){
  # Only select columns 2, 3, and 4 for output
  df_out = plot_ls[[i]][, 2:4]
  write.csv(df_out,
            paste0(i, '_degas_predictions_corr.csv'),
            quote=F, row.names=F)

  df_out = plot_ls[[i]][, c(2,3,5)]
  write.csv(df_out,
            paste0(i, '_degas_predictions_spatial_smooth.csv'),
            quote=F, row.names=F)
})

