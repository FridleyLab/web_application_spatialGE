<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_9</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0">Domain/Cell Phenotyping</h6>
                            <div>
                                <show-vignette url="/documentation/vignettes/phenotyping.pdf"></show-vignette>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="stdeconvolve-tab" data-bs-toggle="tab" data-bs-target="#stdeconvolve" type="button" role="tab" aria-controls="stdeconvolve" aria-selected="true">STdeconvolve</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="stdeconvolve" role="tabpanel" aria-labelledby="stdeconvolve-tab">
                                <stdeconvolve :project="project" :samples="samples" :st-deconvolve-url="stDeconvolveUrl" :st-deconvolve2-url="stDeconvolve2Url" :color-palettes="colorPalettes"></stdeconvolve>
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
            colorPalettes: Object,
            stDeconvolveUrl: String,
            stDeconvolve2Url: String,
        },

    }
</script>
