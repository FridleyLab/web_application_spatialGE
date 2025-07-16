##
# Extract data from TCGA for multiple studies/cohorts listed in tcga_studies.tsv
#

########### TCGA DATA RETRIEVAL: ###########

library('TCGAretriever')
library('magrittr')
library('tidyr')

####install.packages("TCGAretriever")


# Read study/cohort pairs from file
tcga_pairs = read.table('tcga_studies.tsv', sep='\t', header=TRUE, stringsAsFactors=FALSE)

for (i in 1:nrow(tcga_pairs)) {
  study_id = tcga_pairs[i, 1]
  cohort_id = tcga_pairs[i, 2]

  # Obtain clinical data
  clinical_data = get_clinical_data(csid=study_id,
                                    case_list_id=cohort_id)

  # Identify columns with less than 10 categories
  cols_cat = apply(clinical_data[, -1], 2, function(i){
    len_tmp = as.vector(na.omit(unique(i)))
    len_tmp = length(len_tmp)
    if(len_tmp <= 10 & len_tmp > 1){
      return(TRUE)
    } else {
      return(FALSE)
    }
  })
  clinical_data = clinical_data[, c(TRUE, cols_cat)]

  # Create key file with options for clinical data
  clinical_key = clinical_data[, -1] %>%
    tidyr::pivot_longer(colnames(.), names_to='variable', values_to='category') %>%
    dplyr::distinct() %>%
    dplyr::arrange(variable, category) %>%
    tidyr::drop_na(category)

  # Write key to file (unique per study/cohort)
  key_filename = paste0('user_tcga_clinical_variable_key_', cohort_id, '.csv')
  write.csv(clinical_key, key_filename,
            row.names=F, col.names=F, quote=T)

  # Download all expression data
  molecular_data = fetch_all_tcgadata(case_list_id=cohort_id,
                                      gprofile_id=cohort_id,
                                      mutations=F)

  # Prepare molecular data
  rownames(molecular_data) = molecular_data[['hugoGeneSymbol']]
  molecular_data = molecular_data[, -c(which(colnames(molecular_data) %in% c('hugoGeneSymbol', 'entrezGeneId', 'type'))) ]

  # Save TCGA data for DEGAS (unique per study/cohort)
  saveRDS(clinical_data, paste0('user_tcga_clinical_data_', cohort_id, '.RDS'))
  saveRDS(molecular_data, paste0('user_tcga_expression_data_', cohort_id, '.RDS'))
}

