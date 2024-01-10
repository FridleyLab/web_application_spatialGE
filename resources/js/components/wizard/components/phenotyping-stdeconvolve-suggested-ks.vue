<template>
    <div>
        <ul class="nav nav-tabs" :id="'STdeconvolve_myTab' + key" role="tablist">
            <li v-for="(sample, index) in samples" class="nav-item" role="presentation">
                <button class="nav-link" :class="index === 0 ? 'active' : ''" :id="sample.name + 'STdeconvolve-tab' + key" data-bs-toggle="tab" :data-bs-target="'#' + sample.name + 'STdeconvolve' + key" type="button" role="tab" :aria-controls="sample.name + 'STdeconvolve' + key" aria-selected="true">{{ sample.name }}</button>
            </li>
        </ul>

        <div class="tab-content" :id="'STdeconvolve_myTabContent' + key">
            <div v-for="(sample, index) in samples" class="tab-pane fade min-vh-50" :class="index === 0 ? 'show active' : ''" :id="sample.name + 'STdeconvolve' + key" role="tabpanel" :aria-labelledby="sample.name + 'STdeconvolve-tab' + key">

                <div class="row justify-content-center text-center m-4">
                    <div class="w-50">
                        <div class="me-3">
                            <label class="text-lg">
                                Suggested K={{STdeconvolve.suggested_k[sample.name]}}&nbsp;&nbsp;
                            </label>
                            <input v-if="editable" type="number" class="text-end text-md border border-1 rounded w-25 w-md-15 w-xl-10" size="3" v-model="STdeconvolve.selected_k[sample.name]">
                            <span v-if="editable && STdeconvolve.suggested_k[sample.name] !== STdeconvolve.selected_k[sample.name]" class="mx-2 text-warning">modified</span>
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
        key: String,
    },
}
</script>
