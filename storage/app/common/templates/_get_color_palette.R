##
# get_color_palette - Extracted from spatialGE to get a vector of colors outside the package
#
#
get_color_palette <- function(color_pal=NULL, n_cats = NULL) {
  # Get color palette and number of colors needed.
  # Get names of Khroma colors.
  khroma_cols = khroma::info()
  khroma_cols = khroma_cols$palette
  # Assume 5 categories if n_cats not provided (for kriging/quilts).
  if(is.null(n_cats)){
    n_cats = 5
  }
  # Test if input is a Khroma name or RColorBrewer.
  # If so, create palette.
  if (color_pal[1] %in% khroma_cols) {
    cat_cols = as.vector(khroma::colour(color_pal[1], force=T)(n_cats))
  } else if(color_pal[1] %in% rownames(RColorBrewer::brewer.pal.info)) {
    cat_cols = colorRampPalette(RColorBrewer::brewer.pal(n_cats, color_pal[1]))(n_cats)
  } else { # Test if user provided a vector of colors.
    if (length(color_pal) >= n_cats) {
      cat_cols = color_pal[1:n_cats]
    } else {
      stop('Provide enough colors to plot or an appropriate Khroma/RColorBrewer palette.')
    }
  }

  # Subset colors is more colors in palette than categories
  if (length(cat_cols) > n_cats) {
    cat_cols = cat_cols[1:n_cats]
  }
  return(cat_cols)
}
