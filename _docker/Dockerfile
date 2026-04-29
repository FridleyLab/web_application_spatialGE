FROM r-base:4.5.3


#Install Moffitt's CA certificates
RUN apt-get update -qq && \
    apt-get dist-upgrade -y && \
    apt-get install -y --no-install-recommends \
    ca-certificates locales wget libcurl4-openssl-dev/sid libxml2-dev libssl-dev && \
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



#Install required system libraries
RUN apt-get -y update && apt-get -y install libhdf5-dev libudunits2-dev libv8-dev libgdal-dev cmake libfftw3-dev libfontconfig1-dev libcurl4-openssl-dev libglpk-dev libgsl-dev libmagick++-dev libabsl-dev libharfbuzz-dev libfribidi-dev libcairo2-dev

RUN mkdir /spatialGE
WORKDIR /spatialGE

#Install libraries required by spatialGE
RUN Rscript -e "install.packages('systemfonts', dependencies = TRUE)" && \
    Rscript -e "if (!requireNamespace('systemfonts', quietly = TRUE)) stop('systemfonts not installed')"
RUN Rscript -e "install.packages('ggforce')" && \
    Rscript -e "if (!requireNamespace('ggforce', quietly = TRUE)) stop('ggforce not installed')"
RUN Rscript -e "install.packages('ggpubr')" && \
    Rscript -e "if (!requireNamespace('ggpubr', quietly = TRUE)) stop('ggpubr not installed')"
RUN Rscript -e "install.packages('BiocManager')" && \
    Rscript -e "if (!requireNamespace('BiocManager', quietly = TRUE)) stop('BiocManager not installed')"
RUN Rscript -e "BiocManager::install('EBImage', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('EBImage', quietly = TRUE)) stop('EBImage not installed')"
RUN Rscript -e "BiocManager::install('ComplexHeatmap', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('ComplexHeatmap', quietly = TRUE)) stop('ComplexHeatmap not installed')"
RUN Rscript -e "install.packages('hdf5r')" && \
    Rscript -e "if (!requireNamespace('hdf5r', quietly = TRUE)) stop('hdf5r not installed')"
RUN Rscript -e "install.packages('remotes')" && \
    Rscript -e "if (!requireNamespace('remotes', quietly = TRUE)) stop('remotes not installed')"

#Install support libraries to work with Excel files and images
RUN Rscript -e "install.packages('openxlsx')" && \
    Rscript -e "if (!requireNamespace('openxlsx', quietly = TRUE)) stop('openxlsx not installed')"
RUN Rscript -e "install.packages('svglite')" && \
    Rscript -e "if (!requireNamespace('svglite', quietly = TRUE)) stop('svglite not installed')"
RUN Rscript -e "install.packages('rdist')" && \
    Rscript -e "if (!requireNamespace('rdist', quietly = TRUE)) stop('rdist not installed')"
RUN Rscript -e "install.packages('magick')" && \
    Rscript -e "if (!requireNamespace('magick', quietly = TRUE)) stop('magick not installed')"

RUN Rscript -e "remotes::install_github('JEFworks-Lab/STdeconvolve')" && \
    Rscript -e "if (!requireNamespace('STdeconvolve', quietly = TRUE)) stop('STdeconvolve not installed')"

RUN Rscript -e "install.packages('ggtext')" && \
    Rscript -e "if (!requireNamespace('ggtext', quietly = TRUE)) stop('ggtext not installed')"

#Required for Phenotyping-CosMx
RUN Rscript -e "BiocManager::install('SpatialDecon', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('SpatialDecon', quietly = TRUE)) stop('SpatialDecon not installed')"
#Required for InSituType
RUN Rscript -e "BiocManager::install('sparseMatrixStats', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('sparseMatrixStats', quietly = TRUE)) stop('sparseMatrixStats not installed')"
RUN Rscript -e "BiocManager::install('SummarizedExperiment', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('SummarizedExperiment', quietly = TRUE)) stop('SummarizedExperiment not installed')"
RUN Rscript -e "BiocManager::install('SingleCellExperiment', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('SingleCellExperiment', quietly = TRUE)) stop('SingleCellExperiment not installed')"
RUN Rscript -e "install.packages('lsa')" && \
    Rscript -e "if (!requireNamespace('lsa', quietly = TRUE)) stop('lsa not installed')"
#Required for Phenotyping-CosMx
RUN mkdir -p ~/.R && printf "CXX11STD = -std=gnu++14\nCXX14STD = -std=gnu++14\nCXX14 = g++\n" > ~/.R/Makevars && \
    Rscript -e "remotes::install_github('Nanostring-Biostats/InSituType')" && \
    Rscript -e "if (!requireNamespace('InSituType', quietly = TRUE)) stop('InSituType not installed')"

RUN Rscript -e "install.packages('uwot')" && \
    Rscript -e "if (!requireNamespace('uwot', quietly = TRUE)) stop('uwot not installed')"

RUN Rscript -e "install.packages('SeuratObject')" && \
    Rscript -e "if (!requireNamespace('SeuratObject', quietly = TRUE)) stop('SeuratObject not installed')"

RUN Rscript -e "BiocManager::install('GSVA', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('GSVA', quietly = TRUE)) stop('GSVA not installed')"

RUN Rscript -e "BiocManager::install('DelayedMatrixStats', update = TRUE, ask = FALSE)" && \
    Rscript -e "if (!requireNamespace('DelayedMatrixStats', quietly = TRUE)) stop('DelayedMatrixStats not installed')"

#Install spatialGE library
RUN Rscript -e "remotes::install_github('FridleyLab/spatialGE@oospina_dev')" && \
    Rscript -e "if (!requireNamespace('spatialGE', quietly = TRUE)) stop('spatialGE not installed')"



# Create a home folder for the apache user
#RUN mkdir /home/apache
# Add an entry for the "apache" user with UID 48 to /etc/passwd
#RUN echo 'apache:x:48:0::/home/apache:/bin/bash' >> /etc/passwd

# Set the desired username and UID
ARG USERNAME=apache
ARG USER_UID=48

# Create a new user with a specific UID
RUN useradd -m -u $USER_UID -g root -s /bin/bash $USERNAME

# Switch to the new user
USER $USERNAME
