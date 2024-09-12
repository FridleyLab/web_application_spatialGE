<template>
    <div
        class="main-container-plot-viewer"
        :class="{ 'single-container': !base }"
        ref="mainContainer"
    >
        <div
            class="toggle-controls-plot-viewer form-check form-switch"
            v-if="!isStatic"
        >
            <input
                style="cursor: pointer"
                class="form-check-input"
                type="checkbox"
                :id="pKey + 'toggleControls'"
                @change="toggleControls"
                :checked="showControls"
            />
            <label class="form-check-label" :for="pKey + 'toggleControls'">
                {{ showControls ? "Hide Controls" : "Show Controls" }}
            </label>
        </div>

        <div class="container-plot-viewer" ref="leftContainer">
            <div class="title-container-plot-viewer">
                <label>{{ title }}</label>
            </div>
            <div
                class="controls-left-container-plot-viewer px-4"
                v-if="showControls"
            >
                <div class="mb-1">
                    <label :for="pKey + 'pointSizeSlider'">Point size</label>
                    <br />
                    <input
                        type="range"
                        :id="pKey + 'pointSizeSlider'"
                        min="1"
                        max="5"
                        step="0.1"
                        v-model="pointSize"
                        @input="updatePointSize"

                    />
                </div>
                <div class="mb-1" v-if="plotType === 'gradient'">
                    <label :for="pKey + 'expressionThresholdSlider'"
                        >Expression Threshold: {{ expressionThreshold }}</label
                    >
                    <br />
                    <input
                        type="range"
                        :id="pKey + 'expressionThresholdSlider'"
                        min="0"
                        step="0.1"
                        :max="maxValue"
                        v-model="expressionThreshold"
                        @input="handleThresholdChange"

                    />
                </div>
            </div>

            <svg :id="pKey + 'svgContainerLeft'">
                <g :id="pKey + 'overlayLeftGroup'">
                    <rect
                        :id="pKey + 'dragAreaLeft'"
                        width="100%"
                        height="100%"
                        fill="transparent"
                    ></rect>
                    <g :id="pKey + 'overlayLeft'" class="overlay"></g>
                </g>
            </svg>

            <div
                class="legend-container-plot-viewer"
                :id="pKey + 'legend-container-plot-viewer'"
            ></div>
        </div>

        <div class="container-plot-viewer" ref="rightContainer" v-if="base">
            <div class="minimap-plot-viewer" v-if="isMinimapVisible && base">
                <svg :width="minimapWidth" :height="minimapHeight">
                    <image :href="base" width="100%" height="100%"></image>
                    <rect
                        :x="minimapViewportX"
                        :y="minimapViewportY"
                        :width="minimapViewportWidth"
                        :height="minimapViewportHeight"
                        fill="none"
                        stroke="red"
                        stroke-width="2"
                    ></rect>
                </svg>
            </div>

            <svg :id="pKey + 'svgContainer'">
                <g :id="pKey + 'baseAndOverlayGroup'">
                    <image
                        :id="pKey + 'imageBase'"
                        class="image-base-plot-viewer"
                        :href="base"
                        @load="onBaseImageLoad"
                        x="100"
                        y="100"
                        :width="baseImageWidth"
                        :height="baseImageHeight"
                        :style="{ opacity: baseOpacity }"
                    ></image>
                    <rect
                        :id="pKey + 'dragArea'"
                        width="100%"
                        height="100%"
                        style="cursor: move"
                        fill="transparent"
                    ></rect>
                    <g
                        :id="pKey + 'overlay'"
                        class="overlay-plot-viewer"
                        :style="{ opacity: overlayOpacity }"
                    ></g>
                </g>
            </svg>

            <div class="sliders-panel-plot-viewer" v-if="showControls">
                <div class="slider-group">
                    <label :for="pKey + 'opacitySlider'">Overlay Opacity</label>
                    <br />
                    <input
                        :id="pKey + 'opacitySlider'"
                        type="range"
                        min="0"
                        max="1"
                        step="0.1"
                        v-model="overlayOpacity"
                    />
                    <br />
                    <label :for="pKey + 'baseSlider'">Base Opacity</label>
                    <br />
                    <input
                        :id="pKey + 'baseOpacity'"
                        type="range"
                        min="0"
                        max="1"
                        step="0.1"
                        v-model="baseOpacity"
                    />
                </div>
            </div>
        </div>

        <div class="button-container-plot-viewer" v-if="showControls">
            <div class="btn-group-vertical">
                <button
                    class="btn btn-sm btn-info"
                    :id="pKey + 'syncButton'"
                    @click.prevent="toggleSync"
                    title="Synchronize"
                    v-if="base"
                >
                    <i :class="isSynced ? 'fas fa-unlink' : 'fas fa-link'"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    :id="pKey + 'zoomInButton'"
                    :class="{ disabled: !isSynced && base }"
                    @click.prevent="zoomIn"
                    title="Zoom In"
                >
                    <i class="fas fa-search-plus"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    :id="pKey + 'zoomOutButton'"
                    :class="{ disabled: !isSynced && base }"
                    @click.prevent="zoomOut"
                    title="Zoom Out"
                >
                    <i class="fas fa-search-minus"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    :id="pKey + 'resetZoomButton'"
                    :class="{ disabled: !isSynced && base }"
                    @click.prevent="resetZoom"
                    title="Reset Zoom"
                >
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    :id="pKey + 'increaseSize'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="increaseSize"
                    title="Increase Overlay Size"
                    v-if="base"
                >
                    <i class="fas fa-expand"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    :id="pKey + 'decreaseSize'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="decreaseSize"
                    title="Decrease Overlay Size"
                    v-if="base"
                >
                    <i class="fas fa-compress"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    :id="pKey + 'moveUpButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="moveOverlay('up')"
                    title="Move Up"
                    v-if="base"
                >
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    :id="pKey + 'moveDownButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="moveOverlay('down')"
                    title="Move Down"
                    v-if="base"
                >
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    :id="pKey + 'moveLeftButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="moveOverlay('left')"
                    title="Move Left"
                    v-if="base"
                >
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    :id="pKey + 'moveRightButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="moveOverlay('right')"
                    title="Move Right"
                    v-if="base"
                >
                    <i class="fas fa-arrow-right"></i>
                </button>

                <button
                    class="btn btn-sm btn-secondary"
                    :id="pKey + 'increaseWidthButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="increaseWidth"
                    title="Increase Width"
                    v-if="base"
                >
                    <i class="fas fa-arrows-alt-h"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    :id="pKey + 'decreaseWidthButton'"
                    :class="{ disabled: isSynced }"
                    @click.prevent="decreaseWidth"
                    title="Decrease Width"
                    v-if="base"
                >
                    <i class="fas fa-compress-arrows-alt"></i>
                </button>

                <!-- <button
                    class="btn btn-sm btn-secondary"
                    :id="pKey + 'importPositionButton'"
                    @click.prevent="importPositions"
                    title="Import Position"
                    v-if="base"
                >
                    <i class="fas fa-file-import"></i>
                </button> -->

                <div class="btn-group export-group-plot-viewer">
                    <button
                        class="btn btn-sm btn-info dropdown-toggle"
                        type="button"
                        :id="pKey + 'exportDropdown'"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        :class="{ disabled: !isSynced && this.base }"
                    >
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <ul
                        class="dropdown-menu"
                        :aria-labelledby="pKey + 'exportDropdown'"
                    >
                        <li>
                            <select
                                v-model="selectedGraphic"
                                class="form-select"
                            >
                                <option value="left">Left Graphic</option>
                                <option value="right" v-if="base">
                                    Right Graphic
                                </option>
                                <option value="both" v-if="base">Both</option>
                            </select>
                        </li>
                        <li class="mt-2">
                            <select
                                v-model="selectedExportType"
                                class="form-select"
                            >
                                <option value="png">PNG</option>
                                <option value="pdf">PDF</option>
                                <option value="svg">SVG</option>
                            </select>
                        </li>
                        <li class="mt-2">
                            <button
                                class="btn btn-sm btn-primary w-100"
                                @click.prevent="exportGraphics"
                            >
                                Export
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import "@fortawesome/fontawesome-free/css/all.css";
import "bootstrap/dist/js/bootstrap.js";
import * as d3 from "d3";

const PlotTypes = Object.freeze({
    GRADIENT: "gradient",
    CLUSTER: "cluster",
});

export default {
    name: "PlotsComponent",
    props: {
        base: {
            type: String,
            required: true,
        },
        csv: {
            type: String,
            required: true,
        },
        colorPalette: {
            type: Object,
            required: true,
        },
        isYAxisInverted: {
            type: Boolean,
            default: false,
        },
        title: { type: String, required: true },
        plotType: { type: String, required: true },
        isGrouped: { type: Boolean, default: false },
        legendMin: { type: Number, required: true },
        legendMax: { type: Number, required: true },
        pKey: { type: String, required: true },
        aspectRatio: { type: String, required: false },
        isStatic: { type: Boolean, required: false },
    },

    data() {
        return {
            processedData: [],
            filteredData: [],
            isSynced: false,
            currentTransform: d3.zoomIdentity,
            pointScale: 1,
            points: [],
            containerWidth: 0,
            containerHeight: 0,
            isMounted: false,
            showControls: true,

            baseImageWidth: 0,
            baseImageHeight: 0,
            drawHeight: 0,
            drawWidth: 0,
            drawOffsetWidth: 0,
            graphicWidth: 400,
            graphicHeight: 300,
            overlayOpacity: 1,
            baseOpacity: 1,
            pointSize: 2.2,
            expressionThreshold: 0,

            activeColors: {},
            groupedPalette: {},

            minimapWidth: 100,
            minimapHeight: 100,
            minimapViewportX: 0,
            minimapViewportY: 0,
            minimapViewportWidth: 0,
            minimapViewportHeight: 0,

            selectedGraphic: "left",
            selectedExportType: "svg",

            maxValue: 0,
        };
    },

    computed: {
        isMinimapVisible() {
            return this.isSynced && this.currentTransform.k > 1;
        },
    },

    watch: {
        pointSize() {
            this.updatePoints(
                d3.select("#" + this.pKey + "overlay").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
            this.updatePoints(
                d3.select("#" + this.pKey + "overlayLeft").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
        },

        expressionThreshold() {
            this.filterData();
            this.updatePoints(
                d3.select("#" + this.pKey + "overlay").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
            this.updatePoints(
                d3.select("#" + this.pKey + "overlayLeft").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
        },

        colorPalette() {

            if (this.plotType === PlotTypes.GRADIENT) {
                this.createGradientLegend();
            } else if (this.plotType === PlotTypes.CLUSTER) {
                this.createClusterLegend();
            }

            this.initializeActiveColors();
            if (this.isGrouped) this.groupColorPalette();

            this.drawPoints("#" + this.pKey + "overlay");
            this.drawPoints("#" + this.pKey + "overlayLeft");
        },
    },

    mounted() {
        this.observeVisibility();
    },

    methods: {
        /**
         * Observes the component's visibility on the page.
         * Triggers the initialization process when the component becomes visible.
         */
        observeVisibility() {
            const observer = new IntersectionObserver(
                (entries) => {
                    const entry = entries[0];
                    if (entry.isIntersecting && !this.isMounted) {
                        this.isMounted = true;
                        this.initializeComponent();
                        observer.disconnect();
                    }
                },
                { threshold: 0.1 }
            );

            observer.observe(this.$el);
        },

        /**
         * Handles the loading of the base image.
         * Adjusts the image size if it exceeds predefined dimensions.
         * Initializes the component once the image has fully loaded.
         */
        onBaseImageLoad() {
            const img = new Image();
            img.src = this.base;

            img.onload = () => {
                this.baseImageWidth = img.width;
                this.baseImageHeight = img.height;
                this.drawWidth = this.baseImageWidth;
                this.drawHeight = this.baseImageHeight;

                if (this.baseImageWidth > 1000 || this.baseImageHeight > 1000) {
                    this.baseImageWidth = this.baseImageWidth / 10;
                    this.baseImageHeight = this.baseImageHeight / 10;

                    this.drawWidth = this.baseImageWidth * 0.8;
                    this.drawHeight = this.baseImageHeight * 0.8;

                    d3.select("#" + this.pKey + "overlay").attr(
                        "transform",
                        `translate(100,100)`
                    );
                    d3.select("#" + this.pKey + "overlayLeft").attr(
                        "transform",
                        `translate(100,100)`
                    );
                }

                d3.select("#" + this.pKey + "svgContainer")
                    .attr("width", this.baseImageWidth)
                    .attr("height", this.baseImageHeight)
                    .attr(
                        "viewBox",
                        `0 0 ${this.baseImageWidth} ${this.baseImageHeight}`
                    );

                d3.select("#" + this.pKey + "svgContainerLeft")
                    .attr("width", this.baseImageWidth)
                    .attr("height", this.baseImageHeight)
                    .attr(
                        "viewBox",
                        `0 0 ${this.baseImageWidth} ${this.baseImageHeight}`
                    );

                this.initializeComponent();
            };
        },

        /**
         * Initializes the component by loading data, drawing points, center the plots and enables drag functionality.
         * This function is called once the component is fully visible.
         *
         * @returns {Promise<void>} A promise that resolves when the component is fully initialized.
         */
        async initializeComponent() {
            if (this.plotType === PlotTypes.CLUSTER)
                this.initializeActiveColors();
            if (this.isGrouped) this.groupColorPalette();

            const leftContainer = this.$refs.leftContainer;
            this.containerWidth = leftContainer.clientWidth;
            this.containerHeight = leftContainer.clientHeight;

            await this.loadCSVData();

            if (!this.base) {
                const mainContainer = this.$refs.mainContainer;

                this.baseImageWidth = mainContainer.clientWidth;
                this.baseImageHeight = mainContainer.clientHeight;
                this.drawWidth =
                    this.aspectRatio === "3:2"
                        ? mainContainer.clientWidth * 1.5
                        : mainContainer.clientWidth;
                this.drawHeight = mainContainer.clientHeight;

                d3.select("#" + this.pKey + "svgContainerLeft")
                    .attr("width", this.baseImageWidth)
                    .attr("height", this.baseImageHeight)
                    .attr(
                        "viewBox",
                        `0 0 ${this.baseImageWidth} ${this.baseImageHeight}`
                    );

                this.drawPoints("#" + this.pKey + "overlayLeft");

                if (!this.isStatic) {
                    this.leftZoom = d3.zoom().on("zoom", (event) => {
                        d3.select("#" + this.pKey + "overlayLeftGroup").attr(
                            "transform",
                            event.transform
                        );
                    });
                    d3.select("#" + this.pKey + "svgContainerLeft").call(
                        this.leftZoom
                    );
                    d3.select("#" + this.pKey + "dragAreaLeft").style(
                        "cursor",
                        "move"
                    );
                } else {
                    this.showControls = false;
                }

                if (this.aspectRatio === "3:2") {
                    this.adjustScaleToFitPoints();
                } else {
                    this.centerGraphics();
                }
            } else {
                this.drawPoints("#" + this.pKey + "overlay");
                this.drawPoints("#" + this.pKey + "overlayLeft");
                this.centerGraphics();

                if (!this.isSynced) {
                    d3.select("#" + this.pKey + "overlay").call(
                        this.dragOverlay()
                    );
                    d3.select("#" + this.pKey + "dragArea").call(
                        this.dragOverlay()
                    );
                }
            }
        },

        /**
         * Loads and processes the CSV data for plotting.
         * Converts the CSV data into a format suitable for visualization.
         *
         * @returns {Promise<void>} A promise that resolves when the data is fully loaded and processed.
         */
        async loadCSVData() {
            // const data = await d3.csv(this.csv);
            const data = await d3.csvParse(this.csv);
            this.processedData = data.map((d) => ({
                xpos: +d.xpos,
                ypos: +d.ypos,
                value: isNaN(Object.values(data[0])[2])
                    ? Object.values(d)[2]
                    : +Object.values(d)[2],
            }));

            this.filteredData = [...this.processedData];

            if (this.plotType === PlotTypes.GRADIENT)
                this.maxValue = d3.max(this.processedData, (d) => d.value);
        },

        /**
         * Initializes the active color mappings based on the provided color palette.
         * This is only necessesary when plotType is CLUSTER
         */
        initializeActiveColors() {
            for (const key in this.colorPalette) {
                this.activeColors[this.colorPalette[key].color] = true;
            }
        },

        /**
         * Organizes the color palette into groups if the data is grouped.
         * Creates a grouped palette for easier management of colors.
         * This is only necessesary when plotType is CLUSTER and isGrouped is true.
         */
        groupColorPalette() {
            if (this.isGrouped) {
                const groupedItems = {};
                for (const [key, colorObject] of Object.entries(
                    this.colorPalette
                )) {
                    if (!groupedItems[colorObject.label]) {
                        groupedItems[colorObject.label] = {
                            ...colorObject,
                            groupedKeys: [key],
                        };
                    } else {
                        groupedItems[colorObject.label].groupedKeys.push(key);
                    }
                }
                this.groupedPalette = groupedItems;
            }
        },

        /**
         * Updates the expression threshold based on the user's input.
         * Triggers this method when the slider input changes.
         *
         * @param {Event} e - The input event object.
         */
        handleThresholdChange(e) {
            this.expressionThreshold = e.target.value;
        },

        /**
         * Filters the dataset according to the current expression threshold.
         * Ensures that only data meeting the threshold is displayed.
         */
        filterData() {
            this.filteredData = this.processedData.filter(
                (d) => d.value >= this.expressionThreshold
            );
        },

        /**
         * Centers the graphical elements within the container.
         * Adjusts their position based on the current scale and container size.
         */
        centerGraphics() {
            const offsetX = -70;
            const offsetY = -30;

            this.currentTransform = d3.zoomIdentity
                .translate(offsetX, offsetY)
                .scale(0.9);

            d3.select("#" + this.pKey + "baseAndOverlayGroup").attr(
                "transform",
                this.currentTransform
            );

            d3.select("#" + this.pKey + "overlayLeftGroup").attr(
                "transform",
                this.currentTransform
            );
        },

        adjustScaleToFitPoints() {
            const boundingBox = d3
                .select(`#${this.pKey}overlayLeft`)
                .node()
                .getBBox();
            const leftContainer = this.$refs.leftContainer;

            const containerWidth = leftContainer.clientWidth;
            const containerHeight = leftContainer.clientHeight;
            const gradientWidth = 60;
            const availableWidth = containerWidth - gradientWidth;

            const scaleX = availableWidth / boundingBox.width;
            const scaleY = containerHeight / boundingBox.height;
            const scale = Math.min(scaleX, scaleY) * 0.9;

            this.currentTransform = d3.zoomIdentity
                .translate(
                    (availableWidth - boundingBox.width * scale) / 2 +
                        gradientWidth,
                    (containerHeight - boundingBox.height * scale) / 2
                )
                .scale(scale);

            d3.select(`#${this.pKey}overlayLeftGroup`).attr(
                "transform",
                this.currentTransform
            );
        },

        /**
         * Renders the data points onto the specified SVG element.
         * Uses the processed data and scaling functions to accurately position the points.
         *
         * @param {string} svgId - The ID of the SVG element where the points will be drawn.
         */
        drawPoints(svgId) {
            if (this.processedData.length === 0) return;

            const svg = d3
                .select(svgId)
                .attr("width", this.drawWidth)
                .attr("height", this.drawHeight)
                .attr("viewBox", `0 0 ${this.drawWidth} ${this.drawHeight}`);

            svg.selectAll("g").remove();

            this.xScale = d3
                .scaleLinear()
                .domain([0, d3.max(this.processedData, (d) => d.xpos)])
                .range([0, this.drawWidth + this.drawOffsetWidth]);

            this.yScale = d3
                .scaleLinear()
                .domain(
                    this.isYAxisInverted
                        ? [0, d3.max(this.processedData, (d) => d.ypos)]
                        : [d3.max(this.processedData, (d) => d.ypos), 0]
                )
                .range([this.drawHeight, 0]);

            this.colorScale = d3
                .scaleLinear()
                .domain([
                    0,
                    d3.max(this.processedData, (d) => d.value) / 2,
                    d3.max(this.processedData, (d) => d.value),
                ])
                .range(this.colorPalette);

            const g = svg.append("g");

            this.updatePoints(g, this.xScale, this.yScale, this.colorScale);

            if (this.plotType === PlotTypes.GRADIENT) {
                this.createGradientLegend();
            } else if (this.plotType === PlotTypes.CLUSTER) {
                this.createClusterLegend();
            }
        },

        /**
         * Updates the points in the provided SVG group.
         * Refreshes their positions, sizes, and colors based on the latest data.
         *
         * @param {Object} g - The D3 selection of the SVG group to update.
         * @param {Object} xScale - The D3 scale for the x-axis.
         * @param {Object} yScale - The D3 scale for the y-axis.
         */
        updatePoints(g, xScale, yScale) {
            const groupedColors = {};
            if (this.isGrouped) {
                Object.values(this.groupedPalette).forEach((colorObject) => {
                    if (!groupedColors[colorObject.label]) {
                        groupedColors[colorObject.label] = colorObject.color;
                    }
                });
            }

            g.selectAll("circle")
                .data(this.filteredData)
                .join(
                    (enter) =>
                        enter
                            .append("circle")
                            .attr("cx", (d) => xScale(d.xpos))
                            .attr("cy", (d) => yScale(d.ypos))
                            .attr("r", this.pointSize)
                            .style("fill", (d) => {
                                if (this.plotType === PlotTypes.GRADIENT) {
                                    return this.colorScale(d.value);
                                } else if (this.isGrouped) {
                                    return d.value in this.colorPalette
                                        ? groupedColors[
                                              this.colorPalette[d.value].label
                                          ]
                                        : 0;
                                } else {
                                    return this.colorPalette[d.value].color;
                                }
                            }),
                    (update) =>
                        update
                            .attr("cx", (d) => xScale(d.xpos))
                            .attr("cy", (d) => yScale(d.ypos))
                            .attr("r", this.pointSize)
                            .style("fill", (d) => {
                                if (this.plotType === PlotTypes.GRADIENT) {
                                    return this.colorScale(d.value);
                                } else if (this.isGrouped) {
                                    return d.value in this.colorPalette
                                        ? groupedColors[
                                              this.colorPalette[d.value].label
                                          ]
                                        : 0;
                                } else {
                                    return this.colorPalette[d.value].color;
                                }
                            }),
                    (exit) => exit.remove()
                );
        },

        /**
         * Toggles the visibility of data points in the plot.
         * Responds to user interactions with the legend by adjusting the displayed data.
         * This is only necessesary when plotType is CLUSTER
         *
         * @param {string} color - The color of the legend item that was clicked.
         */
        handleLegendClick(color) {
            const isActive = this.activeColors[color];
            this.activeColors[color] = !isActive;

            if (this.isGrouped) {
                const targetLabel = Object.values(this.colorPalette).find(
                    (colorObject) => colorObject.color === color
                )?.label;

                Object.values(this.colorPalette).forEach((colorObject) => {
                    if (colorObject.label === targetLabel) {
                        this.activeColors[colorObject.color] = !isActive;
                    }
                });

                const activeKeys = new Set(
                    Object.values(this.groupedPalette)
                        .filter(
                            (colorObject) =>
                                this.activeColors[colorObject.color]
                        )
                        .flatMap((colorObject) => colorObject.groupedKeys)
                );

                this.filteredData = this.processedData.filter((d) =>
                    activeKeys.has(`${d.value}`)
                );
            } else {
                this.filteredData = this.processedData.filter(
                    (d) => this.activeColors[this.colorPalette[d.value].color]
                );
            }

            this.updatePoints(
                d3.select("#" + this.pKey + "overlay").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
            this.updatePoints(
                d3.select("#" + this.pKey + "overlayLeft").select("g"),
                this.xScale,
                this.yScale,
                this.colorScale
            );
            this.createClusterLegend();
        },

        /**
         * Constructs a gradient legend for the plot.
         * Displays color gradients that correspond to data values.
         */
        createGradientLegend() {
            const legendContainer = d3.select(
                "#" + this.pKey + "legend-container-plot-viewer"
            );

            if (!legendContainer.select("svg").empty()) {
                legendContainer.select("svg").remove();
            }

            const legendWidth = 40;
            const legendHeight = 300;

            const legendSvg = legendContainer
                .append("svg")
                .attr("width", legendWidth + 50)
                .attr("height", legendHeight + 50);

            const gradient = legendSvg
                .append("defs")
                .append("linearGradient")
                .attr(
                    "id",
                    `${this.pKey}-legend-gradient-${this.title.replace(
                        /\s+/g,
                        ""
                    )}`
                )
                .attr("x1", "0%")
                .attr("x2", "0%")
                .attr("y1", "0%")
                .attr("y2", "100%");

            gradient
                .append("stop")
                .attr("offset", "0%")
                .attr("stop-color", this.colorPalette[2]);

            gradient
                .append("stop")
                .attr("offset", "50%")
                .attr("stop-color", this.colorPalette[1]);

            gradient
                .append("stop")
                .attr("offset", "100%")
                .attr("stop-color", this.colorPalette[0]);

            legendSvg
                .append("rect")
                .attr("x", 10)
                .attr("y", 12)
                .attr("width", legendWidth)
                .attr("height", legendHeight)
                .style(
                    "fill",
                    `url(#${this.pKey}-legend-gradient-${this.title.replace(
                        /\s+/g,
                        ""
                    )})`
                );

            legendSvg
                .append("text")
                .attr("x", 30)
                .attr("y", 5)
                .attr("text-anchor", "middle")
                .attr("dominant-baseline", "middle")
                .attr("font-size", "12px")
                .attr("font-family", "Arial, sans-serif")
                .text("log expr");

            const legendScale = d3
                .scaleLinear()
                .domain([this.legendMin, this.legendMax])
                .range([legendHeight, 0]);

            const legendAxis = d3.axisRight(legendScale).ticks(5);

            legendSvg
                .append("g")
                .attr("transform", `translate(${legendWidth + 10}, 12)`)
                .call(legendAxis);
        },

        /**
         * Builds a cluster legend for the plot.
         * Displays color-coded categories when working with grouped data.
         */
        createClusterLegend() {
            const legendContainer = d3.select(
                "#" + this.pKey + "legend-container-plot-viewer"
            );

            if (!legendContainer.select("svg").empty()) {
                legendContainer.select("svg").remove();
            }

            const legendWidth = 150;
            const legendHeight = 600;

            const legendItems = this.isGrouped
                ? Object.entries(this.groupedPalette)
                : Object.entries(this.colorPalette);

            const legendSvg = legendContainer
                .append("svg")
                .attr("width", legendWidth)
                .attr("height", legendHeight);

            legendSvg.selectAll("*").remove();

            legendItems.forEach(([key, colorObject], index) => {
                const yPosition = index * 30 + 20;
                const isActive = this.activeColors[colorObject.color];

                legendSvg
                    .append("circle")
                    .attr("cx", 20)
                    .attr("cy", yPosition)
                    .attr("r", 10)
                    .style("fill", isActive ? colorObject.color : "grey")
                    .style("cursor", "pointer")
                    .on("click", () =>
                        this.handleLegendClick(colorObject.color)
                    );

                legendSvg
                    .append("text")
                    .attr("x", 40)
                    .attr("y", yPosition)
                    .attr("dy", "0.35em")
                    .attr("font-size", "12px")
                    .attr("font-family", "Arial, sans-serif")
                    .style("fill", isActive ? "black" : "grey")
                    .text(`${colorObject.label}`)
                    .style("cursor", "pointer")
                    .on("click", () =>
                        this.handleLegendClick(colorObject.color)
                    );
            });
        },

        /**
         * Toggles the synchronization of movements and zoom between the overlays.
         * Enables or disables simultaneous control of both overlays in the right container.
         */
        toggleSync() {
            if (!this.isSynced) {
                this.isSynced = true;
                d3.select("#" + this.pKey + "overlay").on(".drag", null);
                d3.select("#" + this.pKey + "dragArea").on(".drag", null);
                this.syncAndMoveTogether();
                this.exportPositions();
            } else {
                this.isSynced = false;
                d3.select("#" + this.pKey + "overlay").call(this.dragOverlay());
                d3.select("#" + this.pKey + "dragArea").call(
                    this.dragOverlay()
                );
                d3.select("#" + this.pKey + "svgContainer").on(".drag", null);
            }
        },

        /**
         * Sets up drag functionality for the overlay.
         * Allows the overlay to be moved independently within the base image.
         *
         * @returns {Function} The D3 drag behavior function.
         */
        dragOverlay() {
            const overlay = d3.select("#" + this.pKey + "overlay");
            const dragArea = d3.select("#" + this.pKey + "dragArea");
            const overlayLeft = d3.select("#" + this.pKey + "overlayLeft");
            const dragAreaLeft = d3.select("#" + this.pKey + "dragAreaLeft");

            let initialX = 0,
                initialY = 0,
                currentTranslateX = 0,
                currentTranslateY = 0;

            return d3
                .drag()
                .on("start", (event) => {
                    const overlayTransform = overlay.attr("transform");
                    const overlayTranslate =
                        this.getTranslateFromElemet(overlayTransform);

                    currentTranslateX = overlayTranslate.translateX;
                    currentTranslateY = overlayTranslate.translateY;

                    initialX = event.x;
                    initialY = event.y;
                    overlay.raise().classed("active", true);
                    overlayLeft.raise().classed("active", true);
                })
                .on("drag", (event) => {
                    const dx = event.x - initialX;
                    const dy = event.y - initialY;

                    const scaledTransform = `translate(${
                        currentTranslateX + dx
                    },${currentTranslateY + dy}) scale(${this.pointScale})`;

                    overlay.attr("transform", scaledTransform);
                    dragArea.attr("transform", scaledTransform);

                    overlayLeft.attr("transform", scaledTransform);
                    dragAreaLeft.attr("transform", scaledTransform);
                })
                .on("end", () => {
                    overlay.classed("active", false);
                    overlayLeft.classed("active", false);
                });
        },

        /**
         * Synchronizes the movements of the overlay and the base image when dragging.
         */
        syncAndMoveTogether() {
            const baseAndOverlayGroup = d3.select(
                "#" + this.pKey + "baseAndOverlayGroup"
            );
            const overlayLeftGroup = d3.select(
                "#" + this.pKey + "overlayLeftGroup"
            );
            const dragSync = d3.drag().on("drag", (event) => {
                this.currentTransform = this.currentTransform.translate(
                    event.dx,
                    event.dy
                );
                baseAndOverlayGroup.attr(
                    "transform",
                    this.currentTransform.toString()
                );
                overlayLeftGroup.attr(
                    "transform",
                    this.currentTransform.toString()
                );

                this.updateMinimapViewport();
            });

            d3.select("#" + this.pKey + "svgContainer").call(dragSync);
        },

        /**
         * Zooms in on the plot if synchronization is enabled.
         * Applies a scaling factor to increase the zoom level.
         */
        zoomIn() {
            if (this.isSynced || this.base) {
                this.applyZoom(1.2, "#" + this.pKey + "baseAndOverlayGroup");
            } else {
                d3.select("#" + this.pKey + "svgContainerLeft")
                    .transition()
                    .call(this.leftZoom.scaleBy, 1.2);
            }
        },

        /**
         * Zooms out of the plot if synchronization is enabled.
         * Applies a scaling factor to decrease the zoom level.
         */
        zoomOut() {
            if (this.isSynced || this.base) {
                this.applyZoom(0.8);
            } else {
                d3.select("#" + this.pKey + "svgContainerLeft")
                    .transition()
                    .call(this.leftZoom.scaleBy, 0.8);
            }
        },

        /**
         * Resets the zoom level of the plot to its initial state.
         * Only performs this action if synchronization is enabled.
         */
        resetZoom() {
            if (this.isSynced || this.base) {
                this.currentTransform = d3.zoomIdentity;
                this.applyZoom(0.95);
                this.centerGraphics();
            } else {
                d3.select("#" + this.pKey + "svgContainerLeft")
                    .transition()
                    .call(this.leftZoom.transform, d3.zoomIdentity);
            }
        },

        /**
         * Applies a zoom transformation to the overlays.
         * Scales the overlays based on the provided zoom factor and centers the view.
         *
         * @param {number} factor - The zoom factor to apply.
         */
        applyZoom(factor) {
            const centerX = this.containerWidth / 2;
            const centerY = this.containerHeight / 2;

            const newTransform = this.currentTransform
                .translate(centerX, centerY)
                .scale(factor)
                .translate(-centerX, -centerY);

            this.currentTransform = newTransform;
            d3.select("#" + this.pKey + "baseAndOverlayGroup").attr(
                "transform",
                this.currentTransform.toString()
            );
            d3.select("#" + this.pKey + "overlayLeftGroup").attr(
                "transform",
                this.currentTransform.toString()
            );

            this.updateMinimapViewport();
        },

        /**
         * Updates the position and size of the viewport in the minimap.
         * Reflects the current zoom and translation settings in the minimap.
         */
        updateMinimapViewport() {
            const { x, y, k } = this.currentTransform;
            this.minimapViewportX =
                ((-x / this.baseImageWidth) * this.minimapWidth) / k;
            this.minimapViewportY =
                ((-y / this.baseImageHeight) * this.minimapHeight) / k;
            this.minimapViewportWidth =
                ((this.containerWidth / this.baseImageWidth) *
                    this.minimapWidth) /
                k;
            this.minimapViewportHeight =
                ((this.containerHeight / this.baseImageHeight) *
                    this.minimapHeight) /
                k;
        },

        /**
         * Increases the size of the overlay if it is not synchronized with the base image.
         * Adjusts the scale of the overlay accordingly.
         */
        increaseSize() {
            if (!this.isSynced) {
                this.pointScale *= 1.01;
                this.applyTransform();
            }
        },

        /**
         * Decreases the size of the overlay if it is not synchronized with the base image.
         * Adjusts the scale of the overlay accordingly.
         */
        decreaseSize() {
            if (!this.isSynced) {
                this.pointScale *= 0.99;
                this.applyTransform();
            }
        },

        /**
         * Increases the width of the points in the plot by adjusting the x scale.
         */
        increaseWidth() {
            if (!this.isSynced) {
                this.drawOffsetWidth += 5;
                this.drawPoints(`#${this.pKey}overlay`);
            }
        },

        /**
         * Decreases the width of the points in the plot by adjusting the x scale.
         */
        decreaseWidth() {
            if (!this.isSynced) {
                this.drawOffsetWidth = Math.max(this.drawOffsetWidth - 5, 0);
                this.drawPoints(`#${this.pKey}overlay`);
            }
        },

        /**
         * Extracts translation values from the provided transform string.
         * Parses and returns the translate X and Y values for further use.
         *
         * @param {string} transform - The transform string to extract translation from.
         * @returns {Object} An object containing the translateX and translateY values.
         */
        getTranslateFromElemet(transform) {
            let translateX = 0,
                translateY = 0;

            if (transform) {
                const translate = transform.match(/translate\(([^)]+)\)/);
                if (translate) {
                    const values = translate[1].split(",");
                    translateX = parseFloat(values[0]);
                    translateY = parseFloat(values[1]);
                }
            }
            return { translateX, translateY };
        },

        /**
         * Applies the current translation and scale transformations to the overlays.
         * Ensures consistency between the overlays and their associated drag areas.
         */
        applyTransform() {
            const g = d3.select("#" + this.pKey + "overlay");
            const gLeft = d3.select("#" + this.pKey + "overlayLeft");
            const dragArea = d3.select("#" + this.pKey + "dragArea");

            const transform = g.attr("transform");

            const transformObject = this.getTranslateFromElemet(transform);

            dragArea.attr(
                "transform",
                `translate(${transformObject.translateX}, ${transformObject.translateY})`
            );
            g.attr(
                "transform",
                `translate(${transformObject.translateX}, ${transformObject.translateY}) scale(${this.pointScale})`
            );
            gLeft.attr(
                "transform",
                `translate(${transformObject.translateX}, ${transformObject.translateY}) scale(${this.pointScale})`
            );
        },

        /**
         * Moves the overlay in the specified direction.
         * Adjusts the translation values to achieve the desired movement.
         *
         * @param {string} direction - The direction in which to move the overlay ('up', 'down', 'left', 'right').
         */
        moveOverlay(direction) {
            const step = 1;
            const overlay = d3.select("#" + this.pKey + "overlay");
            const overlayLeft = d3.select("#" + this.pKey + "overlayLeft");

            const currentTransform =
                overlay.attr("transform") || "translate(0,0) scale(1)";
            const transformValues =
                this.getTranslateFromElemet(currentTransform);

            let translateX = transformValues.translateX;
            let translateY = transformValues.translateY;

            switch (direction) {
                case "up":
                    translateY -= step;
                    break;
                case "down":
                    translateY += step;
                    break;
                case "left":
                    translateX -= step;
                    break;
                case "right":
                    translateX += step;
                    break;
            }

            const newTransform = `translate(${translateX}, ${translateY}) scale(${this.pointScale})`;
            overlay.attr("transform", newTransform);
            overlayLeft.attr("transform", newTransform);
        },

        /**
         * Toggles the visibility of the control panel.
         * Shows or hides controls based on the user's preference.
         */
        toggleControls() {
            this.showControls = !this.showControls;
        },

        /**
         * Export the current position and scale of the overlay and base image.
         * Returns an object containing the transformation details.
         */
        exportPositions() {
            const overlayTransform = this.getTranslateFromElemet(
                d3.select("#" + this.pKey + "overlay").attr("transform")
            );
            const baseTransform = this.getTranslateFromElemet(
                d3
                    .select("#" + this.pKey + "baseAndOverlayGroup")
                    .attr("transform")
            );

            console.log("exported Positions", {
                overlayPosition: overlayTransform,
                basePosition: baseTransform,
                pointScale: this.pointScale,
                currentTransform: this.currentTransform.toString(),
            });
            return {
                overlayPosition: overlayTransform,
                basePosition: baseTransform,
                pointScale: this.pointScale,
                currentTransform: this.currentTransform.toString(),
            };
        },

        /**
         * Import and apply the given positions and scales to the overlay and base image.
         * @param {Object} positions - An object containing the transformation details.
         */
        importPositions(positions) {
            if (positions) {
                // This positions is fixed to only work with Lung5_Rep2_fov_1_expr_quilt_data_MYC
                positions = {
                    overlayPosition: {
                        translateX: 155.06353759765625,
                        translateY: 100.39070129394531,
                    },
                    basePosition: {
                        translateX: -109.86120000000011,
                        translateY: -105.63839999999999,
                    },
                    pointScale: 0.7936142836436553,
                    currentTransform:
                        "translate(-109.86120000000011,-105.63839999999999) scale(1.0368000000000002)",
                };
                this.pointScale = positions.pointScale || 1;
                this.currentTransform = d3.zoomIdentity
                    .translate(
                        positions.basePosition.translateX,
                        positions.basePosition.translateY
                    )
                    .scale(this.pointScale);

                d3.select("#" + this.pKey + "baseAndOverlayGroup").attr(
                    "transform",
                    this.currentTransform.toString()
                );

                const overlayTransform = `translate(${positions.overlayPosition.translateX}, ${positions.overlayPosition.translateY}) scale(${this.pointScale})`;
                d3.select("#" + this.pKey + "overlay").attr(
                    "transform",
                    overlayTransform
                );
                d3.select("#" + this.pKey + "overlayLeft").attr(
                    "transform",
                    overlayTransform
                );
                this.centerGraphics();
                this.toggleSync();
            }
        },

        /**
         * Handles the export process for the graphics.
         * This function determines which graphic to export based on user selection
         * and then calls the appropriate export function (PNG, PDF, or SVG).
         */
        async exportGraphics() {
            this.showControls = false;

            await this.$nextTick();

            let svgToExport;

            if (this.selectedGraphic === "left") {
                svgToExport = document.getElementById(
                    this.pKey + "svgContainerLeft"
                );
            } else if (this.selectedGraphic === "right") {
                svgToExport = document.getElementById(
                    this.pKey + "svgContainer"
                );
            } else if (this.selectedGraphic === "both") {
                const leftSvg = document
                    .getElementById(this.pKey + "svgContainerLeft")
                    .cloneNode(true);
                const rightSvg = document
                    .getElementById(this.pKey + "svgContainer")
                    .cloneNode(true);

                const combinedSvg = document.createElementNS(
                    "http://www.w3.org/2000/svg",
                    "svg"
                );

                combinedSvg.setAttribute("width", this.containerWidth * 2);
                combinedSvg.setAttribute(
                    "height",
                    Math.max(this.baseImageHeight, this.containerHeight)
                );

                leftSvg.setAttribute("x", 50);
                leftSvg.setAttribute("y", 0);

                rightSvg.setAttribute("x", this.containerWidth - 100);
                rightSvg.setAttribute("y", 0);

                combinedSvg.appendChild(leftSvg);
                combinedSvg.appendChild(rightSvg);

                svgToExport = combinedSvg;
            }

            const svgClone = this.convertToSVG(svgToExport);

            if (this.selectedExportType === "png") {
                this.exportAsPNG(svgClone);
            } else if (this.selectedExportType === "pdf") {
                this.exportAsPDF(svgToExport);
            } else if (this.selectedExportType === "svg") {
                this.downloadSvg(svgClone);
            }

            this.showControls = true;
        },

        /**
         * Uses the converted SVG and converts it to a PNG file.
         *
         * @param {Element} svgElement - The SVG element to be downloaded as an PNG file.
         */
        exportAsPNG(svgElement) {
            const svgData = new XMLSerializer().serializeToString(svgElement);
            const svgBlob = new Blob([svgData], {
                type: "image/svg+xml;charset=utf-8",
            });
            const url = URL.createObjectURL(svgBlob);

            const image = new Image();
            image.src = url;

            const scaleFactor = 4;

            const canvas = document.createElement("canvas");

            canvas.width =
                this.selectedGraphic === "both"
                    ? Math.max(this.baseImageWidth, this.containerWidth) *
                      2 *
                      scaleFactor
                    : this.baseImageWidth * scaleFactor;
            canvas.height =
                this.selectedGraphic === "both"
                    ? Math.max(this.baseImageHeight, this.containerHeight) *
                      scaleFactor
                    : this.baseImageHeight * scaleFactor;

            const ctx = canvas.getContext("2d");

            image.onload = () => {
                ctx.scale(scaleFactor, scaleFactor);
                ctx.drawImage(image, 0, 0);
                URL.revokeObjectURL(url);

                canvas.toBlob(
                    (blob) => {
                        const pngUrl = URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = pngUrl;
                        a.download = `${this.title}-${
                            this.selectedGraphic
                        }-${Date.now()}.png`;
                        a.click();
                        URL.revokeObjectURL(pngUrl);
                    },
                    "image/png",
                    1.0
                );
            };
        },

        // TODO
        exportAsPDF(svgElement) {},

        /**
         * Converts the provided SVG element as an SVG file.
         * This function clones the SVG element, modifies it if necessary,
         * and then creates a downloadable SVG file for the user.
         *
         * @param {Element} svgElement - The SVG element to be exported as an SVG file.
         */
        convertToSVG(svgElement) {
            let svgClone;

            if (this.selectedGraphic !== "both") {
                svgClone = svgElement.cloneNode(true);
            } else {
                svgClone = svgElement;
            }

            if (this.base) {
                const imageBaseElement = svgClone.querySelector(
                    `#${this.pKey}imageBase`
                );

                if (imageBaseElement) {
                    const img = new Image();
                    img.src = this.base;
                    const canvas = document.createElement("canvas");
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0);
                    const base64String = canvas.toDataURL("image/png");
                    imageBaseElement.setAttribute("href", base64String);
                    imageBaseElement.style.height = "80%";
                }
            }

            if (
                this.selectedGraphic === "left" ||
                this.selectedGraphic === "both"
            ) {
                const legendContainer = document.querySelector(
                    `#${this.pKey}legend-container-plot-viewer svg`
                );

                svgClone.firstChild.setAttribute(
                    "transform",
                    "translate(-30,-30) scale(0.9)"
                );

                if (legendContainer) {
                    const legendClone = legendContainer.cloneNode(true);
                    svgClone.appendChild(legendClone);
                }

                const leftTitle = document.querySelector(
                    ".title-container-plot-viewer label"
                );

                if (leftTitle) {
                    const titleClone = document.createElementNS(
                        "http://www.w3.org/2000/svg",
                        "text"
                    );
                    titleClone.setAttribute("x", "100");
                    titleClone.setAttribute("y", "20");
                    titleClone.setAttribute("font-size", "16");
                    titleClone.setAttribute("font-family", "Arial, sans-serif");
                    titleClone.setAttribute("font-weight", "bold");
                    titleClone.setAttribute("dy", "1em");
                    titleClone.textContent = leftTitle.textContent;
                    svgClone.appendChild(titleClone);
                }
            }

            return svgClone;
        },

        /**
         * Uses the converted SVG to download the SVG file.
         *
         * @param {Element} svgElement - The SVG element to be downloaded as an SVG file.
         */
        downloadSvg(svgElement) {
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgElement);

            const blob = new Blob([svgString], {
                type: "image/svg+xml;charset=utf-8",
            });
            const url = URL.createObjectURL(blob);

            const a = document.createElement("a");
            a.href = url;
            a.download = `${this.title}-${
                this.selectedGraphic
            }-${Date.now()}.svg`;
            a.click();

            URL.revokeObjectURL(url);
        },
    },
};
</script>

<style scoped src="bootstrap/dist/css/bootstrap.css"></style>

<style scoped>
.main-container-plot-viewer {
    display: flex;
    width: 100%;
    height: 100%;
    margin: 0 auto;
    position: relative;
    border: 1px solid #ccc;
    overflow: hidden;
    min-height: 600px;
}

.main-container-plot-viewer.single-container {
    display: block;
    width: 50%;
    margin: 0 auto;
}

.container-plot-viewer {
    position: relative;
    flex: 1;
    border: 1px solid #ccc;
    overflow: hidden;
}

svg {
    width: 100%;
    height: 100%;
}

.image-base-plot-viewer {
    height: 80%;
}

.image-base-plot-viewer,
.overlay-plot-viewer {
    cursor: move;
}

.button-container-plot-viewer {
    position: absolute;
    right: 20px;
    top: 50px;
    display: flex;
    flex-direction: column;
}

.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.minimap-plot-viewer {
    position: absolute;
    top: 20px;
    left: 20px;
    border: 1px solid #ccc;
    background: rgba(255, 255, 255, 0.7);
    z-index: 10;
}

.toggle-controls-plot-viewer {
    z-index: 1;
    position: absolute;
    top: 5px;
    right: 10px;
    align-items: center;
    border-radius: 8px;
}

.sliders-panel-plot-viewer {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.7rem;
}

.controls-left-container-plot-viewer {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.7rem;
    z-index: 100;
}

.legend-container-plot-viewer {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 5;
}

.export-group-plot-viewer > .dropdown-menu {
    padding: 10px;
}

.title-container-plot-viewer {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    font-size: 16px;
    font-weight: bold;
    font-family: Arial, sans-serif;
    color: #333;
    text-align: center;
}
</style>
