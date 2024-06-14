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
                            <li v-if="['VISIUM', 'GENERIC'].includes(project.platform_name)" class="nav-item" role="presentation">
                                <button class="nav-link active" id="stdeconvolve-tab" data-bs-toggle="tab" data-bs-target="#stdeconvolve" type="button" role="tab" aria-controls="stdeconvolve" aria-selected="true">STdeconvolve</button>
                            </li>
                            <li v-if="project.platform_name === 'COSMX'" class="nav-item" role="presentation">
                                <button class="nav-link active" id="insitutype-tab" data-bs-toggle="tab" data-bs-target="#insitutype" type="button" role="tab" aria-controls="insitutype" aria-selected="true">InSituType</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div v-if="['VISIUM', 'GENERIC'].includes(project.platform_name)" class="tab-pane fade show active min-vh-50" id="stdeconvolve" role="tabpanel" aria-labelledby="stdeconvolve-tab">
                                <stdeconvolve :project="project" :samples="samples" :st-deconvolve-url="stDeconvolveUrl" :st-deconvolve2-url="stDeconvolve2Url" :st-deconvolve3-url="stDeconvolve3Url" :color-palettes="colorPalettes"></stdeconvolve>
                            </div>
                            <div v-if="project.platform_name === 'COSMX'" class="tab-pane fade show active min-vh-50" id="insitutype" role="tabpanel" aria-labelledby="insitutype-tab">
                                <insitutype :project="project" :samples="samples" :in-situ-type-url="inSituTypeUrl" :in-situ-type2-url="inSituType2Url" :in-situ-type-rename-url="inSituTypeRenameUrl" :color-palettes="colorPalettes"></insitutype>
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
            stDeconvolve3Url: String,
            inSituTypeUrl: String,
            inSituType2Url: String,
            inSituTypeRenameUrl: String,
        },

    }
</script>
