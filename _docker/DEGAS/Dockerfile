# Use the official R image with system dependencies
FROM rocker/r-ver:4.3.0

#Install Moffitt's CA certificates
RUN apt-get update -qq && \
    apt-get upgrade -y && \
    apt-get clean all && \
    apt-get install -y --no-install-recommends \
        ca-certificates locales wget libcurl4-openssl-dev libxml2-dev libssl-dev && \
    apt-get clean && \
    apt-get purge && \
    rm -rf /var/lib/apt/lists/* && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-ca.crt \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-ca.cer" && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-ca-int.crt \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-ca-int.cer" && \
    wget --no-check-certificate -O /usr/local/share/ca-certificates/moffitt-chain \
      "https://gitlab.moffitt.usf.edu:8000/singularity/mcc-certificates/-/raw/main/certs/moffitt-chain" && \
    chmod 644 /usr/local/share/ca-certificates/*moffitt* && \
    update-ca-certificates --verbose && \
        echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen && locale-gen en_US.utf8 && update-locale LANG=en_US.UTF-8

# Install system dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        python3-pip \
        python3-venv \
        python3-dev \
        libcurl4-openssl-dev \
        libssl-dev \
        libxml2-dev \
        libgit2-dev \
        libglpk-dev \
        libhdf5-dev \
        libzmq3-dev \
        libmariadb-dev \
        libharfbuzz-dev \
        libfribidi-dev \
        libfreetype6-dev \
        libpng-dev \
        libtiff5-dev \
        libjpeg-dev \
        && rm -rf /var/lib/apt/lists/*

# Install R packages
RUN R -e "install.packages(c('magrittr','tidyr','dplyr','tibble','Rtsne','ggplot2','Matrix'), repos='https://cloud.r-project.org/')"
RUN R -e "if (!require('remotes')) install.packages('remotes', repos='https://cloud.r-project.org/')"
RUN R -e "remotes::install_github('tsteelejohnson91/DEGAS')"

RUN R -e "if (!requireNamespace('BiocManager', quietly=TRUE)) install.packages('BiocManager')" && \
    R -e "BiocManager::install('limma', ask=FALSE)"


RUN R -e "if (!require('TCGAretriever')) install.packages('TCGAretriever', repos='https://cloud.r-project.org/')"


# Install Miniconda
ENV CONDA_DIR=/opt/conda
RUN wget --quiet https://repo.anaconda.com/miniconda/Miniconda3-latest-Linux-x86_64.sh -O ~/miniconda.sh && \
    bash ~/miniconda.sh -b -p $CONDA_DIR && \
    rm ~/miniconda.sh && \
    $CONDA_DIR/bin/conda clean -t -i -p -y && \
    ln -s $CONDA_DIR/etc/profile.d/conda.sh /etc/profile.d/conda.sh
ENV PATH=$CONDA_DIR/bin:$PATH

# Create conda environment for DEGAS
# RUN conda create -y -n degas_env python=3.9 tensorflow=2.5.0 && \
#     $CONDA_DIR/bin/conda run -n degas_env pip install numpy==1.23.5 && \
#     conda clean -a
RUN conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/main && \
    conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/r && \
    conda create -y -n degas_env python=3.9 tensorflow=2.5.0 && \
    $CONDA_DIR/bin/conda run -n degas_env pip install numpy==1.23.5 && \
    conda clean -afy


# Set working directory
WORKDIR /spatialGE

# Copy scripts into the container
#COPY degas_spatialge_part_1_tcga.R ./
#COPY degas_spatialge_part_2_pred.R ./

# Set environment so R can find conda python
ENV RETICULATE_PYTHON=/opt/conda/envs/degas_env/bin/python

# Run both scripts
CMD ["Rscript", "degas_spatialge_part_1_tcga.R"]
