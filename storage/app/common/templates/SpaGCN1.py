##
# Test environment to run SpaGCN
#

# Env setting (conda 23.11.0):
# conda create --name spagcn_web python=3.8
# conda activate spagcn_web

# pip install SpaGCN==1.2.7
# pip install numba==0.53.1
# pip install numpy==1.20.3
# pip install pyreadr==0.5.0


# Import libraries
import os
import pandas as pd
import numpy as np
import scanpy as sc
import math
import random
import torch
import SpaGCN as spg
import anndata as ad
import pyreadr

# User parameters:
p = {param_p} # p: parameter is the percentage of total expression contributed by neighborhoods. If smaller area (e.g. MERFISH), then set higher p
min_k = {param_number_of_domains_min} # Minimum k value to predict
max_k = {param_number_of_domains_max} # Maximum k value to predict
user_seed = {param_user_seed}
refine_clusters = {param_refine_clusters} # Only available for Visium

# Set seeds
r_seed = t_seed = n_seed = user_seed

samples = [{param_sample_list}]

class DataError(Exception):
    pass

# Iterate over samples in simpleSTlist
for i in range(len(samples)):
    # Load simpleSTlist
    file = samples[i] + "_simplestlist_example.RData"
    r = pyreadr.read_r(file)

    # Extract data from simpleSTlist
    tr_counts = r['tr_counts']
    coords_df = r['spatial_meta']
    coords_df.index = coords_df.iloc[:, 0].to_list()
    coords_df = coords_df.iloc[:, 1:]
    col_names = list(r['col_names'].iloc[:, 0])
    gene_names = list(r['gene_names'].iloc[:, 0])

    # Spot/cell names in the same order?
    if col_names == coords_df.index.to_list():
        # Make AnnData object
        adata = ad.AnnData(tr_counts.transpose())
        adata.obs_names = col_names
        adata.var["genename"] = gene_names
        adata.obs["x_pixel"] = coords_df["xpos"]
        adata.obs["y_pixel"] = coords_df["ypos"]

        del col_names, gene_names # Clean env

        # Calculate adjacent matrix
        adj = spg.calculate_adj_matrix(x=adata.obs["x_pixel"].tolist(), y=adata.obs["y_pixel"].tolist(), histology=False)
        adj_fp = str(r['sample_name'].iloc[0, 0]) + '_adj_mtx.csv'
        adj_df = pd.DataFrame(adj)
        adj_df.columns = adata.obs["x_pixel"].index.to_list()
        adj_df.index = adata.obs["x_pixel"].index.to_list()
        adj_df.to_csv(adj_fp)

        del adj_fp, adj_df # Clean env

        # Filter MT genes and ERCC controls (maybe GeoMx has ERCC controls? Can't remember)
        spg.prefilter_specialgenes(adata)

        # l: Length scale, how rapidly the weight decays as a function of distance (modulates p?)
        l = spg.search_l(p, adj, start=0.01, end=1000, tol=0.01, max_run=100)

        pred_df = pd.DataFrame(adata.obs_names, columns=['libname'])

        for k in range(min_k, max_k+1):
            # Set the number of clusters
            n_clusters = k

            # Search for suitable resolution
            res = spg.search_res(adata, adj, l, n_clusters, start=0.7, step=0.1, tol=5e-3, lr=0.05, max_epochs=20, r_seed=r_seed, t_seed=t_seed, n_seed=n_seed)
            #res = 0.2

            # Set seed
            random.seed(r_seed)
            torch.manual_seed(t_seed)
            np.random.seed(n_seed)

            # Run SpaGCN
            clf = spg.SpaGCN()
            clf.set_l(l)
            clf.train(adata, adj, init_spa=True, init="louvain", res=res, tol=5e-3, lr=0.05, max_epochs=200)
            y_pred, prob = clf.predict()

            #pred_df['spagcn_k' + str(k)] = y_pred.tolist()
            pred_df['spagcn_k' + str(k)] = [x + 1 for x in y_pred.tolist()]

            # Cluster refinement (optional). Has to be gridded to be used?
            if refine_clusters:
                #adj_2d = spg.calculate_adj_matrix(x=adata.obs["x_pixel"].tolist(), y=adata.obs["y_pixel"].tolist(), histology=False)
                adj_2d = adj
                refined_pred = spg.refine(sample_id=adata.obs.index.tolist(), pred=y_pred.tolist(), dis=adj_2d, shape="hexagon")
                #pred_df['spagcn_k' + str(k) + '_refined'] = refined_pred
                pred_df['spagcn_k' + str(k) + '_refined'] = [x + 1 for x in refined_pred]

                del adj_2d, refined_pred

            del n_clusters, res, y_pred, prob, clf # Clean env

        res_fp = 'spagcn_predicted_domains_sample_' + str(r['sample_name'].iloc[0, 0]) + '.csv'
        pred_df.to_csv(res_fp, index=False)

        # Write AnnData for SVG detection
        adata.write_h5ad("anndata_" + str(r['sample_name'].iloc[0, 0]) + ".h5ad")
        del adata, l # Clean env
    else:
        print("SpaGCN error: The spot/cell IDs are not in the same order in the count and coordinate data.")
        raise DataError

