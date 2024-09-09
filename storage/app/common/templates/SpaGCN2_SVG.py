##
# SPATIALLY VARIABLE GENES USING SPAGCN
#

import os
import re
import scanpy as sc
import pandas as pd
import numpy as np
import SpaGCN as spg
import json

# User parameters
preds = '{param_annotation_to_test}' # Same as the annotation selected ate the beginning

## PROCESSING BEGINS HERE
samples = [{param_sample_list}]
# samples = [f for f in  os.listdir('../../../results_and_intermediate_files/spagcn/visium/') if re.match(r'^spagcn_svg_annotations_', f)]
# samples = [s.replace('spagcn_svg_annotations_sample_', '') for s in samples]
# samples = [s.replace('.csv', '') for s in samples]

#store the list of csv files generated for each sample
files = {}

# Iterate over samples
for i in range(len(samples)):

    files[samples[i]] = []

    # Read adjancency matrix
    adj_fp = samples[i] + "_adj_mtx.csv"
    adj_df = pd.read_csv(adj_fp)
    adj = adj_df.iloc[:, 1:].to_numpy()

    del adj_fp  # Clean env

    # Read AnnData object
    adata = sc.read("anndata_" + samples[i] + ".h5ad")
    # Read domain assignments
    res_fp = "spagcn_svg_annotations_sample_" + samples[i] + ".csv"
    domains = pd.read_csv(res_fp)
    domains = domains[['libname', preds]] # This will return error if annotation is not present in the data

    os.remove(res_fp) # Remove annotation file created by R container

    del res_fp  # Clean env

    # Make sure domains are in the same order as adjacency matrix
    df_tmp = pd.DataFrame({'libname': adj_df.iloc[:, 1:].columns.to_list()})
    domains = pd.merge(df_tmp, domains, left_on='libname', right_on='libname', how='outer')

    del df_tmp # Clean env

    start = np.quantile(adj[adj != 0], q=0.001)
    end = np.quantile(adj[adj != 0], q=0.1)

    # Test SVGs in each domain
    for kval in list(set(domains[preds])):
        # Search radius such that each spot in the target domain has approximately 10 neighbors on average
        r = spg.search_radius(target_cluster=kval, cell_id=adata.obs.index.tolist(),
                              x=adata.obs["x_pixel"].tolist(), y=adata.obs["y_pixel"].tolist(),
                              pred=domains[preds].tolist(), start=start, end=end, num_min=10, num_max=14, max_run=100)

        # Detect neighboring domain
        nbr_domains = spg.find_neighbor_clusters(target_cluster=kval, cell_id=adata.obs.index.to_list(),
                                                 x=adata.obs["x_pixel"].tolist(), y=adata.obs["y_pixel"].tolist(),
                                                 pred=domains[preds].tolist(), radius=r, ratio=1/2)

        print('Neighbor domains ' + str(kval) + ': ' + str(nbr_domains) + '...')

        # Find spatially variable genes
        adata_tmp = adata
        adata_tmp.obs["pred"] = domains[preds].tolist()
        de_genes_info = spg.rank_genes_groups(input_adata=adata,
                                              target_cluster=kval,
                                              nbr_list=nbr_domains,
                                              label_col="pred",
                                              adj_nbr=True,
                                              log=True)

        res_fp = samples[i] + '_' + str(kval) + '_' + preds + '_spagcn_spatially_var_genes.csv'
        de_genes_info = de_genes_info.sort_values(by=["pvals_adj", "in_group_fraction"], ascending=[True, False])
        de_genes_info.to_csv(res_fp, index=False)

        files[samples[i]].append(res_fp)

with open('spagcn_svg_files.json', 'w') as file:
    json.dump(files, file, indent=2)

print('spatialGE_PROCESS_COMPLETED')
