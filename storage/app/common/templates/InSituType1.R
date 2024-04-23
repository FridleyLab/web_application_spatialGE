##
# Cell typing of single-cell spatial transcriptomics with Insitutype
#

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
#devtools::load_all('~/Dropbox (Moffitt Cancer Center)/SPATIAL_TRANSCRIPTOMICS/spatialGE/')
library('magrittr')

# Load STlist
load('#{_stlist}#.RData')
STlist = #{_stlist}#

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

# Transpose data (Insitutype takes cell x genes)
merged_cts = t(as.matrix(merged_cts))

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

# Supervised algorithm using complete signature matrix generated from Choi Lab data set
if(length(negmean) == 1){
  sup = insitutypeML(x=merged_cts, bg=negmean, reference_profiles=cell_prof)
} else{
  sup = insitutypeML(x=merged_cts, neg=negmean, reference_profiles=cell_prof)
}

###*******#####save sup as RData
save(sup, file='insitutype_results.RData')


# Make data frame containing classes
sup_df = as.data.frame(sup[['clust']])
colnames(sup_df) = 'insitutype_cell_types'
sup_df = sup_df %>% tibble::rownames_to_column('complete_cell_id')

# Add cell type labels to STlist
insitutype_stlist = STlist
for(i in names(insitutype_stlist@spatial_meta)) {
  if(any(colnames(insitutype_stlist@spatial_meta[[i]]) == 'insitutype_cell_types')) {
    col_rm = which(colnames(insitutype_stlist@spatial_meta[[i]]) == 'insitutype_cell_types')
    insitutype_stlist@spatial_meta[[i]] = insitutype_stlist@spatial_meta[[i]][, -col_rm]
    rm(col_rm) # Clean env
  }
  slide_name = gsub('_fov_[0-9]+$', '', i)
  insitutype_stlist@spatial_meta[[i]] = insitutype_stlist@spatial_meta[[i]] %>%
    dplyr::mutate(complete_cell_id=paste0(slide_name, '_', libname)) %>%
    dplyr::left_join(., sup_df, by='complete_cell_id') %>%
    dplyr::select(-c('complete_cell_id'))
}

# annot_variables used for Differential Expression AND STgradient analyses
annot_variables = unique(unlist(lapply(insitutype_stlist@spatial_meta, function(i){ var_cols=grep('spagcn_|stclust_|insitutype_cell_types', colnames(i), value=T); return(var_cols) })))
write.table(annot_variables, 'stdiff_annotation_variables.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)
## clusters_by_annot_variables used for Differential Expression analyses AND STgradient analyses
cluster_values = tibble::tibble()
for(i in names(insitutype_stlist@spatial_meta)){
  for(cl in grep('spagcn_|stclust_|insitutype_cell_types', colnames(insitutype_stlist@spatial_meta[[i]]), value=T)){
    cluster_values = dplyr::bind_rows(cluster_values,
                                      tibble::tibble(cluster=unique(insitutype_stlist@spatial_meta[[i]][[cl]])) %>%
                                        tibble::add_column(annotation=cl))
  }}
cluster_values = dplyr::distinct(cluster_values) %>%
  dplyr::select(annotation, cluster)
write.table(cluster_values, 'stdiff_annotation_variables_clusters.csv', quote=F, row.names=F, col.names=F, sep=',')

###*******#####save sup as stclust_stlist RData
stclust_stlist = insitutype_stlist
save(stclust_stlist, file='stclust_stlist.RData')
