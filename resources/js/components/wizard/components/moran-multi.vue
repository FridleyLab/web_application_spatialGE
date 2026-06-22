<template>
<div class="m-4">
    <form>

        <div :class="processing ? 'disabled-clicks' : ''">
            <div class="my-3 text-bold">
                Cell-cell interaction (Multivariate Moran's I)
            </div>
            <div>
                Analyze spatial co-expression patterns between two genes (e.g., ligand-receptor pairs) across
                multiple spatial scales. This method computes a multivariate Moran's I statistic using kernel-based
                bandwidths to detect distance-dependent interaction patterns between gene pairs.
            </div>

            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>
                        <div>Gene 1 (Ligand)</div>
                        <div>
                            <Multiselect
                                id="moran-gene1"
                                v-model="params.gene1"
                                mode="single"
                                placeholder="Search and select a gene"
                                :close-on-select="true"
                                :searchable="true"
                                :resolve-on-load="true"
                                :delay="0"
                                :min-chars="0"
                                :options="async (query) => { return await searchGenes(query, params.gene1) }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-3">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div>
                        <div>Gene 2 (Receptor)</div>
                        <div>
                            <Multiselect
                                id="moran-gene2"
                                v-model="params.gene2"
                                mode="single"
                                placeholder="Search and select a gene"
                                :close-on-select="true"
                                :searchable="true"
                                :resolve-on-load="true"
                                :delay="0"
                                :min-chars="0"
                                :options="async (query) => { return await searchGenes(query, params.gene2) }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3">Maximum bandwidth: <span class="text-lg text-bold text-primary">{{ params.max_h }}</span></div>
                    <input type="range" min="50" max="500" step="50" class="w-100" v-model="params.max_h">
                </div>
            </div>

            <div class="row justify-content-center text-center m-4">
                <div class="w-100 w-md-80 w-lg-70 w-xxl-55">
                    <div class="me-3">Bandwidth increment: <span class="text-lg text-bold text-primary">{{ params.inc_h }}</span></div>
                    <input type="range" min="1" max="100" step="1" class="w-100" v-model="params.inc_h">
                </div>
            </div>

        </div>

        <div class="p-3 text-center mt-4">
            <send-job-button
                label="Run analysis"
                :disabled="processing || !params.gene1 || !params.gene2"
                :project-id="project.id"
                job-name="MoranMulti"
                @started="runMoranMulti"
                @ongoing="processing = true"
                @completed="processCompleted"
                :project="project"
            ></send-job-button>
        </div>


        <div v-if="!processing && ('moran_multi' in project.project_parameters)" class="p-3 mt-4">

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-interaction" data-bs-toggle="tab" data-bs-target="#interaction-panel" type="button" role="tab" aria-controls="interaction-panel" aria-selected="true">Cell-cell interaction</button>
                </li>
                <li v-for="(sample, index) in moranMulti.samples" :key="'tab-'+sample" class="nav-item" role="presentation">
                    <button class="nav-link" :id="'tab-'+sample" data-bs-toggle="tab" :data-bs-target="'#panel-'+sample" type="button" role="tab" :aria-controls="'panel-'+sample" aria-selected="false">{{ sample }}</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active min-vh-50" id="interaction-panel" role="tabpanel" aria-labelledby="tab-interaction">
                    <div class="text-center my-4">
                        <show-plot :src="moranMulti.base_url + 'moran_multi_interaction'"></show-plot>
                    </div>
                </div>

                <div v-for="(sample, index) in moranMulti.samples" :key="'panel-'+sample" class="tab-pane fade min-vh-50" :id="'panel-'+sample" role="tabpanel" :aria-labelledby="'tab-'+sample">
                    <div class="text-center my-4">
                        <show-plot :src="moranMulti.base_url + 'moran_multi_perms_' + sample"></show-plot>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a :href="moranMulti.base_url + 'moran_multi_results.csv'" class="text-primary" download>Download results CSV</a>
            </div>

        </div>

    </form>
</div>
</template>
<script>

import Multiselect from '@vueform/multiselect';

    export default {
        name: 'moranMulti',

        components: {
            Multiselect,
        },

        props: {
            project: Object,
            samples: Object,
            moranMultiUrl: String,
        },

        data() {
            return {
                moranMulti: ('moran_multi' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.moran_multi) : {},

                processing: false,

                params: {
                    gene1: ('moran_multi' in this.project.project_parameters) && JSON.parse(this.project.project_parameters.moran_multi).params ? JSON.parse(this.project.project_parameters.moran_multi).params.gene1 : null,
                    gene2: ('moran_multi' in this.project.project_parameters) && JSON.parse(this.project.project_parameters.moran_multi).params ? JSON.parse(this.project.project_parameters.moran_multi).params.gene2 : null,
                    max_h: ('moran_multi' in this.project.project_parameters) && JSON.parse(this.project.project_parameters.moran_multi).params ? JSON.parse(this.project.project_parameters.moran_multi).params.max_h : 250,
                    inc_h: ('moran_multi' in this.project.project_parameters) && JSON.parse(this.project.project_parameters.moran_multi).params ? JSON.parse(this.project.project_parameters.moran_multi).params.inc_h : 10,
                },
            }
        },

        methods: {

            searchGenes: async function(query, currentValue) {
                if (!query && currentValue) {
                    return [{ value: currentValue, label: currentValue }];
                }
                if (!query) return [];

                const response = await fetch(
                    '/projects/' + this.project.id + '/search-genes?context=N&query=' + query
                );
                const data = await response.json();
                return data.map((item) => {
                    return { value: item, label: item }
                })
            },

            runMoranMulti() {
                this.processing = true;
                axios.post(this.moranMultiUrl, this.params)
                    .then((response) => {
                    })
                    .catch((error) => {
                        this.processing = false;
                        console.log(error.message);
                    })
            },

            processCompleted() {
                this.moranMulti = ('moran_multi' in this.project.project_parameters) ? JSON.parse(this.project.project_parameters.moran_multi) : {};
                this.processing = false;
            },
        },

    }
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
<style>
:root {
    --ms-placeholder-color: #3B82F6;
    --ms-border-color-active: #3B82F6;
    --ms-ring-color: #3B82F630;
    --ms-spinner-color: #3B82F6;
    --ms-tag-bg: #3B82F6;
    --ms-tag-color: #FFFFFF;
    --ms-tag-radius: 9999px;
    --ms-tag-font-weight: 400;

    --ms-option-bg-selected: #3B82F6;
    --ms-option-bg-selected-pointed: #3B82F6;
}
</style>
