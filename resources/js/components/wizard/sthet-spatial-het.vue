<template>
    <div class="container-fluid py-4 col-xl-11 col-md-11 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-10 col-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">filter_3</i>
                        </div>
                        <div class="text-end pt-1">
                            <h6 class="mb-0 text-capitalize">STplot - Visualization</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="stplot-quilt-tab" data-bs-toggle="tab" data-bs-target="#stplot-quilt" type="button" role="tab" aria-controls="stplot-quilt" aria-selected="true">Plot</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="stplot-quilt" role="tabpanel" aria-labelledby="stplot-quilt-tab">
                                <sthet-plot :project="project" :samples="samples" :stplot-quilt-url="stplotQuiltUrl" :color-palettes="colorPalettes"></sthet-plot>
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
        name: 'sthetSpatialHet',

        props: {
            project: Object,
            samples: Object,
            colorPalettes: Object,
            sthetPlotUrl: String,
        },

        mounted() {
            console.log(this.project);
        }

    }
</script>
