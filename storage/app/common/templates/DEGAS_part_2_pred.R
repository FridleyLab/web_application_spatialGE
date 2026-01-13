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
# One of the annotations generated in Spatial Domain Detection
ann_test = '#{annotation}#' #This one kept running and running
#ann_test = 'stclust_spw0_k2' #This one works
# Text box, integer - Number of CN layers
cnn_layers = #{number_of_layers}#
# Text box, integer - Number of model bootstraps
bootstraps = #{bootstraps}#
# Zero gene count threshold (Slided 0-1)
zero_thr = #{zero_thr}# # For Visium or CosMx 6K or Xenium 5K
# zero_thr = 0.1 # For CosMx 1K
# Top variable genes percentile (slider 0-1)
top_var = #{top_var}# # For Visium or CosMx 6K or Xenium 5K
# top_var = 0.5 # for CosMx 1K
# Filter out annotation groups with < min_cells cells
min_cells = 50
# Top variable genes percentile (slider 0-0.5) for TCGA bulk RNAseq
#top_var_bulkdata = 0.1


########### ANALYSIS BEGINS: ###########

library('magrittr')
library('tibble')
library('DEGAS')
library(limma)

# Load TCGA data
clinical_dat = readRDS('user_tcga_clinical_data.RDS')
molecular_dat = readRDS('user_tcga_expression_data.RDS')

# Check if a shift is needed due to negative values
if(any(molecular_dat < 0)){
  molecular_dat = molecular_dat + abs(min(molecular_dat, na.rm=T))
}


risk_cat = c(#{risk_cat}#)
non_risk_cat = c(#{non_risk_cat}#)

print(unique(clinical_dat[[clin_var]]))
# Standardize case for comparison
clinical_dat[[clin_var]] <- tolower(clinical_dat[[clin_var]])
risk_cat <- tolower(risk_cat)
non_risk_cat <- tolower(non_risk_cat)

# Build risk/non-risk labels
group <- ifelse(clinical_dat[[clin_var]] %in% risk_cat, "risk",
                ifelse(clinical_dat[[clin_var]] %in% non_risk_cat, "non_risk", NA))
# Update tcga_labels
tcga_labels = ifelse(clinical_dat[[clin_var]] %in% risk_cat, 'risk',
                ifelse(clinical_dat[[clin_var]] %in% non_risk_cat, 'non_risk', NA))

# ---------------------------------------------
# Remove samples without labels
# ---------------------------------------------
keep <- !is.na(group)
if (any(!keep)) {
  warning("Removing patients without risk category.")
}
molecular_mat <- molecular_dat[, keep, drop = FALSE]
clinical_dat  <- clinical_dat[keep, ]
group         <- group[keep]
group <- factor(group, levels = c("non_risk","risk"))

# ---------------------------------------------
# Differential Expression Analysis (limma)
# ---------------------------------------------
design <- model.matrix(~ group)
fit <- lmFit(log2(molecular_mat + 1), design)
fit <- eBayes(fit)

# Get all genes ranked by risk vs non_risk contrast
tt <- topTable(fit, coef = "grouprisk", number = Inf)

# Select DE genes (choose your cutoff)
de_genes <- rownames(tt[tt$adj.P.Val < 0.05 & abs(tt$logFC)>0.58, ])
cat("Selected", length(de_genes), "DE genes\n")

# Subset molecular data to DE genes only
molecular_dat <- molecular_mat[de_genes, , drop = FALSE]

# ---------------------------------------------
# Scale TCGA data (DEGAS format)
# ---------------------------------------------
tcga_proc <- t(apply(t(molecular_dat), 1, DEGAS::scaleFunc))

rm(molecular_mat, fit)  # clean workspace

# ---------------------------------------------
# Create one-hot labels for DEGAS
# ---------------------------------------------
tcga_labels_raw <- group
tcga_labels <- toOneHot(tcga_labels_raw)

# reorder columns: risk first
tcga_labels <- tcga_labels[, c("risk", "non_risk")]


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
  st_vars = apply(log2(cd_tmp+1), 1, var)
  st_selected = rownames(cd_tmp)[ (st_prcnonzero > zero_thr & st_vars >
                                     quantile(st_vars, 1-top_var,na.rm = TRUE)) ]
  final_features = intersect(colnames(tcga_proc), st_selected)
  message(i, ": keeping ", length(final_features), " overlapping genes")

  # ============================
  # If >250 genes → prioritization
  # ============================
  if (length(final_features) > 250) {
    message("  → More than 250 genes detected, selecting top 250…")
    # 1. TCGA DE priority: rank by |logFC| (or −log10(FDR))
    tcga_table <- tt[final_features, , drop = FALSE]

    # If missing values, replace with 0
    tcga_table$logFC[is.na(tcga_table$logFC)] <- 0
    tcga_table$adj.P.Val[is.na(tcga_table$adj.P.Val)] <- 1

    # TCGA DE score
    tcga_score <- abs(tcga_table$logFC) + -log10(tcga_table$adj.P.Val + 1e-12)
    names(tcga_score)<-rownames(tcga_table)
    # Rank
    ranked_genes <- names(sort(tcga_score, decreasing = TRUE))
    # Keep top 250
    final_features <- ranked_genes[1:250]
  }

  # --------------------------------------------
  # Write final_features to a text file
  # --------------------------------------------
  out_file <- paste0(i, "_final_features.txt")
  write.table(final_features, out_file, quote = FALSE, row.names = FALSE, col.names = FALSE)
  message(" Saved feature list to: ", out_file)

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
  #min_cells <- min_cells
  bad_types <- which(colSums(st_labels[[i]]) < min_cells)
  if (length(bad_types)) {
    keep_cells <- rowSums(st_labels[[i]][, bad_types, drop = FALSE]) == 0
    st_counts_proc[[i]] <- st_counts_proc[[i]][ keep_cells,]
    st_labels[[i]] <- st_labels[[i]][keep_cells, -bad_types ]
  }
  tcga_proc_tmp = tcga_proc[, colnames(tcga_proc) %in% colnames(st_counts_proc[[i]])]

  if (nrow(st_counts_proc[[i]]) > 24000){
    K=800
    mod_tmp= runDEGASatlas(stDat=st_counts_proc[[i]],scLab=st_labels[[i]],
                           patDat=tcga_proc_tmp,patLab=tcga_labels,
                           tmpDir,
                           "ClassClass","DenseNet",
                           cnn_layers,bootstraps,
                           K)
  }else{
    mod_tmp = runCCMTLBag(scExp=st_counts_proc[[i]], scLab=st_labels[[i]],
                          patExp=tcga_proc_tmp, patLab=tcga_labels,
                          tmpDir,
                          'ClassClass', 'DenseNet',
                          cnn_layers, bootstraps)
  }

  return(mod_tmp)
})


# Calculate label probabilities
DEGAS_preds = lapply(1:length(DEGAS_model), function(i){
  preds_tmp = predClassBag(DEGAS_model[[i]], st_counts_proc[[i]], "pat")

  return(preds_tmp)
})

# Create table with results for plotting
plot_ls = lapply(1:length(DEGAS_preds), function(i){
  # add removed cells back and assign NA
  all_cells <- st_coords[[i]]$libname
  DEGAS_preds[[i]] <- DEGAS_preds[[i]][match(all_cells, rownames(DEGAS_preds[[i]])), , drop = FALSE]
  rownames(DEGAS_preds[[i]]) <- all_cells

  corrs_tmp = toCorrCoeff(DEGAS_preds[[i]][, 1])
  df_tmp = st_coords[[i]] %>%
    tibble::add_column(pred_corr=corrs_tmp) %>%
    tibble::add_column(pred_corr_spatial_smooth=knnSmooth(corrs_tmp, as.matrix(st_coords[[i]][c(2, 3)])))
})
names(plot_ls) = snames

# Save results to file
lapply(snames, function(i){
  # Only select columns 2, 3, and 4 for output
  df_out = plot_ls[[i]][, c(1,2:4)]
  write.csv(df_out,
            paste0(i, '_degas_predictions_corr.csv'),
            quote=F, row.names=F)

  df_out = plot_ls[[i]][, c(1,2,3,5)]
  write.csv(df_out,
            paste0(i, '_degas_predictions_spatial_smooth.csv'),
            quote=F, row.names=F)
})


print('spatialGE_PROCESS_COMPLETED')
