FROM python:3.10-slim
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential libgl1 libglx-mesa0 && rm -rf /var/lib/apt/lists/*

WORKDIR /opt

RUN --mount=type=cache,target=/var/cache/apt \
    apt-get update && apt-get install -y --no-install-recommends git && \
    git clone https://github.com/raphael-group/CalicoST -b exponly && \
    apt-get purge -y git && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*


WORKDIR /opt/CalicoST

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

RUN apt-get update && apt-get install -y --no-install-recommends \
    python3-tk tk && rm -rf /var/lib/apt/lists/*

RUN sed -i '1iimport scipy.sparse' src/calicost/utils_IO.py
RUN sed -i 's/adata.layers\["count"\] = adata.X.A.astype(int)/adata.layers["count"] = adata.X.toarray().astype(int) if scipy.sparse.issparse(adata.X) else adata.X.astype(int)/g' src/calicost/utils_IO.py

RUN sed -i 's/np.NAN/np.nan/g' src/calicost/hmm_NB_BB_phaseswitch.py
RUN sed -i 's/np.NAN/np.nan/g' src/calicost/hmrf.py

RUN sed -i 's/smooth_mat.append( tmpsmooth_mat.A )/smooth_mat.append(tmpsmooth_mat.toarray() if scipy.sparse.issparse(tmpsmooth_mat) else tmpsmooth_mat)/g' src/calicost/utils_hmrf.py
RUN sed -i 's/adjacency_mat.append( tmpadjacency_mat.A )/adjacency_mat.append(tmpadjacency_mat.toarray() if scipy.sparse.issparse(tmpadjacency_mat) else tmpadjacency_mat)/g' src/calicost/utils_hmrf.py

RUN sed -i 's/tmp = (scipy.sparse.csr_matrix(gamma) @ mapping_matrices\[s\]).A/tmp = (scipy.sparse.csr_matrix(gamma) @ mapping_matrices\[s\]); tmp = tmp.toarray() if scipy.sparse.issparse(tmp) else tmp/g' src/calicost/utils_hmm.py

RUN sed -i 's/pdf/svg/g' src/calicost/calicost_exp_only.py

RUN sed -i 's/-shifted_coords\[idx,1\]/shifted_coords[idx,1]/g' src/calicost/utils_plotting.py

ENV PYTHONPATH=/opt/CalicoST/src

RUN rm -fr .git/

# Set the desired username and UID
ARG USERNAME=apache
ARG USER_UID=48
ARG USER_GID=48
# Create a new user with a specific UID
RUN useradd -m -u $USER_UID -g $USER_GID -s /bin/bash $USERNAME
# Switch to the new user
USER $USERNAME

ENTRYPOINT ["python", "src/calicost/calicost_exp_only.py"]
