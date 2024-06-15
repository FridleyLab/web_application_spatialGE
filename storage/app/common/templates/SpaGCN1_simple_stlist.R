# Extract transformed counts and coordinates from STlist ("simpleSTlist")
#

library('spatialGE')


load("#{_stlist}#.RData")
stclust_stlist = #{_stlist}#

#for(i in names(stclust_stlist@tr_counts)){
for(i in #{_samples}#)
{
  tr_counts = as.matrix(stclust_stlist@tr_counts[[i]])
  spatial_meta = stclust_stlist@spatial_meta[[i]][, 1:3]
  col_names = colnames(stclust_stlist@tr_counts[[i]])
  gene_names = rownames(stclust_stlist@tr_counts[[i]])
  sample_name = i
  save('tr_counts', 'spatial_meta', 'col_names', 'gene_names', 'sample_name', file=paste0(i, '_simplestlist_example.RData'))
}
