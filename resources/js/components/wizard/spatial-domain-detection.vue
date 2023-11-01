<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_6</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">Spatial domain detection</h6>
                            <div>
                                <show-vignette url="/documentation/vignettes/spatial_domain_detection.pdf"></show-vignette>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="sdd-stclust-tab" data-bs-toggle="tab" data-bs-target="#sdd-stclust" type="button" role="tab" aria-controls="sdd-stclust" aria-selected="true">STclust</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sdd-spagcn-tab" data-bs-toggle="tab" data-bs-target="#sdd-spagcn" type="button" role="tab" aria-controls="sdd-spagcn" aria-selected="false">SpaGCN</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sdd-milwrm-tab" data-bs-toggle="tab" data-bs-target="#sdd-milwrm" type="button" role="tab" aria-controls="sdd-milwrm" aria-selected="false">MILWRM</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="sdd-stclust" role="tabpanel" aria-labelledby="sdd-stclust-tab">
                                <sdd-stclust :project="project" :samples="samples" :sdd-stclust-url="sddStclustUrl" :color-palettes="colorPalettes"></sdd-stclust>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="sdd-spagcn" role="tabpanel" aria-labelledby="sdd-spagcn-tab">
                                <sdd-spagcn :project="project" :samples="samples" :sdd-spagcn-url="sddSpagcnUrl"></sdd-spagcn>
                            </div>
                            <div class="tab-pane fade min-vh-50" id="sdd-milwrm" role="tabpanel" aria-labelledby="sdd-milwrm-tab">
                                Coming soon
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'spatialDomainDetection',

        props: {
            project: Object,
            samples: Object,
            colorPalettes: Object,
            sddStclustUrl: String,
            sddSpagcnUrl: String,

        },

        mounted() {
            //console.log(this.project);
        }

    }
</script>
