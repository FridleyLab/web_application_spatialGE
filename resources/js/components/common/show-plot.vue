<template>
    <div class="text-center m-4" :class="cssClasses">
        <div :class="showTissue ? 'd-xxl-flex' : ''">
            <div :class="showTissue ? 'w-50' : ''">

                <object ref="svgObject" v-if="isSVG && !zoomEnabled" class="img-fluid" :data="src + '.' + fileExtension + (autoReload ? '?' + Date.now() : '')" type="image/svg+xml" style="pointer-events: none;"></object>

                <img v-if="!isSVG" :src="src + '.' + fileExtension + (autoReload ? '?' + Date.now() : '')" class="img-fluid" />

                <SvgPanZoom v-if="isSVG && zoomEnabled && svgData"

                :zoomEnabled="true"
                :controlIconsEnabled="true"
                :fit="true"
                :center="true"
                >
                    <svg v-html="svgData" xmlns="http://www.w3.org/2000/svg"  style="min-height: 600px; width:100%"></svg>
                </SvgPanZoom>



            </div>
            <div v-if="showTissue" class="ms-2 w-50">
                <img class="img-fluid" :src="sample.image_file_url" alt="Tissue image" @dblclick="window.open('/')" />
            </div>



        </div>
        <div v-if="downloadable" class="mt-2">
            <button v-if="isSVG" type="button" class="btn btn-sm me-4" :class="zoomEnabled ? 'btn-success' : 'btn-outline-secondary'" @click="zoomEnabled = !zoomEnabled">Zoom</button>
            <!-- <button type="button" class="btn btn-secondary btn-sm me-4" @click="generatePDF">SVG-PDF</button> -->
            <a :href="srcName + '.pdf'" class="btn btn-sm btn-outline-info me-2" download>PDF</a>
            <a :href="srcName + '.png'" class="btn btn-sm btn-outline-info me-2" download>PNG</a>
            <a :href="srcName + '.svg'" class="btn btn-sm btn-outline-info" download>SVG</a>
            <label v-if="sideBySide && showTissue" class="ms-3"><input type="checkbox" v-model="sbs"> Quilt plot with H&E image <show-modal v-if="sideBySideToolTip.length" :tag="sideBySideToolTip"></show-modal></label>
        </div>

    </div>

    <div v-if="false && showTissue" class="text-center m-4" :class="cssClasses">
        <div ref="container" class="container">
            <img class="image" :src="sample.image_file_url" alt="Tissue image">
            <!-- <object id="svgPlot" class="svgPlot" :data="src + '.svg' + (autoReload ? '?' + Date.now() : '')" type="image/svg+xml"
                @mousedown="startDrag" @mouseup="endDrag" @mousemove="drag" @mouseleave="endDrag"></object> -->

            <svg v-html="svgData ? svgData.replace('fill: #FFFFFF', 'fill: none').replace('stroke: #364B9A;','fill: none;').replace('stroke: #364B9A;','') : ''" xmlns="http://www.w3.org/2000/svg"
                ref="svgPlot" class="svgPlot" @mousedown="startDrag" @mouseup="endDrag" @mousemove="drag" @mouseleave="endDrag" @mousewheel="zoom"
            ></svg>
        </div>
        <div>
            <!-- <button @click="alignLeft">Align Left</button>
            <button @click="alignRight">Align Right</button>
            <button @click="alignCenter">Align Center</button>
            <br>
            <button @click="moveLeft">Move Left</button>
            <button @click="moveRight">Move Right</button>
            <button @click="moveUp">Move Up</button>
            <button @click="moveDown">Move Down</button> -->
            <br>
            <button type="button" @click="zoomOut">Zoom Out</button>
            <button type="button" @click="zoomIn">Zoom In</button>
        </div>
    </div>
</template>
<script>

import { SvgPanZoom } from "vue-svg-pan-zoom";

// import jsPDF from 'jspdf';
// import 'svg2pdf.js';


    export default {
        name: 'showPlot',

        components: {
            SvgPanZoom,
        },

        props: {
            src: String,
            fileExtension: {type: String, default: 'svg'},
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


                zoomFactor: 0,


                svgPlot: null,
                container: null,
                scaleFactor: 1,
                offsetX: 0,
                offsetY: 0,
                isDragging: false,
                dragStartX: 0,
                dragStartY: 0,


            }
        },

        mounted() {
            if(this.isSVG) {
                axios.get(this.src + '.' + this.fileExtension + (this.autoReload ? '?' + Date.now() : ''))
                    .then(response => {
                        this.svgData = response.data;

                        this.svgPlot = this.$refs.svgPlot;
                        this.container = this.$refs.container;

                        // this.svgData = this.svgData.replace(/<\?xml [^>]*>/, '');
                        // this.svgData = this.svgData.replace(/<svg[^>]*>|<\/svg>/g, '');

                    });
            }



                /*this.svgPlot.addEventListener('mousedown', this.startDrag);
                this.svgPlot.addEventListener('mouseup', this.endDrag);
                this.svgPlot.addEventListener('mousemove', this.drag);
                this.svgPlot.addEventListener('mouseleave', this.endDrag);
                this.svgPlot.addEventListener('wheel', this.zoom);*/
        },

        computed: {
            srcName() {
                return this.src + (this.sbs ? '-sbs' : '');
            },

            showTissue() {
                return this.showImage && this.sample !== null && this.sample.has_image;
            },

            isSVG() {
                return this.fileExtension === 'svg';
            }
        },

        methods: {
            zoom(event) {
                event.preventDefault();
                this.scaleFactor += event.deltaY * -0.001;
                this.scaleFactor = Math.min(Math.max(0.1, this.scaleFactor), 4);
                this.updateTransform();
            },

            zoomIn() {
                this.scaleFactor += 0.05;
                this.scaleFactor = Math.min(Math.max(0.1, this.scaleFactor), 4);
                this.updateTransform();
            },

            zoomOut() {
                this.scaleFactor -= 0.05;
                this.scaleFactor = Math.min(Math.max(0.1, this.scaleFactor), 4);
                this.updateTransform();
            },

            startDrag(event) {
                console.log("startDrag");
                console.log(event);
                this.isDragging = true;
                this.dragStartX = event.clientX;
                this.dragStartY = event.clientY;
            },

            endDrag() {
                this.isDragging = false;
            },

            drag(event) {
                if (!this.isDragging) return;
                let dx = event.clientX - this.dragStartX;
                let dy = event.clientY - this.dragStartY;
                this.offsetX += dx;
                this.offsetY += dy;
                this.dragStartX = event.clientX;
                this.dragStartY = event.clientY;
                this.updateTransform();
            },

            updateTransform() {
                this.svgPlot.style.transform = `translate(${this.offsetX}px, ${this.offsetY}px) scale(${this.scaleFactor})`;
            },


            // generatePDF() {

            //     // Create a new jsPDF instance
            //     const doc = new jsPDF();

            //     // Create a canvas element
            //     const canvas = document.createElement('canvas');
            //     canvas.width = 300; // Adjust width as needed
            //     canvas.height = 300; // Adjust height as needed

            //     // Get the context of the canvas
            //     const ctx = canvas.getContext('2d');

            //     // Create an image from the SVG data
            //     const img = new Image(1000, 600);
            //     img.src = 'data:image/svg+xml;base64,' + btoa(this.svgData); // Convert SVG data to base64

            //     // Draw the image onto the canvas when it's loaded
            //     img.onload = () => {
            //         ctx.drawImage(img, 0, 0);

            //         // Convert canvas to a data URL
            //         const canvasDataURL = canvas.toDataURL('image/png');

            //         // Add the canvas as an image to the PDF
            //         doc.addImage(canvasDataURL, 'PNG', 5, 5, 100, 70); // Adjust coordinates and dimensions as needed

            //         // Save the PDF
            //         doc.save('exported.pdf');
            //     };
            // },



        }

    }
</script>

<style scoped>

.container {
    position: relative;
    width: 800px; /* Adjust according to your image and SVG plot size */
    height: 600px; /* Adjust according to your image and SVG plot size */
    overflow: hidden;
}
.image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 100%;
}
.svgPlot {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: move;
    opacity: 100%;

}

</style>
