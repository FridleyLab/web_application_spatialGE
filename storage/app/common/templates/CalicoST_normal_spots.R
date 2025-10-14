library(dplyr)

# Load the STlist object
load('stclust_stlist.RData')
STlist <- stclust_stlist
# Example usage
sample_name <- "#{sample_name}#"
tissue_positions_file <- paste0(sample_name, "/spatial/", sample_name, "_", "tissue_positions_list.csv")
variable_name <- "#{annotation_name}#"
cluster_value <- "#{cluster}#"

# Read tissue positions file (barcode, in_tissue, array_row, array_col, pxl_col_in_fullres, pxl_row_in_fullres)
tissue_positions <- read.csv(
  tissue_positions_file,
  header = FALSE,
  col.names = c('barcode', 'in_tissue', 'array_row', 'array_col', 'pxl_col_in_fullres', 'pxl_row_in_fullres')
)

# Internal: normalize cluster value type (factor vs character/numeric)
.normalize_cluster_value <- function(vec, val) {
  if (is.factor(vec)) {
    as.character(val) -> val
    as.character(vec)
  } else {
    vec
  }
}

# Return ST coords for a sample/variable/cluster (as stored in STlist)
extract_cluster_coordinates <- function(sample_name, variable_name, cluster_value) {
  if (!sample_name %in% names(STlist@spatial_meta)) {
    stop(sprintf("Sample %s not found in STlist", sample_name))
  }
  spatial_data <- STlist@spatial_meta[[sample_name]]
  if (!variable_name %in% colnames(spatial_data)) {
    stop(sprintf("Variable %s not found in sample %s", variable_name, sample_name))
  }

  cl_col <- spatial_data[[variable_name]]
  # Handle factors vs numerics/characters
  if (is.factor(cl_col)) {
    cl_val <- as.character(cluster_value)
    cl_col <- as.character(cl_col)
  } else {
    cl_val <- cluster_value
  }

  cluster_spots <- spatial_data[cl_col == cl_val, , drop = FALSE]
  if (!nrow(cluster_spots)) {
    warning(sprintf("No spots for cluster %s in %s/%s", cluster_value, sample_name, variable_name))
    return(data.frame())
  }

  # ST coords (note: STlist has swapped axes vs tissue_positions)
  out <- data.frame(
    sample  = sample_name,
    variable = variable_name,
    cluster = as.character(cluster_value),
    xpos = cluster_spots$xpos,
    ypos = cluster_spots$ypos,
    stringsAsFactors = FALSE
  )
  out
}

# Exact-match barcodes by swapped axes:
# STlist.ypos == tissue_positions.pxl_col_in_fullres
# STlist.xpos == tissue_positions.pxl_row_in_fullres
get_barcodes_by_cluster <- function(sample_name, variable_name, cluster_value) {
  coords <- extract_cluster_coordinates(sample_name, variable_name, cluster_value)
  if (!nrow(coords)) return(coords)

  # Build join keys (rename to common names for an exact, no-tolerance match)
  coords_join <- coords %>%
    mutate(
      join_col = ypos,  # ST y -> tissue X (pxl_col_in_fullres)
      join_row = xpos   # ST x -> tissue Y (pxl_row_in_fullres)
    )

  tp_join <- tissue_positions %>%
    transmute(
      barcode,
      join_col = pxl_col_in_fullres,
      join_row = pxl_row_in_fullres,
      # Keep the original tissue coordinates
      tissue_x = pxl_col_in_fullres,
      tissue_y = pxl_row_in_fullres
    )

  # Left-join to keep every ST spot; exact one-to-one expected
  res <- coords_join %>%
    left_join(tp_join, by = c("join_col", "join_row")) %>%
    # Return tissue coordinates instead of STlist coordinates
    select(sample, variable, cluster,
           xpos = tissue_x,  # Use tissue X coordinate
           ypos = tissue_y,  # Use tissue Y coordinate
           barcode)

  # Diagnostics
  dup_tp <- tp_join %>% count(join_col, join_row) %>% filter(n > 1)
  if (nrow(dup_tp) > 0) {
    warning(sprintf("Found %d duplicate (x,y) tuples in tissue_positions; expected uniqueness.", nrow(dup_tp)))
  }
  if (any(is.na(res$barcode))) {
    message(sprintf("Unmatched spots: %d of %d (exact match, swapped axes).",
                    sum(is.na(res$barcode)), nrow(res)))
  }
  res
}

cat("Available variables in sample:\n")
print(colnames(STlist@spatial_meta[[sample_name]]))

if (variable_name %in% colnames(STlist@spatial_meta[[sample_name]])) {
  cat(sprintf("Available clusters for %s:\n", variable_name))
  print(unique(STlist@spatial_meta[[sample_name]][[variable_name]]))
}

# Match barcodes with exact, swapped-axis join
result <- get_barcodes_by_cluster(sample_name, variable_name, cluster_value)
print(head(result))
# Only save the barcode values to a TSV file and do not have a header row
write.table(
  result$barcode,
#   file = paste0(sample_name, "_", variable_name, "_cluster", cluster_value, "_barcodes.tsv"),
  file = paste0(sample_name, "/calicost_barcodes.tsv"),
  row.names = FALSE, col.names = FALSE, quote = FALSE
)


# Summary
cat(sprintf("Spots: %d | Matched barcodes: %d | Unmatched: %d\n",
            nrow(result), sum(!is.na(result$barcode)), sum(is.na(result$barcode))))
