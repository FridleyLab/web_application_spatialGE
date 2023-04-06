install.packages('BiocManager')

BiocManager::install('edgeR', update = TRUE, ask = FALSE)
BiocManager::install('EBImage', update = TRUE, ask = FALSE)

# Install spatialGE
install.packages('rgeos')
install.packages('hdf5r')
install.packages('remotes')
remotes::install_github('FridleyLab/spatialGE@oospina_dev')
