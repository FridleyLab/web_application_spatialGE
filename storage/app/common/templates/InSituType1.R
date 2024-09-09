##
# Cell typing of single-cell spatial transcriptomics with Insitutype
#

genesis_t = Sys.time() # Log time

# USER PARAMETERS:

# Select cell profile (i.e., cell type signature)
# Species (Human / Mouse)
species = "#{_species}#"
# If 'Human' tissue:
# '~/OneDrive - Moffitt Cancer Center/u01_spatialge_web_shared/data/cell_profiles_spatialdecon/cell_profile_names_human.csv'
# If 'Mouse' tissue:
# '~/OneDrive - Moffitt Cancer Center/u01_spatialge_web_shared/data/cell_profiles_spatialdecon/cell_profile_names_mouse.csv'
cell_prof_db = "#{_cell_prof_db}#"

# Refine cell type predictions? T/F
refine_cells = #{_refine_cells}#

# DO NOT MAKE A CONTROL FOR `negmean` yet
negmean = 0.05 # Use this value as "expected background. Will probably be incorrect for most cases. Hoping most data sets keep the NegPrbs
# DO NOT MAKE A CONTROL FOR `age_grp` yet
age_grp = "Adult"


# To install Insitutype
#devtools::install_github("https://github.com/Nanostring-Biostats/InSituType")
# To install SpatialDecon
#BiocManager::install('SpatialDecon')


library('InSituType')
library('SpatialDecon')
library('spatialGE')
library('magrittr')

# Load STlist
load('#{_stlist}#.RData')
STlist = #{_stlist}#

start_t = Sys.time()
# Iterate over STlist and merge counts... (Hoping this doesnt burn the memory)
i = names(STlist@counts)[1]
merged_cts = STlist@counts[[i]]
colnames(merged_cts) = paste0(gsub('_fov_[0-9]+$', '', i), '_', colnames(merged_cts)) # Add slide IDs
if(length(STlist@counts) > 1){
  for(i in names(STlist@counts)[-1]){
    df_tmp = STlist@counts[[i]]
    colnames(df_tmp) = paste0(gsub('_fov_[0-9]+$', '', i), '_', colnames(df_tmp))
    common_tmp = intersect(rownames(merged_cts), rownames(df_tmp))
    merged_cts = merged_cts[common_tmp, ]
    df_tmp = df_tmp[common_tmp, ]
    merged_cts = cbind(merged_cts, df_tmp)

    rm(df_tmp, common_tmp) # Clean env
  }
}
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tExpression data merge completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

# Transpose data (Insitutype takes cell x genes)
start_t = Sys.time()
merged_cts = t(as.matrix(merged_cts))
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tMerged expression transpose completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

# Calculate average negative control counts if available (genes matching 'NegPrb')
neg_tmp = grep('NegPrb', colnames(merged_cts), value=T)
if(length(neg_tmp) > 0){
  negmean = rowSums(merged_cts[, neg_tmp]) / length(neg_tmp)
  names(negmean) = rownames(merged_cts)

  # Remove negative probes
  merged_cts = merged_cts[, !(colnames(merged_cts) %in% neg_tmp) ]
}
rm(neg_tmp) # Clean env

## CELL PROFILE MATRIX

# Download cell profile matrices
# Options from:
# https://github.com/Nanostring-Biostats/CellProfileLibrary/raw/master/Human/Human_datasets_metadata.csv
# https://github.com/Nanostring-Biostats/CellProfileLibrary/raw/master/Mouse/Mouse_datasets_metadata.csv
cell_prof = download_profile_matrix(species=species, age_group=age_grp, matrixname=cell_prof_db)

# OR

# Create a custom one (MORE ON THIS LATER)
# Read cell profile matrix
# cell_prof = read_delim('../../data/cellprofilemtx_choilab_scrnaseq.csv', delim=',', show_col_types=F) %>% column_to_rownames(var='gene_name')

# Remove cells with zero counts (cells with negative probe counts will be unusable after deleting the probes from matrix)
zero_ct_cells = which(Matrix::rowSums(merged_cts == 0) == ncol(merged_cts))
if(length(zero_ct_cells) > 0){
  merged_cts = merged_cts[-zero_ct_cells, ]
  negmean = negmean[-zero_ct_cells]
}
merged_cts = as.matrix(merged_cts)

start_t = Sys.time()
# Supervised algorithm using complete signature matrix generated from Choi Lab data set
if(length(negmean) == 1){
  sup = insitutypeML(x=merged_cts, bg=negmean, reference_profiles=cell_prof)
} else{
  sup = insitutypeML(x=merged_cts, neg=negmean, reference_profiles=cell_prof)
}
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tCell classifcation completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

###*******#####save Insitutype results object as RData
save(sup, file='insitutype_results.RData')

# Make data frame containing classes
sup_df = as.data.frame(sup[['clust']])
colnames(sup_df) = 'insitutype_cell_types'
sup_df = sup_df %>% tibble::rownames_to_column('complete_cell_id')

# Add cell type labels to STlist
for(i in names(STlist@spatial_meta)){
  if(any(colnames(STlist@spatial_meta[[i]]) == 'insitutype_cell_types')){
    STlist@spatial_meta[[i]] = STlist@spatial_meta[[i]][, !grepl('insitutype_cell_types', colnames(STlist@spatial_meta[[i]]), fixed=T)]
  }

  slide_name = gsub('_fov_[0-9]+$', '', i)
  STlist@spatial_meta[[i]] = STlist@spatial_meta[[i]] %>%
    dplyr::mutate(complete_cell_id=paste0(slide_name, '_', libname)) %>%
    dplyr::left_join(., sup_df, by='complete_cell_id') %>%
    dplyr::select(-c('complete_cell_id')) %>%
    dplyr::mutate(insitutype_cell_types=tidyr::replace_na(insitutype_cell_types, 'unknown'))
}

# annot_variables used for Differential Expression analyses and STgradient analyses
annot_variables = lapply(names(STlist@spatial_meta), function(i){
  var_cols=colnames(STlist@spatial_meta[[i]])[-c(1:5)]
  df_tmp = tibble::tibble()
  for(v in var_cols){
    cluster_values = unique(STlist@spatial_meta[[i]][[v]])
    df_tmp = dplyr::bind_rows(df_tmp, tibble::tibble(V1=i, V2=v, V3=v, V4=cluster_values, V5=cluster_values))
  }
  return(df_tmp) })
annot_variables = dplyr::bind_rows(annot_variables) %>% dplyr::filter(V2 == 'insitutype_cell_types')

# Check if annot_variables file already exists, to avoid rewriting previous manual annotations
if(file.exists('stdiff_annotation_variables_clusters.csv')){
  annot_variables_tmp = data.table::fread('stdiff_annotation_variables_clusters.csv', header=F)
  annot_variables_tmp = annot_variables_tmp[!grepl('insitutype_cell_types', annot_variables_tmp[['V2']]), ]
  if(nrow(annot_variables_tmp) > 0){
    annot_variables = rbind(annot_variables_tmp, annot_variables)
  }
  rm(annot_variables_tmp) # Clean env
}
write.table(annot_variables, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')

###*******#####save new STlist as RData
stclust_stlist = STlist
save(stclust_stlist, file='stclust_stlist.RData')

# PCA/UMAP
start_t = Sys.time()
# Iterate over STlist and merge transformed counts... (Hoping this doesnt burn the memory)
# Subset genes to those in Insitutype object cell profiles
i = names(STlist@tr_counts)[1]
merged_cts = STlist@tr_counts[[i]]
merged_cts = merged_cts[rownames(sup[['profiles']]), ]
colnames(merged_cts) = paste0(gsub('_fov_[0-9]+$', '', i), '_', colnames(merged_cts)) # Add slide IDs
if(length(STlist@tr_counts) > 1){
  for(i in names(STlist@tr_counts)[-1]){
    df_tmp = STlist@tr_counts[[i]]
    colnames(df_tmp) = paste0(gsub('_fov_[0-9]+$', '', i), '_', colnames(df_tmp))
    common_tmp = intersect(rownames(merged_cts), rownames(df_tmp))
    merged_cts = merged_cts[common_tmp, ]
    df_tmp = df_tmp[common_tmp, ]
    merged_cts = cbind(merged_cts, df_tmp)

    rm(df_tmp, common_tmp) # Clean env
  }
}
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tNormalized expression merge (PCA/UMAP) completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

# Scale genes before PCA input
start_t = Sys.time()
merged_cts_scl = scale(Matrix::t(merged_cts))
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tScaling and transpose (normalized expression - PCA/UMAP) completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t, merged_cts) # Clean env

# Calculate PCs
start_t=Sys.time()
pca_irlba_obj = irlba::prcomp_irlba(merged_cts_scl, n=20, retx=T, scale.=F, center=F)
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tAll FOVs PCA completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

# Calculate UMAP embeddings
start_t=Sys.time()
umap_obj = uwot::umap(pca_irlba_obj[['x']], pca=NULL)
rownames(umap_obj) = rownames(merged_cts_scl)
end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('\tAll FOVs UMAP completed in ', round(end_t, 2), ' min.\n'))
rm(start_t, end_t) # Clean env

###*******#####save sup as stclust_stlist RData
#save(umap_obj, file='insitutype_umap_object.RData')

rm(merged_cts_scl) # Clean env

# Create data frame to plot UMAP embeddings
umap_df = as.data.frame(umap_obj) %>%
  dplyr::rename(UMAP1=1, UMAP2=2) %>%
  tibble::rownames_to_column('complete_cell_id') %>%
  dplyr::mutate_at(c('UMAP1', 'UMAP2'), format, scientific=F) %>%
  dplyr::left_join(., as.data.frame(sup[['clust']]) %>%
                     dplyr::rename(insitutype_cell_types=1) %>%
                     tibble::rownames_to_column('complete_cell_id'), by='complete_cell_id') %>%
  dplyr::mutate(sample_name=stringr::str_replace(complete_cell_id, '_cell_[0-9]+', '')) %>%
  #dplyr::left_join(., STlist@sample_meta, by='sample_name') %>%
  dplyr::select(-c('complete_cell_id'))
data.table::fwrite(umap_df, file='insitutype_umap_data.csv', quote=T, row.names=F)

# IN CASE WE WANT TO SUBSAMPLE UMAP PLOT
#umap_df = umap_df[sample(1:nrow(umap_df), size=0.5*nrow(umap_df)), ]

# Generate flightpath plot data
fl_res = InSituType::flightpath_layout(logliks=sup[['logliks']])

flp_df = as.data.frame(fl_res[['cellpos']])
flp_df = flp_df %>% dplyr::mutate_at(c('x', 'y'), format, scientific=F)
flp_df[['insitutype_cell_types']] = fl_res[['clust']]
#flp_df = flp_df %>% dplyr::left_join(., tibble::enframe(fl_res[['meanconfidence']], name='insitutype_cell_types', value='conf'), by='insitutype_cell_types')
#flp_df[['conf']] = round(flp_df[['conf']], 2)
#flp_df[['insitutype_cell_types']] = paste0(flp_df[['insitutype_cell_types']], ' (', flp_df[['conf']], ')')
#flp_df = flp_df[, -4]

data.table::fwrite(flp_df, file='insitutype_flightpath_data.csv', quote=T, row.names=F)

# Labels for flightpath plot clouds
flp_labels = as.data.frame(fl_res[['clustpos']]) %>% tibble::rownames_to_column('insitutype_cell_types') %>%
  dplyr::left_join(., tibble::enframe(fl_res[['meanconfidence']], name='insitutype_cell_types', value='conf'), by='insitutype_cell_types')
flp_labels[['conf']] = round(flp_labels[['conf']], 2)
flp_labels[['insitutype_cell_types']] = paste0(flp_labels[['insitutype_cell_types']], ' (', flp_labels[['conf']], ')')
flp_labels = flp_labels[, c('x', 'y', 'insitutype_cell_types')]
flp_labels = flp_labels %>% dplyr::mutate_at(c('x', 'y'), format, scientific=F)

data.table::fwrite(flp_labels, file='insitutype_flightpath_cloud_labels.csv', quote=T, row.names=F)

# File to create barplots
bps_df = dplyr::bind_rows(lapply(names(STlist@spatial_meta), function(i){
  df_tmp = STlist@spatial_meta[[i]] %>%
    dplyr::mutate(insitutype_cell_types=tidyr::replace_na(insitutype_cell_types, 'unknown')) %>%
    dplyr::mutate(fov_id=i) %>%
    dplyr::select(c('fov_id', 'insitutype_cell_types')) %>%
    dplyr::group_by(insitutype_cell_types) %>%
    dplyr::summarize(number_cells=dplyr::n()) %>%
    dplyr::ungroup() %>%
    tibble::add_column(fov_id=i, .before=1) %>%
    return(df_tmp)
}))
# Make wide table (cell types in columns, FOVs in rows)
bps_df = bps_df %>%
  tidyr::pivot_wider(names_from='insitutype_cell_types', values_from='number_cells') %>%
  dplyr::mutate(unknown=tidyr::replace_na('unknown', 0))
# Write file
write.csv(bps_df, 'insitutype_cell_types_barplot_data.csv', row.names=F)

# Create data frames for quilt plots
lapply(1:length(names(STlist@spatial_meta)), function(i){
  s = names(STlist@spatial_meta)[i]
  df_tmp = STlist@spatial_meta[[s]][,  c('xpos', 'ypos', 'insitutype_cell_types')]
  data.table::fwrite(df_tmp, file=paste0(s, '_insitutype_quilt_data.csv'), quote=F, row.names=F)
})

apocalypse_t = difftime(Sys.time(), genesis_t, units='min')
cat(paste0('Insitutype Part 1 completed in ', round(apocalypse_t, 2), ' min.\n'))

