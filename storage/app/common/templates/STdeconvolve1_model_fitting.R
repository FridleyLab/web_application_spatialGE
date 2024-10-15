#{HEADER}#
##
# Domain/niche phenotyping with STdeconvolve
#
# Arguments
rm_mt = #{rm_mt}# # Check box -- Remove mitochondiral genes? (Visium only)
rm_rp = #{rm_rp}# # Check box -- Remove ribosomal genes? (Visium only)
use_var_genes = #{use_var_genes}# # Check box -- Use only high-variance genes?
use_var_genes_n = #{use_var_genes_n}#
min_k = #{min_k}#
max_k = #{max_k}#
cores = 6 # For parallelization (not user-controlled)

start_t = Sys.time()
library('magrittr')
library('STdeconvolve')
library('spatialGE')
library('ggplot2')
library('ggtext')

# USE NORMALIZED
load(file='normalized_stlist.RData')
stlist = normalized_stlist

# Process each sample
suggested_k = tibble::tibble() # To store suggested k values
p = list() # Save plots
all_ldas = list()
for(i in names(stlist@counts)){
  # Remove mitochondrial/ribosomal genes from corpus
  if(rm_mt){
    rm_genes = rownames(stlist@counts[[i]])[grepl("^MT-", rownames(stlist@counts[[i]]))]
    stlist = filter_data(stlist, rm_genes=rm_genes, samples=i)
    rm(rm_genes) # Clean env
  }
  if(rm_rp){
    rm_genes = rownames(stlist@counts[[i]])[grepl("^RP[L|S]", rownames(stlist@counts[[i]]))]
    stlist = filter_data(stlist, rm_genes=rm_genes, samples=i)
    rm(rm_genes) # Clean env
  }

  # Extract counts from STlist
  cd = t(as.matrix(stlist@counts[[i]]))

  # Process counts
  corpus = preprocess(dat=cd, ODgenes=use_var_genes, nTopOD=use_var_genes_n, verbose=F, plot=F)

  rm(cd) # Clean env

  # Run LDA
  log_start_t = Sys.time()
  ldas = fitLDA(corpus[['corpus']], Ks=c(min_k:max_k), ncores=cores, seed=12345, plot=F, verbose=F)
  log_end_t = difftime(Sys.time(), log_start_t, units='min')
  cat(paste0('Finished fitting LDA model to ', i, '... ', round(as.vector(log_end_t), 2), ' min\n'))

  # Plot LDA model selection metrics
  metrics_df = tibble::tibble(k=as.numeric(names(ldas[['models']])),
                              alpha=unlist(lapply(ldas[['models']], function(i)(return(i@alpha)))),
                              perplexity=ldas[['perplexities']],
                              scaled_perplexity=scales::rescale(ldas[['perplexities']], to=c(0, max_k)),
                              rare_types=ldas[['numRare']]) %>%
    dplyr::mutate(k_alpha=paste0(as.character(names(ldas[['models']])), ' (', round(alpha, 2), ')'))

  # Attempt to find best K
  # Find model with minimum perplexity
  min_perp_index = which.min(metrics_df[['perplexity']])
  min_perp_k = metrics_df[['k']][min_perp_index]
  min_perp_val = metrics_df[['perplexity']][min_perp_index]
  min_perp_rare_val = metrics_df[['rare_types']][min_perp_index]
  range_perp = max(metrics_df[['perplexity']])- metrics_df[['perplexity']][min_perp_index]
  if(min_perp_rare_val > 0){
    next_rare_index = min_perp_index - 1
    next_rare_val = metrics_df[['rare_types']][next_rare_index]
    next_rare_perp = metrics_df[['perplexity']][next_rare_index]
    diff_perp = next_rare_perp - min_perp_val
    # Checks that differences in perplexity are lower than half the range of perplexities, on order to pick a "drop/elbow"
    while(next_rare_val > 0 & diff_perp < range_perp * 0.5 & next_rare_index > 0){
      next_rare_index = next_rare_index - 1
      next_rare_val = metrics_df[['rare_types']][next_rare_index]
      next_rare_perp = metrics_df[['perplexity']][next_rare_index]
      diff_perp = next_rare_perp - min_perp_val
      min_perp_k = metrics_df[['k']][next_rare_index]
    }
    rm(next_rare_index, next_rare_val, next_rare_perp, diff_perp) # Clean env
  }
  suggested_k = dplyr::bind_rows(suggested_k, tibble::tibble(sample_name=i, suggested_k=min_perp_k))

  rm(min_perp_index, min_perp_k, min_perp_val, min_perp_rare_val, range_perp) # Clean env

  # Create color palette according to model alphas (desirable bwlow 1)
  col_alpha = ifelse(metrics_df[['alpha']] > 1, 'orange', 'gray50')

  p[[i]] = ggplot(metrics_df) +
    geom_line(aes(x=k, y=rare_types), col='blue') +
    geom_line(aes(x=k, y=scaled_perplexity), col='red') +
    xlab('Number of topics in model') +
    scale_y_continuous('Topics with proportion < 0.05',
                       breaks=seq(-1, max_k),
                       labels=c('Model\nAlpha', 0:max_k),
                       sec.axis=sec_axis(~., name="Perplexity",
                                         breaks=seq(0, max(metrics_df[['k']])),
                                         labels=sprintf(
                                           '%.1f', seq(
                                             min(metrics_df[['perplexity']]),
                                             max(metrics_df[['perplexity']]),
                                             length.out=max(metrics_df[['k']])+1)))) +
    scale_x_continuous(breaks=as.numeric(names(ldas[['models']])), labels=metrics_df[['k_alpha']]) +
    ggtitle(paste0(i, '<br>
      <span style="color: blue;">Rare (<5% frequency) topics</span> and <span style="color: red;">model perplexity</span><br>
      Model alpha: (
      <span style="color: gray50;">Alpha < 1</span> |
      <span style=\"color: orange;">Alpha > 1</span>
      )')) +
    theme(panel.background=element_rect(color="black", fill=NULL),
          axis.text.x=element_text(angle=45, vjust=1, hjust=1, color=col_alpha),
          axis.title.y.left=ggplot2::element_text(color='blue'),
          axis.text.y.left=element_text(color=c('gray50', rep('blue', 16))),
          axis.title.y.right=ggplot2::element_text(color='red'),
          axis.text.y.right=element_text(color='red'),
          axis.ticks.y.left=element_line(color=c('white', rep('black', 16))),
          plot.title=ggtext::element_markdown())

  # Save LDA models
  all_ldas[[i]] = ldas

  rm(metrics_df, col_alpha, log_start_t, log_end_t, ldas) # Clean env
}

write.table(names(p), 'stdeconvolve_plots.csv',sep=',', row.names = FALSE, col.names=FALSE, quote=FALSE)

for(i in names(p)) {
    filetitle = paste('stdeconvolve_', i, sep='')
    saveplot(filetitle, p[[i]])

    #generate side-by-side for samples with tissue image
    # for(sample in list(#{samples_with_tissue}#)) {
    #     if(grepl(sample, i, fixed=TRUE)) {
    #         tp = cowplot::ggdraw() + cowplot::draw_image(paste0(sample, '/spatial/image_', sample, '.png'))
    #         ptp = ggpubr::ggarrange(p[[i]], tp, ncol=2)
    #         saveplot(paste0(filetitle, '-sbs'), ptp, 1400, 600)
    #     }
    # }
}

# Save suggested K values
write.table(suggested_k, 'stdeconvolve_suggested_k.csv',sep=',', row.names = FALSE, quote=FALSE)
#write.csv(suggested_k, 'stdeconvolve_suggested_k.csv', row.names = FALSE, col.names=FALSE, quote=FALSE)

# Save LDA models
saveRDS(all_ldas, './stdeconvolve_lda_models.RDS')

end_t = difftime(Sys.time(), start_t, units='min')
cat(paste0('STdeconvolve finished. ', round(as.vector(end_t), 2), ' min\n'))
