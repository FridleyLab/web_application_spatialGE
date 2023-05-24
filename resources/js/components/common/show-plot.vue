<template>
    <div class="text-center m-4" :class="cssClasses">
        <div>
            <object class="img-fluid" :data="src + '.svg' + (autoReload ? '?' + Date.now() : '')" type="image/svg+xml" style="pointer-events: none;"></object>
        </div>
        <div v-if="downloadable">
            <a :href="src + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
            <a :href="src + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
            <a :href="src + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
        </div>
    </div>
</template>
<script>
    export default {
        name: 'showPlot',

        props: {
            src: String,
            autoReload: {type: Boolean, default: true},
            cssClasses: {type: String, default: ''},
            downloadable: {type: Boolean, default: true},
        },


    }
</script>
