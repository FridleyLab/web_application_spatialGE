saveplot <- function(filetitle, plot, width_ = 800, height_ = 600) {
    library('svglite')
    ggpubr::ggexport(filename = paste(filetitle,'.png', sep=''), plot, width = width_, height = height_)
    ggpubr::ggexport(filename = paste(filetitle,'.pdf', sep=''), plot, width = width_/100, height = height_/100)
    svglite(paste(filetitle,'.svg', sep=''), width = width_/100, height = height_/100)
    print(plot)
    dev.off()
}
