##
# Cr0ss-sample spatial domain detection with MILWRM (only tested on Visium so far)
#

# Env setting (conda 23.11.0):
# conda create --name milwrm_web python=3.9.16
# conda install conda - forge::gdal
# conda activate milwrm_web

# pip install numpy==1.22.4
# pip install MILWRM==1.1.1
# pip install scanpy==1.9.3
# pip install harmonypy==0.0.10
# pip install pandas==2.2.2
# pip install pyreadr==0.5.0


# Load packages
import os
import MILWRM as mw
import scanpy as sc
import numpy as np
import harmonypy as hm
import pandas as pd
import anndata as ad
import pyreadr

# User parameters:
alpha = {param_alpha}  # Slider that goes from 0.01 to 0.05
max_pc = {param_max_pc}   # Slider from 2 to 50
samples = [{param_sample_list}]

### MILWRM STARTS HERE
# Iterate over samples in simpleSTlist and create anndata objects
visium = []
#visium_map = []
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
        adata.obsm["spatial"] = np.array(adata.obs)

        del col_names, gene_names # Clean env

        visium.append(adata)
        visium[i].var_names_make_unique()
#        visium_map.append(mw.map_pixels(visium[i], filter_label="in_tissue"))

        del adata

# Combine AnnData objects
comb = visium[0].concatenate(visium[1:], fill_value=0, join="outer", batch_categories=samples)

# Scale data and perform PCA
sc.pp.scale(comb)
sc.pp.pca(comb, n_comps=50)

# Take the first 20 PCs to correct batch effects with Harmony
data_mat = comb.obsm['X_pca'][:,:20]
meta_data = comb.obs
data_mat = np.array(data_mat)
vars_use = ['batch']

# Run Harmony for batch correction
harm_corr = hm.run_harmony(data_mat, meta_data, vars_use, max_iter_harmony=10)

# Extract corrected PC scores
# Add an X and shift 1 the column names (spots/cells)
res = pd.DataFrame(harm_corr.Z_corr)
res.columns = ['X{}'.format(i + 1) for i in range(res.shape[1])]

# Transpose (spots in rows)
res = res.T

# Add corrected PCs to combined AnnData
comb.obsm['new_PCA'] = res.values

# Add the pre and post harmony PCs back to individual sample adatas
for i, adata in zip(samples, visium):
    adata.obsm['new_PCA'] = comb[comb.obs.batch==str(i),:].obsm['new_PCA']

for i, adata in zip(samples, visium):
    adata.obsm['X_pca'] = comb[comb.obs.batch==str(i),:].obsm['X_pca']

# Create tissue labeler (?)
tl = mw.st_labeler(visium)

# Preprocess data using the corrected PCs
tl.prep_cluster_data(use_rep="new_PCA", features=list(range(0, max_pc+1)), n_rings=1)

# Perform labeling of tissue regions
# The alpha parameter ranges from 0.01 to 0.05 and determines the number of tissue domains (Higher alpha, less tissue domains)
a = tl.label_tissue_regions(plot_out=False, alpha=alpha)

for i in range(len(samples)):
    res_fp = 'milwrm_predicted_domains_sample_' + samples[i] + '.csv'
    pred_df = pd.DataFrame(tl.adatas[i].obs)
#    pred_df['barcode'] = list(pred_df.index)
    pred_df['tissue_ID'] = [x + 1 for x in pred_df['tissue_ID']]
#    pred_df = pred_df[['barcode', 'x_pixel', 'y_pixel', 'tissue_ID']]
    pred_df = pred_df[['x_pixel', 'y_pixel', 'tissue_ID']]
    pred_df.rename(columns={'x_pixel': 'xpos', 'y_pixel': 'ypos', 'tissue_ID': 'milwrm_alpha'+str(alpha)}, inplace=True)
    pred_df.to_csv(res_fp, index=False)
