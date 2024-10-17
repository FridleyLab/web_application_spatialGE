<template>
    <div class="form-check form-switch" v-if="!plotType === 'pca'">
           <input
               style="cursor: pointer"
               class="form-check-input"
               type="checkbox"
               id="toggleControls"
               @change="toggleShowPlot"
               :checked="showPlot"
           />
           <label class="form-check-label" for="toggleControls">
               {{ showPlot ?  "Hide": `Compute and Render ${title}`}}
           </label>
   </div>
   <div class="main-container" v-if="showPlot || plotType === 'pca'">
       <div class="left-container" ref="plotContainer">
           <PlotViewer
               ref="plotViewer"
               v-if="processedData && (plotType === 'umap' || plotType === 'flight' || plotType === 'gradient' || plotType === 'cluster')"
               :processedData="processedData"
               :expression="expression"
               :title="title"
               :plotType="plotType"
               :colorPalette="palette"
               :legendMin="legendMin"
               :legendMax="legendMax"
               :isPanZoomLocked="isPanZoomLocked"
               :isYAxisInverted="inverted"
               :labelData="labelData"
               @update-svg="handleSvgUpdate"
           ></PlotViewer>
           <ViolinPlot v-if="isViolinPlot && processedData"
           :data="processedData"
           :colorPalette="palette"
           :plotType="plotType"
           ></ViolinPlot>
           <PCAPlot
           v-if="plotType === 'pca' && processedData"
           :data="processedData"
           :colorPalette="palette"
           :plotType="plotType"
           />

       </div>
   </div>
</template>

<script>
import * as d3 from "d3";
import { ref, onMounted, watch } from "vue";
import PlotViewer from "./PlotViewer.vue";
import ViolinPlot from "./ViolinPlot.vue";
import PCAPlot from "./PCAPlot.vue";


const PlotTypes = Object.freeze({
   GRADIENT: "gradient",
   CLUSTER: "cluster",
   FLIGHT: "flight",
   UMAP: "umap",
   VIOLIN: "violin",
   BOX: "box",
   PCA: "pca"
});


export default {
   name: "plotHolder",

   components: {
       PlotViewer,
       ViolinPlot,
       PCAPlot
   },

   props: {
       csv: {
           type: String,
           required: true,
       },
       expression: {
           type: String,
           required: true,
       },
       title: {
           type: String,
           required: true,
       },
       plotType: {
           type: String,
           required: true,
       },
       palette: {
           type: Object,
           required: true,
       },
       legendMin: {
           type: Number,
           default: 0,
       },
       legendMax: {
           type: Number,
           default: 10,
       },
       labelCsv: {
           type: String,
           required: false,
       },
       showImage: { type: Boolean, default: true },
       inverted: { type: Boolean, required: true },
   },

   data() {
       return {
           svgData: null,
           svgPlot: null,
           container: null,
           processedData: null,
           isOverlayLocked: false,
           isPanZoomLocked: false,
           defaultQuiltSvg: null,
           sharedState: {
               plotWidth: "100px",
               plotHeight: "100px",
           },
           showPlot: false,
           labelData: null,
           isViolinPlot: null
       };
   },

   provide() {
       return {
           sharedState: this.sharedState,
       };
   },

   async mounted() {
       this.isViolinPlot = this.plotType === PlotTypes.VIOLIN || this.plotType === PlotTypes.BOX ? true : false
       const data = await d3.csv(this.csv); // replace endpoint URI
    //    const data = await d3.csv(`http://10.201.21.159:8001/download-csv/${this.csv}`); // replace endpoint URI
       this.processedData = data;
       this.sharedState.plotWidth = "800";
       this.sharedState.plotHeight = "800";

       if(this.plotType === "flight"){
           this.labelData = await d3.csvParse(`${this.labelCsv}`) // replace endpoint URI
       }
   },

   methods: {
       toggleShowPlot() {
           this.showPlot = !this.showPlot;
       },
   },
};
</script>

<style>
.main-container {
   display: flex;
   width: 100%;
   height: 100%;
}

.left-container,
.right-container {
   flex: 1;
   border: 1px solid black;
   display: flex;
   justify-content: center;
   align-items: center;
   position: relative;
}

.right-container .toggle-sync {
   position: absolute;
   z-index: 1;
   top: 10px;
   right: 20px;
   border-radius: 5px;
   display: flex;
   align-items: center;
}

.toggle-sync > .form-check-label {
   margin-left: 10px;
}

.right-container {
   background-color: #ffffff;
   display: flex;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   padding: 10px;
   box-sizing: border-box;
   position: relative;
}

.form-group label {
   margin-right: 10px;
}

.toggle-controls {
   position: absolute;
   align-items: center;
   border-radius: 8px;
}
</style>
