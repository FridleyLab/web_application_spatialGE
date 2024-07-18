library('magrittr')

##
# data_quilt_expression: Extracts data for quilt plots of gene expression
# x an STlist
# genes a vector of one or more gene names to plot.
# samples a vector of numbers indicating the ST samples to plot, or their
# sample names. If vector of numbers, it follow the order of `names(x@counts)`.
# If NULL, the function plots all samples
# @param data_type one of 'tr' or 'raw', to plot transformed or raw counts
# respectively
#
# @return a list with plots
#
data_quilt_expression = function(x=NULL, genes=NULL, samples=NULL, data_type='tr'){

  # Test that a gene name was entered.
  if (is.null(genes)) {
    stop("Please, enter one or more genes to plot.")
  }

  # Remove duplicated genes entered by user.
  genes = unique(genes)

  # Define which samples to plot
  if(is.null(samples)){
    samples = names(x@spatial_meta)
  } else{
    if(is.numeric(samples)){
      samples = names(x@spatial_meta)[samples]
    }
    if(length(grep(paste0(samples, collapse='|'), names(x@spatial_meta))) == 0){
      stop('The requested samples are not present in the STlist spatial metadata.') # Error if samples were not found in STlist
    }
  }

  # Extract coordinates from STlist
  cds = x@spatial_meta[samples]

  # Select appropriate slot to take counts from
  if(data_type == 'tr'){
    counts = x@tr_counts[samples]
  }else if(data_type == 'raw'){
    counts = x@counts[samples]
  } else(
    stop('Please, select one of transformed (tr) or raw (raw) counts')
  )

  x = NULL # Offload STlist

  # Extract genes and transpose expression data (to get genes in columns)
  # Also join coordinate data
  for(i in samples){
    if(any(rownames(counts[[i]]) %in% genes)){
      counts[[i]] = as.data.frame(t(as.matrix(counts[[i]][genes, , drop=F]))) %>%
        tibble::rownames_to_column(var='libname') %>%
        dplyr::left_join(cds[[i]] %>% dplyr::select(libname, xpos, ypos), ., by='libname') %>%
        dplyr::select(-c('libname'))
    } else{
      # Remove samples for which none of the requested genes are available
      counts = counts[grep(i, names(counts), value=T, invert=T)]
      warning(paste0('The requested genes are not available for sample ', i, '.'))
    }
  }

  # Test if requested data slot is available.
  if(rlang::is_empty(counts)) {
    stop("Data was not found in the specified slot of this STlist.")
  }

  return(counts)
}


stlist = #{_stlist}#
data_type='#{data_type}#'

# Extract data frames
stplot_dfs = data_quilt_expression(stlist, genes=#{genes}#, data_type=data_type, samples=#{samples}#)

# Title for legend title
if(data_type == 'tr'){
  qlegname = paste0(stlist@misc[['transform']], "\nexpr")
} else{
  qlegname = 'raw\nexpr'
}

# Save to text file
lapply(1:length(stplot_dfs), function(i){
  data.table::fwrite(stplot_dfs[[i]], file=paste0(names(stplot_dfs)[i], '_expr_quilt_data.csv'), quote=F, row.names=F)
})
