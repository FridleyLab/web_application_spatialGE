<template>
    <div class="container-fluid py-4 col-xl-11 col-md-12 col-sm-12">
        <div class="row justify-content-center">
            <div class="col-xl-12 col-sm-12 mb-xl-0 mb-4 mt-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" style="position: relative; top: 31%;"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M2 5c-.55 0-1 .45-1 1v15c0 1.1.9 2 2 2h15c.55 0 1-.45 1-1s-.45-1-1-1H4c-.55 0-1-.45-1-1V6c0-.55-.45-1-1-1zm19-4H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-1 16H8c-.55 0-1-.45-1-1V4c0-.55.45-1 1-1h12c.55 0 1 .45 1 1v12c0 .55-.45 1-1 1z"/><text x="14" y="13.5" text-anchor="middle" font-family="Roboto, Arial, sans-serif" font-size="10" font-weight="500" fill="white">12</text></svg>
                        </div>
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
