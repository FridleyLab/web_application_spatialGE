<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="text-end pt-1">
                            <h6 class="mb-0">Cell-cell interaction</h6>
                        </div>
                    </div>

                    <div class="card-body">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="moran-multi-tab" data-bs-toggle="tab" data-bs-target="#moran-multi" type="button" role="tab" aria-controls="moran-multi" aria-selected="true">Cell-cell interaction</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active min-vh-50" id="moran-multi" role="tabpanel" aria-labelledby="moran-multi-tab">
                                <moran-multi :project="project" :samples="samples" :moran-multi-url="moranMultiUrl"></moran-multi>
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
        name: 'cellCellInteraction',

        props: {
            project: Object,
            samples: Object,
            moranMultiUrl: String,
        },

    }
</script>
