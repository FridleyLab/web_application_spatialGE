FROM r-base:4.1.2

#Install required system libraries
RUN apt-get -y update && apt-get -y install libhdf5-dev libudunits2-dev libv8-dev libgdal-dev cmake libfftw3-dev libfontconfig1-dev libcurl4-openssl-dev libglpk-dev libgsl-dev libmagick++-dev

RUN mkdir /spatialGE
WORKDIR /spatialGE

#Install libraries required by spatialGE
RUN Rscript -e "install.packages('systemfonts', dependencies = TRUE)"
RUN Rscript -e "install.packages('ggforce')"
RUN Rscript -e "install.packages('ggpubr')"
RUN Rscript -e "install.packages('BiocManager')"
RUN Rscript -e "BiocManager::install('EBImage', update = TRUE, ask = FALSE)"
RUN Rscript -e "BiocManager::install('ComplexHeatmap', update = TRUE, ask = FALSE)"
RUN Rscript -e "install.packages('rgeos')"
RUN Rscript -e "install.packages('hdf5r')"
RUN Rscript -e "install.packages('remotes')"

#Install support libraries ro work with Excel files and images
RUN Rscript -e "install.packages('openxlsx')"
RUN Rscript -e "install.packages('svglite')"
RUN Rscript -e "install.packages('rdist')"
RUN Rscript -e "install.packages('magick')"

RUN Rscript -e "remotes::install_github('JEFworks-Lab/STdeconvolve')"


#Install spatialGE library
RUN Rscript -e "remotes::install_github('FridleyLab/spatialGE@oospina_dev')"


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
