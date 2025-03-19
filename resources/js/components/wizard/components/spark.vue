<template>
<div class="m-4">
    <form>

        <div class="my-3 text-bold">
            SPARK-X
        </div>
        <div>
            A non-parametric method for detection of spatially variable genes using spatial kernel modeling.
        </div>
        <div>
            <strong>NOTE:</strong> Please keep in mind that the method is time-consuming and the more genes included, the more time it takes to complete.
        </div>


        <div :class="processing ? 'disabled-clicks' : ''">

            <div class="form-check mt-4 text-center">
                <label class="text-lg">
                    <input type="radio" name="method" value="genes" v-model="params.method"> Select Genes <show-modal tag="spark_x_select_genes"></show-modal>
                </label>

                <label class="text-lg ms-5">
                    <input type="radio" name="method" value="gene_sets" v-model="params.method"> Gene Sets <show-modal tag="spark_x_gene_sets"></show-modal>
                </label>

                <label class="text-lg ms-5">
                    <input type="radio" name="method" value="expression" v-model="params.method"> Select genes by expression <show-modal tag="spark_x_select_genes_by_expression"></show-modal>
                </label>
            </div>

            <div v-if="params.method === 'genes'">
                <div class="row justify-content-center text-center m-3">
                    <div class="w-100 w-md-80 w-lg-70 w-xxl-55">

                        <div>
                            <div>Search and select genes</div>
                            <div>
                                <Multiselect
                                    id="quilt-plot-gene-list"
                                    v-model="params.genes"
                                    mode="tags"
                                    placeholder="Select options"
                                    :close-on-select="true"
                                    :searchable="true"
                                    :resolve-on-load="false"
                                    :delay="0"
                                    :min-chars="1"
                                    :options="async (query) => { return await searchGenes(query) }"
                                />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div v-if="params.method === 'gene_sets'" class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>Select/upload a gene set database</div>
                    <div>
                        <span>
                            <Multiselect :options="gene_sets_options" v-model="params.gene_sets"></Multiselect>
                        </span>
                    </div>
                    <div v-if="params.gene_sets === 'upload'" class="mt-2 mb-4">
                        <div class="my-2"><a href="/resources/example_gene_sets_spatialge.gmt" class="text-primary" download>Download example gene database</a></div>
                        <div><input type="file" @change="handleFileUpload" /></div>
                    </div>
                </div>
                <div class="mt-4" v-if="params.gene_sets !== null && gene_sets_data !== null">
                    <div><strong>Optional:</strong> You can select specific gene sets. If none is selected, all will be processed</div>
                    <data-grid :scrolling-toggle="false" :headers="gene_sets_data.headers" :data="gene_sets_data.data" :show-gene-card="true" :allow-selection="true" key-attribute="name" :page-size="10" @selected="(keys) => handleSelectedGeneSets(keys)"></data-grid>
                </div>
            </div>

            <div v-if="params.method === 'expression'" class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3">THR: <span class="text-lg text-bold text-primary">{{ params.thr }}</span></div>
                    <input type="range" min="0" max="1" step="0.05" class="w-100" v-model="params.thr">
                </div>
                <div>
                    <label class="me-3 text-lg">
                        <input type="checkbox" v-model="params.subset_mean"> Subset mean
                    </label>
                </div>
            </div>

            <div class="row justify-content-center text-center m-3">

                <div class="my-4">
                    <project-summary-table :data="project.project_parameters.initial_stlist_summary" :url="project.project_parameters.initial_stlist_summary_url" :selected-keys="params.samples" @selected="(keys) => params.samples = keys"></project-summary-table>
                </div>

            </div>



        </div>

        <div class="row mt-3">
            <div class="p-3 text-end">
                <send-job-button label="RUN SPARK-X" :disabled="processing || !readyToProcess" :project-id="project.id" job-name="SPARK" @started="SPARK" @ongoing="processing = true" @completed="processCompleted" :project="project" ></send-job-button>
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

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'spark',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            sparkUrl: String,
            gmtFileUrl: String,
        },

        data() {
            return {

                gene_sets_options: [
                    {'label': '- UPLOAD your own gene database -', 'value': 'upload'},
                    {'label': 'KEGG - human', 'value': 'kegg'},
                    {'label': 'HALLMARK - human', 'value': 'hallmark'},
                    {'label': 'HALLMARK - Mouse', 'value': 'mh.all.v2023.1.Mm.symbols'},
                    {'label': 'REACTOME - Human', 'value': 'c2.cp.reactome.v2023.1.Hs.symbols'},
                    {'label': 'REACTOME - Mouse', 'value': 'm2.cp.reactome.v2023.1.Mm.symbols'},
                    {'label': 'GO - Biological process - Human', 'value': 'c5.go.bp.v2023.1.Hs.symbols'},
                    {'label': 'GO - Cellular component - Human', 'value': 'c5.go.cc.v2023.1.Hs.symbols'},
                    {'label': 'GO - Molecular function - Human', 'value': 'c5.go.mf.v2023.1.Hs.symbols'},
                    {'label': 'GO - Biological process - Mouse', 'value': 'm5.go.bp.v2023.1.Mm.symbols'},
                    {'label': 'GO - Cellular Component - Mouse', 'value': 'm5.go.cc.v2023.1.Mm.symbols'},
                    {'label': 'GO - Molecular function - Mouse', 'value': 'm5.go.mf.v2023.1.Mm.symbols'}
                ],

                spark: ('spark' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.spark) : {},

                method: 'genes',

                processing: false,

                params: {
                    method: 'genes',
                    gene_sets: null,
                    samples: [],
                    genes: [],
                    thr: 0.9,
                    subset_mean: true,
                    user_gene_sets: null,
                    selected_gene_sets: []
                },

                gene_sets_data: null,
            }
        },

        watch: {
            'params.method': function() {
                if(this.params.method !== 'gene_sets') {
                    this.gene_sets_data = null;
                }
            },

            'params.gene_sets': function() {
                this.gene_sets_data = null;
                this.params.user_gene_sets = null;
                this.gmtTemplateSelected();
            },

            'params.user_gene_sets': function() {
                this.gene_sets_data = null;
                this.gmtTemplateSelected();
            }
        },

        mounted() {

        },

        computed: {
            readyToProcess() {
                return (this.params.method === 'genes' && this.params.genes.length > 0) ||
                    (this.params.method === 'gene_sets' && this.params.gene_sets !== '') ||
                    (this.params.method === 'expression' && this.params.thr >=0 && this.params.thr <= 1);
            },
        },

        methods: {

            SPARK() {
                this.processing = true;

                if(this.params.method !== 'gene_sets') {
                    this.params.gene_sets = null;
                }

                const formData = new FormData();

                for(let param in this.params) {
                    if(this.params[param] !== null) {
                        formData.append(param, this.params[param]);
                        console.log(param, this.params[param]);
                    }
                }



                axios.post(this.sparkUrl, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
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

            searchGenes: async function(query) {

                const response = await fetch(
                    '/projects/' + this.project.id + '/search-genes?context=N&query=' + query
                );

                const data = await response.json(); // Here you have the data that you need

                return data.map((item) => {
                    return { value: item, label: item }
                })
            },

            handleFileUpload(event) {
                this.params.user_gene_sets = event.target.files[0];
            },

            gmtTemplateSelected() {
                if(this.params.method === 'gene_sets') {

                    if(this.params.gene_sets === 'upload' && this.params.user_gene_sets === null) {
                        return;
                    }

                    const formData = new FormData();

                    formData.append('gene_sets', this.params.gene_sets);
                    formData.append('user_gene_sets', this.params.user_gene_sets);

                    // let params = {gene_sets: this.params.gene_sets};

                    axios.post(this.gmtFileUrl, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                        .then((response) => {
                            console.log(response.data);
                            this.gene_sets_data = {};
                            this.gene_sets_data.data = response.data;
                            this.gene_sets_data.headers = [{value: 'count', text: '# genes'}, {value: 'name', text: 'Gene set'},{value: 'gene_list', text: 'Genes'}];
                        })
                        .catch((error) => {
                            console.log(error.message);
                        });
                }
            },

            handleSelectedGeneSets(keys) {
                this.params.selected_gene_sets = keys;
                console.log(keys);
            }
        },

    }
</script>
