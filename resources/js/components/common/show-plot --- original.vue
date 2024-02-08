<template>
    <div class="text-center m-4" :class="cssClasses">
        <div :class="showTissue ? 'd-xxl-flex' : ''">
            <div :class="showTissue ? 'w-50' : ''">
                <object class="img-fluid" :data="src + '.svg' + (autoReload ? '?' + Date.now() : '')" type="image/svg+xml" style="pointer-events: none;"></object>
            </div>
            <div v-if="showTissue" class="w-50">
                <img class="img-fluid" :src="sample.image_file_url" alt="Tissue image" @dblclick="window.open('/')" />
            </div>
        </div>
        <div v-if="downloadable" class="mt-2">
            <a :href="srcName + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
            <a :href="srcName + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
            <a :href="srcName + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
            <label v-if="sideBySide && showTissue" class="ms-3"><input type="checkbox" v-model="sbs"> Quilt plot with H&E image <show-modal v-if="sideBySideToolTip.length" :tag="sideBySideToolTip"></show-modal></label>
        </div>

    </div>
</template>
<script>

import { SvgPanZoom } from "vue-svg-pan-zoom";

    export default {
        name: 'showPlot',

        components: {
            SvgPanZoom,
        },

        props: {
            src: String,
            autoReload: {type: Boolean, default: true},
            cssClasses: {type: String, default: ''},
            downloadable: {type: Boolean, default: true},
            showImage: {type: Boolean, default: false},
            sample: {type: Object, default: null},
            sideBySide: {type: Boolean, default: false},
            sideBySideToolTip: {type: String, default: ''},
        },

        data(){
            return {
                sbs: false
            }
        },

        computed: {
            srcName() {
                return this.src + (this.sbs ? '-sbs' : '');
            },

            showTissue() {
                return this.showImage && this.sample !== null && this.sample.has_image;
            }
        },

    }
</script>
