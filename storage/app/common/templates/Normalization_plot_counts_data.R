##
# @title plot_counts_data: Outputs data to create plots for the distribution of counts
# @details
# The function extracts data from an STlist that can be used to visualize the
# distribution of counts across all genes and spots. These data are useful for
# assessment of the effect of filtering and data transformations and to assess
# zero-inflation.
#
# @param x an STlist
# @param samples samples to include in the plot. Default (NULL) includes all samples
# @param data_type one of `tr` or `raw`, to plot transformed or raw counts
# @param distrib_subset the proportion of spots/cells to plot. These data sets can be
# very large, and this argument provides control on how many randomly selected values to
# show to speed plotting
# @param subset_seed related to `distrib_subset`. Sets the seed number to ensure
# the same subset of values is selected for plotting
# @return a list of ggplot objects
#
plot_counts_data = function(x=NULL, samples=NULL, data_type='tr', distrib_subset=0.5, subset_seed=12345){

  library('magrittr')

  # Define samples to plot if NULL or numeric
  if(is.null(samples)){
    samples = names(x@counts)
  } else if(is.numeric(samples)){
    samples = names(x@counts)[samples]
  }

  # Check that transformed counts are available if data_type='tr'
  if(data_type == 'tr'){
    if(rlang::is_empty(x@tr_counts)){
      stop('spatialGE_web: No transformed expression data in this STlist.')
    }
  }

  # Extract requested data type
  if(data_type == 'tr'){
    x_dat = x@tr_counts[samples]
    x_colname = 'norm_expression'
  } else{
    x_dat = x@counts[samples]
    x_colname = 'raw_counts'
  }

  # Free memory
  x = NULL
  gc(full=T)

  # Loop through samples and make long data frame
  df_tmp = lapply(1:length(samples), function(i){
    df2_tmp = as.matrix(x_dat[[ samples[i] ]]) %>%
      as.data.frame() %>%
      tibble::rownames_to_column('gene_name') %>%
      #tidyr::pivot_longer(cols=dplyr::everything(.), names_to='libname', values_to=x_colname) %>%
      tidyr::pivot_longer(cols=-1, names_to='libname', values_to=x_colname) %>%
      tibble::add_column(sample_name=samples[i]) %>%
      #dplyr::select(dplyr::all_of(c('sample_name', x_colname, 'gene_name', 'libname')))
      dplyr::select(dplyr::all_of(c('sample_name', x_colname, 'gene_name')))

    # Subsample data
    set.seed(subset_seed)
    df2_tmp = df2_tmp[sample(1:nrow(df2_tmp), nrow(df2_tmp)*distrib_subset), ]

    return(df2_tmp)
  })
  names(df_tmp) = samples

  return(df_tmp)
}


##
# @title plot_summary_counts_data: Outputs summarized data to create plots for the
# distribution of counts
# @details
# The function extracts data from an STlist that can be used to visualize the
# distribution of counts across all genes and spots. These data are useful for
# assessment of the effect of filtering and data transformations and to assess
# zero-inflation.
#
# @param x an STlist
# @param samples samples to include in the plot. Default (NULL) includes all samples
# @param data_type one of `tr` or `raw`, to plot transformed or raw counts
# @param distrib_subset the proportion of spots/cells to plot. These data sets can be
# very large, and this argument provides control on how many randomly selected values to
# show to speed plotting
# @param subset_seed related to `distrib_subset`. Sets the seed number to ensure
# the same subset of values is selected for plotting
# @return a list of ggplot objects
#
plot_summary_counts_data = function(x=NULL, samples=NULL, data_type='tr', distrib_subset=1, subset_seed=12345){

  library('magrittr')

  # Define samples to plot if NULL or numeric
  if(is.null(samples)){
    samples = names(x@counts)
  } else if(is.numeric(samples)){
    samples = names(x@counts)[samples]
  }

  # Check that transfromed counts are available if data_type='tr'
  if(data_type == 'tr'){
    if(rlang::is_empty(x@tr_counts)){
      stop('spatialGE_web: No transformed expression data in this STlist.')
    }
  }

  # Extract requested data type
  if(data_type == 'tr'){
    x_dat = x@tr_counts[samples]
    x_colname = 'norm_expression'
  } else{
    x_dat = x@counts[samples]
    x_colname = 'raw_counts'
  }

  # Free memory
  x = NULL
  gc(full=T)

  # Loop through samples and make long data frame
  df_tmp = lapply(1:length(samples), function(i){
    # Extract expression
    mtx = as.matrix(x_dat[[ samples[i] ]])

    # Subsample data if requested
    if(distrib_subset < 1){
      set.seed(subset_seed)
      mtx[sample(1:length(mtx), length(mtx)*distrib_subset)] = NA
    }

    # Add column with gene names to pivot
    mtx = as.data.frame(mtx)
    mtx[['gene']] = rownames(mtx)

    # Make long formatted table
    mtx_long = mtx %>%
      tidyr::pivot_longer(cols = -c("gene"), names_to="column", values_to="value")

    # Calculate value frequencies
    freqs = mtx_long %>%
      dplyr::group_by(gene, value) %>%
      dplyr::summarize(count=dplyr::n()) %>%
      dplyr::ungroup() %>%
      dplyr::select(c('value', 'count', 'gene'))

    cat(paste0('Expression summary values extracted for sample: ', samples[i], '.\n'))

    return(freqs)
  })
  names(df_tmp) = samples

  return(df_tmp)
}

