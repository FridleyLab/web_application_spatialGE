#{HEADER}#

##
# Cell-cell interaction (Multivariate Moran's I)
# KernelCommunication package
#

# User options
ligand = "#{gene1}#"
receptor = "#{gene2}#"
max_h = #{max_h}#
inc_h = #{inc_h}#
h_range = seq(0, max_h, inc_h)

# Load packages
library('spatialGE')
library('magrittr')
library('ggplot2')
library('KernelCommunication')

# Load STList
load("#{_stlist}#.RData")
stlist_obj = #{_stlist}#

# Get samples
samples = names(stlist_obj@counts)

# Calculate the metric for each sample
res = lapply(samples, function(samp){
  res = KernelCommunication::soupir_moran_multi(
    coords = stlist_obj@spatial_meta[[samp]][,c("xpos", "ypos")],
    gene1 = stlist_obj@tr_counts[[samp]][ligand,],
    gene2 = stlist_obj@counts[[samp]][receptor,],
    h_vec = h_range,
    perms = 100,
    seed = 333
  )
  return(res)
})
names(res) = samples

# Combine results across samples
res2 = lapply(res, function(samp){
  data.frame(h = samp$h,
             observed = samp$observed,
             permuted = rowMeans(samp$permutations),
             row.names = NULL)
}) %>%
  dplyr::bind_rows(.id = "sample")

# Calculate null-adjusted interaction
res2$degree_of_interaction = res2$observed - res2$permuted

# Write results CSV
write.csv(res2, "moran_multi_results.csv", row.names = FALSE)

# Main interaction plot
p_interaction = ggplot(res2) +
  geom_line(aes(x = h, y = degree_of_interaction, color = sample), linewidth = 0.8) +
  labs(x = "Bandwidth (h)", y = "Degree of interaction",
       title = paste0("Cell-cell interaction: ", ligand, " - ", receptor),
       color = "Sample") +
  theme_bw() +
  theme(legend.position = "bottom")

saveplot("moran_multi_interaction", p_interaction)

# Per-sample permutation plots
for(samp in samples) {
  perms = res[[samp]]$permutations
  colnames(perms) = paste0("perm_", 1:ncol(perms))
  perms = as.data.frame(perms)
  perms$h = res[[samp]]$h
  perms_long = tidyr::pivot_longer(perms, cols = -h, names_to = "permutation", values_to = "I")

  observed_df = data.frame(I = res[[samp]]$observed, h = res[[samp]]$h)

  p_perm = ggplot() +
    geom_line(data = perms_long,
              aes(x = h, y = I, group = permutation), alpha = 0.25, color = "grey50") +
    geom_line(data = observed_df,
              aes(x = h, y = I), color = "red", linewidth = 1) +
    labs(x = "Bandwidth (h)", y = "Moran's I",
         title = paste0(samp, ": ", ligand, " - ", receptor, " (observed vs. permuted)")) +
    theme_bw()

  saveplot(paste0("moran_multi_perms_", samp), p_perm)
}
