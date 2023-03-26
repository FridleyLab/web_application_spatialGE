<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Select a method:
        </div>
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        Log-normal
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>The Log-normal method</strong> uses... <code>features</code>, etc.
                        </div>
                        <div class="my-3">
                            <label class="form-label">Scaling factor:</label> <input type="number" value="10000" class="text-end text-sm border border-1 rounded w-10">
                        </div>
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-outline-info">Use this method</a>
                        </div>
                    </div>

                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        SCT
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>The SCT method</strong> is... <code>attributes</code>, etc.
                        </div>
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-outline-info">Use this method</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        Quantile
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                    <div class="accordion-body">
                        <div>
                            <strong>The Quantile method</strong> creates... <code>variables</code>, etc.
                        </div>
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-outline-info">Use this method</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="mt-4">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">Boxplot</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="nd-violinplot-tab" data-bs-toggle="tab" data-bs-target="#nd-violinplot" type="button" role="tab" aria-controls="nd-violinplot" aria-selected="false">Violin plots</button>
                </li>
<!--                <li class="nav-item" role="presentation">-->
<!--                    <button class="nav-link" id="nd-histograms-tab" data-bs-toggle="tab" data-bs-target="#nd-histograms" type="button" role="tab" aria-controls="nd-histograms" aria-selected="false">Histograms</button>-->
<!--                </li>-->
<!--                <li class="nav-item" role="presentation">-->
<!--                    <button class="nav-link" id="nd-densityplot-tab" data-bs-toggle="tab" data-bs-target="#nd-densityplot" type="button" role="tab" aria-controls="nd-densityplot" aria-selected="false">Density plots</button>-->
<!--                </li>-->

            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">
                    <div class="m-4">
                        Color palette:
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Blue-Red
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Yellow-Orange
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Rainbow
                        </label>
                    </div>
                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
                    </div>
                </div>
                <div class="tab-pane fade" id="nd-violinplot" role="tabpanel" aria-labelledby="nd-violinplot-tab">
                    <div class="m-4">
                        Color palette:
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Blue-Red
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Yellow-Orange
                        </label>
                        <label class="m-2">
                            <input type="radio" class="" name="xyz"> Rainbow
                        </label>
                    </div>
                    <div class="text-center">
                        <pre>



                        Under development (spatialGE library)



                        </pre>
                    </div>
                </div>
<!--                <div class="tab-pane fade" id="nd-histograms" role="tabpanel" aria-labelledby="nd-histograms-tab">-->
<!--                    <div class="m-4">-->
<!--                        Color palette:-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Blue-Red-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Yellow-Orange-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Rainbow-->
<!--                        </label>-->
<!--                    </div>-->
<!--                    <div class="text-center">-->
<!--                        <pre>-->



<!--                        Under development (spatialGE library)-->



<!--                        </pre>-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="tab-pane fade" id="nd-densityplot" role="tabpanel" aria-labelledby="nd-densityplot-tab">-->
<!--                    <div class="m-4">-->
<!--                        Color palette:-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Blue-Red-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Yellow-Orange-->
<!--                        </label>-->
<!--                        <label class="m-2">-->
<!--                            <input type="radio" class="" name="xyz"> Rainbow-->
<!--                        </label>-->
<!--                    </div>-->
<!--                    <div class="text-center">-->
<!--                        <pre>-->



<!--                        Under development (spatialGE library)-->



<!--                        </pre>-->
<!--                    </div>-->
<!--                </div>-->
            </div>
        </div>



    </form>
</div>
</template>
<script>
    export default {
        name: 'qcDtNormalize',

        props: {
            samples: Object,
            filterUrl: String
        },

        data() {
            return {

                range: [-1,5],

                params: {
                    spot_minreads: 0,
                    spot_maxreads: 10000,

                    spot_mingenes: 0,
                    spot_maxgenes: 40000,

                    gene_minreads: 0,
                    gene_maxreads: 10000,

                    gene_minspots: 0,
                    gene_maxspots: 6000,

                    spot_minpct: 0,
                    spot_maxpct: 100,
                    spot_pct_expr: '',

                    gene_minpct: 0,
                    gene_maxpct: 100,
                },

                processing: false,

                //samplesToProcess: [],

                textOutput: ''
            }
        },



        methods: {

            startProcess() {

                this.processing = true;

                axios.post(this.filterUrl)
                    .then((response) => {
                        //document.getElementById("imgResult").src = 'data:image/png;base64,' + response.data.image;

                        //this.textOutput = response.data.output;

                        console.log(response.data);

                        this.processing = false;
                    })
                    .catch((error) => {
                        console.log(error.message)
                        this.processing = false;
                    })


            },
        },

    }
</script>
