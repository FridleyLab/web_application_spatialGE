<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <!-- <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_7</i>
                        </div> -->
                        <div class="text-end pt-1">
                            <h6 class="mb-0">DEGAS</h6>
                            <!-- <div>
                                <show-vignette url="/documentation/vignettes/phenotyping.pdf"></show-vignette>
                            </div> -->
                        </div>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="degas-tab" data-bs-toggle="tab" data-bs-target="#degas" type="button" role="tab" aria-controls="degas" aria-selected="true">DEGAS</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="degas" role="tabpanel" aria-labelledby="degas-tab">
                                <degas-tab :project="project" :project-samples="samples" :color-palettes="colorPalettes" :tcga-variables="tcgaVariables" :degas2-url="degas2Url"></degas-tab>
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
        name: 'phenotyping',

        props: {
            project: Object,
            samples: Object,
            tcgaVariables: Object,
            degas2Url: String,
            colorPalettes: Object,
        },

    }
</script>
