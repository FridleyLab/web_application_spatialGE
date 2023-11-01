##
# Implementation of SpaGCN analysis in spatialGE-web
#

# Install rpy2 (handle "simpleSTlist")
# pip install rpy2

# Install SpaGCN (requires python > 3.5)
# pip install SpaGCN==1.2.7

# Install anndata2ri to convert sparse matrices
# pip install anndata2ri

# Import libraries
import os
import numpy as np ## Used numpy 1.2.0 to avoid conflicts with SpaGCN
import pandas as pd ## Used pandas 1.5.3 to avoid conflicts with SpaGCN
import anndata as ad
import SpaGCN as spg
#import scanpy as sc
import anndata2ri
import random
import torch
from rpy2.robjects import r
from scipy.sparse import csr_matrix # To make count matrox sparse

# User parameters:
p = {param_p} # p: parameter is the percentage of total expression contributed by neighborhoods. If smaller area (e.g. MERFISH), then set higher p
min_k = {param_number_of_domains_min} # Minimum k value to predict
max_k = {param_number_of_domains_max} # Maximum k value to predict
user_seed = {param_user_seed}
refine_clusters = {param_refine_clusters} # Only available for Visium

# Set seeds
r_seed = t_seed = n_seed = user_seed

# Load simpleSTlist
file = "/spatialGE/simple_stlist.RData"
r['load'](file)

class DataError(Exception):
  pass

# Iterate over samples in simpleSTlist
for i in range(len(r['simple_stlist'][0])):
    # Extract data from simpleSTlist
    tr_counts = anndata2ri.scipy2ri.rpy2py(r['simple_stlist'][0][i])
    tr_counts = csr_matrix(tr_counts, dtype=np.float32)
    coords_df = anndata2ri.rpy2py(r['simple_stlist'][1][i])
    coords_df.index = coords_df.iloc[:, 0].to_list()
    coords_df = coords_df.iloc[:, 1:]
    col_names = list(r['simple_stlist'][2][i])
    gene_names = list(r['simple_stlist'][3][i])

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

            # Set seed
            random.seed(r_seed)
            torch.manual_seed(t_seed)
            np.random.seed(n_seed)

            # Run SpaGCN
            clf = spg.SpaGCN()
            clf.set_l(l)
            clf.train(adata, adj, init_spa=True, init="louvain", res=res, tol=5e-3, lr=0.05, max_epochs=200)
            y_pred, prob = clf.predict()

            pred_df['spagcn_k' + str(k)] = y_pred.tolist()

            # Cluster refinement (optional). Has to be gridded to be used?
            if refine_clusters:
                adj_2d = spg.calculate_adj_matrix(x=adata.obs["x_pixel"].tolist(), y=adata.obs["y_pixel"].tolist(), histology=False)
                refined_pred = spg.refine(sample_id=adata.obs.index.tolist(), pred=y_pred.tolist(), dis=adj_2d, shape="hexagon")
                pred_df['spagcn_k' + str(k) + '_refined'] = refined_pred

                del adj_2d, refined_pred

            del n_clusters, res, y_pred, prob, clf # Clean env

        res_fp = './spagcn_predicted_domains_sample_' + r['simple_stlist'][4][i] + '.csv'
        pred_df.to_csv(res_fp, index=False)

        del adata, l # Clean env
    else:
        print("SpaGCN error: The spot/cell IDs are not in the same order in the count and coordinate data.")
        raise DataError
