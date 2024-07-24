<template>
    <div class="main-container">
        <div class="left-container">
            <PlotViewer
                ref="plotViewer"
                v-if="processedData"
                :processedData="processedData"
                :expression="expression"
                :title="title"
                :colorPalette="palette"
                :legendMin="legendMin"
                :legendMax="legendMax"
                :isPanZoomLocked="isPanZoomLocked"
                @update-svg="handleSvgUpdate"
            ></PlotViewer>
        </div>
        <div class="right-container">
            <div class="toggle-sync form-check form-switch">
                <input
                    style="cursor: pointer"
                    class="form-check-input"
                    type="checkbox"
                    id="lockPanZoomSwitch"
                    @change="toggleIsPanZoomLocked"
                    :disabled="!isOverlayLocked"
                />
                <label class="form-check-label" for="lockPanZoomSwitch">{{
                    isPanZoomLocked
                        ? "Unlock Pan and Zoom"
                        : "Lock Pan and Zoom"
                }}</label>
            </div>
            <OverlayEditor
                ref="overlayEditor"
                :base="base"
                :overlay="svgData"
                :show-image="showImage"
                @zoom-pan="handleZoomPan"
                @toggle-lock="handleToggleLock"
                @update-overlay-width="handleOverlayWidthUpdate"
                @update-plot="handleUpdatePlot"
            ></OverlayEditor>
        </div>
    </div>
</template>

<script>
import * as d3 from "d3";
// import PlotViewer from "./PlotViewer.vue";
// import OverlayEditor from "./OverlayEditor.vue";
import { sharedState } from "./state.js";

export default {
    name: "editorPlot",

    // components: {
    //     PlotViewer,
    //     OverlayEditor,
    // },

    props: {
        base: {
            type: String,
            required: true,
        },
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
        palette: {
            type: Object,
            required: true,
        },
        legendMin: {
            type: Number,
            required: true,
        },
        legendMax: {
            type: Number,
            required: true,
        },
        showImage: { type: Boolean, default: true },
    },

    data() {
        return {
            svgData: null,
            svgPlot: null,
            container: null,
            processedData: null,
            isOverlayLocked: false,
            isPanZoomLocked: false,
        };
    },

    provide() {
        return {
            sharedState,
        };
    },

    async mounted() {
        // const data = await d3.csv(`./${this.csv}`);
        const data = await d3.csvParse(this.csv);
        this.processedData = data;
    },

    methods: {
        handleSvgUpdate(svgString) {
            this.svgData = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(
                svgString
            )}`;
        },

        handleToggleLock(isLocked) {
            this.isOverlayLocked = isLocked;
        },

        handleZoomPan(transform) {
            if (this.isPanZoomLocked && this.$refs.plotViewer) {
                this.$refs.plotViewer.applyTransformElementIntoZoomAndPan(
                    transform.transformElement
                );
            }
        },

        handleOverlayWidthUpdate(overlayWidthUpdate) {
            if (this.$refs.plotViewer) {
                this.$refs.plotViewer.overlayWidthUpdate(overlayWidthUpdate);
            }
        },

        handleUpdatePlot() {
            if (this.$refs.plotViewer) {
                console.log("RECREATING");
                this.$refs.plotViewer.recreatePlot();
            }
        },

        toggleIsPanZoomLocked() {
            this.isPanZoomLocked = !this.isPanZoomLocked;
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
</style>
