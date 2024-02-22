<template>
    <div>
        <ul class="nav nav-tabs" :id="'STdeconvolve_myTab' + idKey" role="tablist">
            <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'STdeconvolve-tab' + idKey" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'STdeconvolve' + idKey" type="button" role="tab" :aria-controls="sample.name + 'STdeconvolve' + idKey" aria-selected="true">{{ sample.name }}</button>
            </li>
        </ul>

        <div class="tab-content" :id="'STdeconvolve_myTabContent' + idKey">
            <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'STdeconvolve' + idKey" role="tabpanel" :aria-labelledby="sample.name + 'STdeconvolve-tab' + idKey">

                <div class="row justify-content-center text-center m-4">
                    <div class="w-80">
                        <div class="me-3">
                            <label class="text-lg">
                                Suggested K={{STdeconvolve.suggested_k[sample.name]}}&nbsp;&nbsp;
                            </label>
                            <span v-if="editable && STdeconvolve.suggested_k[sample.name] !== STdeconvolve.selected_k[sample.name]" class="mx-2 text-warning">Modified K=</span>
                            <input v-if="editable" type="number" :min="STdeconvolve.parameters.min_k" :max="STdeconvolve.parameters.max_k" class="text-end text-md border border-1 rounded w-15 w-md-10 w-xl-10" size="3" v-model="STdeconvolve.selected_k[sample.name]" @change="check_k(sample.name)">
                            <!-- <span v-if="editable && STdeconvolve.suggested_k[sample.name] !== STdeconvolve.selected_k[sample.name]" class="mx-2 text-warning">modified</span> -->
                        </div>
                        <input v-if="editable" type="range" :min="STdeconvolve.parameters.min_k" :max="STdeconvolve.parameters.max_k" step="1" class="w-100" v-model="STdeconvolve.selected_k[sample.name]">
                    </div>
                </div>

                <div v-for="image in STdeconvolve.plots">
                    <show-plot v-if="image.includes(sample.name)" :src="image" :sample="sample"></show-plot>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'stdeconvolve_suggested_ks',

    props: {
        samples: Object,
        STdeconvolve: Object,
        editable: {type: Boolean, default: false},
        idKey: String,
    },

    methods: {
        check_k(sampleName) {
            if(this.STdeconvolve.selected_k[sampleName] < this.STdeconvolve.parameters.min_k) this.STdeconvolve.selected_k[sampleName] = this.STdeconvolve.parameters.min_k;
            if(this.STdeconvolve.selected_k[sampleName] > this.STdeconvolve.parameters.max_k) this.STdeconvolve.selected_k[sampleName] = this.STdeconvolve.parameters.max_k;
        }
    }
}
</script>
