<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            SPARK
        </div>
        <div>
            A non-parametric method for detection of spatially variable genes using spatial kernel modeling.
        </div>


        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="row justify-content-center text-center m-3">

                <div class="my-4">
                    <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :selected-keys="params.samples" @selected="(keys) => params.samples = keys"></project-summary-table>
                </div>

            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3">THR: <span class="text-lg text-bold text-primary">{{ params.thr }}</span> <show-modal tag="vis_quilt_plot_point_size"></show-modal></div>
                    <input type="range" min="0" max="1" step="0.01" class="w-100" v-model="params.thr">
                </div>
            </div>

        </div>

        <div class="row mt-3">
            <div class="p-3 text-end">
                <send-job-button label="RUN SPARK" :disabled="processing" :project-id="project.id" job-name="SPARK" @started="SPARK" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
            </div>
        </div>

        <!-- Create tabs for each sample-->
        <div v-if="!processing && ('spark' in project.project_parameters)" class="m-4">

            <div class="text-justify">
                <div class="fs-5">Explanation of results:</div>
                <ul>
                    <li><strong>Combined p-value:</strong> The p-value resulting from testing non-independence between gene expression and spatial localization, across multiple multiple replicates (i.e., kernels). The resulting p-values are combined using the Cauchy combination rule.</li>
                    <li><strong>Adjusted p-value:</strong> Multiple comparison p-value adjustment using the  Benjamini and Yekutieli (BY) method.</li>
                </ul>
            </div>

            <ul class="nav nav-tabs" id="mySamplesTab" role="tablist">
                <li v-for="(sample, index) in spark.samples" class="nav-item" role="presentation">
                    <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample + '-tab'" data-bs-toggle="tab" :data-bs-target="'#' + sample" type="button" role="tab" :aria-controls="sample" aria-selected="true">{{ sample }}</button>
                </li>
            </ul>

            <div class="tab-content" id="mySamplesTabContent">

                <div v-for="(sample, index) in spark.samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample" role="tabpanel" :aria-labelledby="sample + '-tab'">
                    <div class="m-4">
                        <data-grid :src="spark.base_url + spark.json_files[sample]" :allow-selection="false" :visible-columns="['gene_name', 'combined_pvalue', 'adjusted_pvalue']"></data-grid>
                    </div>
                </div>

            </div>
        </div>

    </form>
</div>
</template>
<script>

    export default {
        name: 'spark',

        props: {
            project: Object,
            samples: Object,
            sparkUrl: String,
        },

        data() {
            return {

                spark: ('spark' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spark) : {},

                processing: false,

                params: {
                    samples: [],
                    thr: 0.5,
                },
            }
        },

        watch: {

        },

        mounted() {

        },

        methods: {

            SPARK() {
                this.processing = true;
                axios.post(this.sparkUrl, this.params)
                    .then((response) => {

                    })
                    .catch((error) => {
                        console.log(error.message);
                    })
            },

            processCompleted() {
                this.processing = false;
                this.spark = ('spark' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spark) : {};
            },
        },

    }
</script>
