<template>
    <div class="text-center m-4" :class="cssClasses">
        <div :class="showTissue ? 'd-xxl-flex' : ''">
            <div :class="showTissue ? 'w-50' : ''">

                <object v-if="!zoomEnabled" class="img-fluid" :data="src + '.svg' + (autoReload ? '?' + Date.now() : '')" type="image/svg+xml" style="pointer-events: none;"></object>


                <SvgPanZoom v-if="zoomEnabled && svgData"

                :zoomEnabled="true"
                :controlIconsEnabled="true"
                :fit="true"
                :center="true"
                >
                    <svg v-html="svgData" xmlns="http://www.w3.org/2000/svg"  style="min-height: 600px; width:100%"></svg>
                </SvgPanZoom>



            </div>
            <div v-if="showTissue" class="w-50">
                <img class="img-fluid" :src="sample.image_file_url" alt="Tissue image" @dblclick="window.open('/')" />
            </div>











        </div>
        <div v-if="downloadable" class="mt-2">
            <button type="button" class="btn btn-sm me-4" :class="zoomEnabled ? 'btn-success' : 'btn-outline-secondary'" @click="zoomEnabled = !zoomEnabled">Zoom</button>
            <a :href="srcName + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
            <a :href="srcName + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
            <a :href="srcName + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
            <label v-if="sideBySide && showTissue" class="ms-3"><input type="checkbox" v-model="sbs"> Quilt plot with H&E image <show-modal v-if="sideBySideToolTip.length" :tag="sideBySideToolTip"></show-modal></label>
        </div>

    </div>

    <div>

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
                sbs: false,

                svgData: null,
                zoomEnabled: false,
            }
        },

        mounted() {
            axios.get(this.src + '.svg')
                .then(response => {
                    this.svgData = response.data;

                    // this.svgData = this.svgData.replace(/<\?xml [^>]*>/, '');
                    // this.svgData = this.svgData.replace(/<svg[^>]*>|<\/svg>/g, '');

                });
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
