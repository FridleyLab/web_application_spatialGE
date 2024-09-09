<template>
    <div class="main-container">
        <div class="left-container" ref="plotContainer">
            <PlotViewer
                ref="plotViewer"
                v-if="isPlotVisible && processedData"
                :processedData="processedData"
                :expression="expression"
                :title="title"
                :plotType="plotType"
                :colorPalette="palette"
                :legendMin="legendMin"
                :legendMax="legendMax"
                :isPanZoomLocked="isPanZoomLocked"
                :isYAxisInverted="inverted"
                :isGrouped="grouped"
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
                v-if="svgData"
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
import { ref, onMounted, watch } from "vue";
// import PlotViewer from "./PlotViewer.vue";
// import OverlayEditor from "./OverlayEditor.vue";

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
            required: true,
        },
        legendMax: {
            type: Number,
            required: true,
        },
        showImage: { type: Boolean, default: true },
        inverted: { type: Boolean, required: false },
        grouped: { type: Boolean, default: true },
    },

    setup() {
        const plotContainer = ref(null);
        const isPlotVisible = ref(false);

        const observer = new IntersectionObserver(
            ([entry]) => {
                isPlotVisible.value = entry.isIntersecting;
                if (isPlotVisible.value) {
                    observer.disconnect();
                }
            },
            { threshold: 0.1 }
        );

        onMounted(() => {
            if (plotContainer.value) {
                observer.observe(plotContainer.value);
            }
        });

        return {
            plotContainer,
            isPlotVisible,
        };
    },

    data() {
        return {
            svgData: null,
            svgPlot: null,
            container: null,
            processedData: null,
            isOverlayLocked: false,
            isPanZoomLocked: false,
            sharedState: {
                plotWidth: 0,
                plotHeight: 0,
            },
        };
    },

    provide() {
        return {
            sharedState: this.sharedState,
        };
    },

    async mounted() {
        const baseImage = new Image();
        baseImage.src = this.base;

        baseImage.onload = async () => {
            const width = baseImage.naturalWidth;
            const height = baseImage.naturalHeight;
            const aspectRatio = width / height;
            const containerWidth = window.innerWidth;
            const desiredWidth = containerWidth * 0.4;
            const desiredHeight = desiredWidth / aspectRatio;
            this.sharedState.plotWidth = desiredWidth;
            this.sharedState.plotHeight = desiredHeight;

            // const data = await d3.csv(`./${this.csv}`);
            const data = await d3.csvParse(this.csv);
            this.processedData = data;
        };
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
