<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            Principal Component Analysis - PCA
        </div>
        <div>
            The following PCA plot has been created by calculating the average expression of genes within each sample. This PCA does not incorporate any spatial component of the data.
        </div>



        <div class="mt-4">
            <ul class="nav nav-tabs" id="filterDiagrams" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="nd-boxplot-tab" data-bs-toggle="tab" data-bs-target="#nd-bloxplot" type="button" role="tab" aria-controls="nd-bloxplot" aria-selected="true">Boxplot</button>
                </li>
            </ul>
            <div class="tab-content" id="filterDiagramsContent">
                <div class="tab-pane fade show active" id="nd-bloxplot" role="tabpanel" aria-labelledby="nd-bloxplot-tab">
                    <div class="m-4">
                        Color by:
                        <select class="border border-1 rounded p-1">
                            <option>Age</option>
                            <option>Gender</option>
                        </select>
                    </div>
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
            </div>
        </div>



    </form>
</div>
</template>
<script>
    export default {
        name: 'qcDtPca',

        props: {
            samples: Object,
            filterUrl: String
        },

        data() {
            return {

                processing: false,

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
