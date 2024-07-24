<template>
    <div class="editor-container">
        <div ref="plotRef" class="plot-area">
            <div class="title-container">
                <svg ref="titleRef" class="title-svg"></svg>
            </div>
            <div class="plot-container">
                <svg ref="legendRef" class="legend-svg"></svg>
                <svg ref="plotSvgRef" class="plot-svg"></svg>
            </div>
        </div>

        <div class="buttons-panel" v-if="showControls && !isPanZoomLocked">
            <div class="btn-group-vertical">
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="zoomIn"
                    title="Zoom In"
                >
                    <i class="fas fa-search-plus"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="zoomOut"
                    title="Zoom Out"
                >
                    <i class="fas fa-search-minus"></i>
                </button>
                <button
                    class="btn btn-sm btn-secondary"
                    @click.prevent="resetZoom"
                    title="Reset Zoom"
                >
                    <i class="fas fa-redo"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="() => pan(0, -20)"
                    title="Pan Up"
                >
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="() => pan(0, 20)"
                    title="Pan Down"
                >
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="() => pan(-20, 0)"
                    title="Pan Left"
                >
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button
                    class="btn btn-sm btn-light"
                    @click.prevent="() => pan(20, 0)"
                    title="Pan Right"
                >
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button
                    class="btn btn-sm btn-primary"
                    @click.prevent="resetPlot"
                    title="Reset Settings"
                >
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button
                    class="btn btn-sm btn-success"
                    @click.prevent="exportToPNG"
                    title="Export to PNG"
                >
                    <i class="fas fa-file-image"></i>
                </button>
                <button
                    class="btn btn-sm btn-danger"
                    @click.prevent="exportToPDF"
                    title="Export to PDF"
                >
                    <i class="fas fa-file-pdf"></i>
                </button>
                <button
                    class="btn btn-sm btn-warning"
                    @click.prevent="exportToSVG"
                    title="Export to SVG"
                >
                    <i class="fas fa-file-code"></i>
                </button>
            </div>
        </div>

        <div class="controls" v-if="showControls">
            <div class="mb-1">
                <label for="pointSizeSlider">Point size</label>
                <input
                    type="range"
                    id="pointSizeSlider"
                    min="0.5"
                    max="5"
                    step="0.5"
                    v-model="pointSize"
                    @input="updatePointSize"
                    class="form-range"
                />
            </div>
            <div class="mb-1">
                <label for="expressionThresholdSlider"
                    >Expression Threshold: {{ expressionThreshold }}</label
                >
                <input
                    type="range"
                    id="expressionThresholdSlider"
                    min="0"
                    step="0.1"
                    :max="maxValue"
                    v-model="expressionThreshold"
                    @input="handleThresholdChange"
                    class="form-range"
                />
            </div>
        </div>

        <div
            class="toggle-export form-check form-switch"
            v-if="showControls && !isPanZoomLocked"
        >
            <input
                style="cursor: pointer"
                class="form-check-input"
                type="checkbox"
                id="toggleExport"
                @change="toggleExport"
                :checked="exportDefault"
            />
            <label class="form-check-label" for="toggleExport">
                {{ showControls ? "Export Default" : "Export Current" }}
            </label>
        </div>

        <div class="toggle-controls form-check form-switch">
            <input
                style="cursor: pointer"
                class="form-check-input"
                type="checkbox"
                id="toggleControls"
                @change="toggleControls"
                :checked="showControls"
            />
            <label class="form-check-label" for="toggleControls">
                {{ showControls ? "Hide Controls" : "Show Controls" }}
            </label>
        </div>
    </div>
</template>

<script>
import * as d3 from "d3";
import { inject } from "vue";
import { Tooltip } from "bootstrap";
import jsPDF from "jspdf";
import html2canvas from "html2canvas";

export default {
    props: {
        processedData: { type: Array, required: true },
        expression: { type: String, required: true },
        title: { type: String, required: true },
        colorPalette: { type: Object, required: true },
        legendMin: { type: Number, required: true },
        legendMax: { type: Number, required: true },
        isPanZoomLocked: { type: Boolean, required: true },
        isYAxisInverted: { type: Boolean, required: true },
    },

    setup() {
        const state = inject("sharedState");
        return { sharedState: state };
    },

    data() {
        return {
            data: [],
            filteredData: [],
            pointSize: 1.5,
            expressionThreshold: 0,
            zoomTransform: d3.zoomIdentity,
            exportDefault: true,
            maxValue: null,
            plotWidth: this.sharedState.plotWidth,
            plotHeight: this.sharedState.plotHeight,
            showControls: true,
            increaseRatio: 8,
        };
    },

    watch: {
        pointSize() {
            this.updatePoints();
        },
        expressionThreshold() {
            this.filterData();
            this.createPlot();
        },
    },

    mounted() {
        this.readData();
        this.createPlot();
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll("[title]")
        );
        tooltipTriggerList.map(
            (tooltipTriggerEl) =>
                new Tooltip(tooltipTriggerEl, { trigger: "hover" })
        );
    },

    methods: {
        readData() {
            this.data = this.processedData.map((d) => ({
                x: +d.xpos,
                y: +d.ypos,
                value: +d[this.expression],
            }));

            this.filteredData = this.data;
            this.maxValue = d3.max(this.data, (d) => d.value);
        },

        createPlot() {
            if (this.data.length === 0) return;

            d3.select(this.$refs.plotSvgRef).selectAll("*").remove();

            const width = this.sharedState.plotWidth;
            const height = this.sharedState.plotHeight;

            const padding = 0;

            const svg = d3
                .select(this.$refs.plotSvgRef)
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", `100 100 ${width} ${height}`)
                .attr("preserveAspectRatio", "xMidYMid meet");

            this.xScale = d3
                .scaleLinear()
                .domain([0, d3.max(this.data, (d) => d.x)])
                .range([padding, width - padding]);

            this.yScale = d3
                .scaleLinear()
                .domain(
                    this.isYAxisInverted
                        ? [0, d3.max(this.data, (d) => d.y)]
                        : [d3.max(this.data, (d) => d.y), 0]
                )
                .range([height - padding, padding]);

            this.colorScale = d3
                .scaleLinear()
                .domain([
                    0,
                    d3.max(this.data, (d) => d.value) / 2,
                    d3.max(this.data, (d) => d.value),
                ])
                .range(this.colorPalette);

            const g = svg.append("g");

            this.updatePoints(g);

            const zoom = d3
                .zoom()
                .scaleExtent([0.5, 20])
                .on("zoom", (event) => {
                    g.attr("transform", event.transform);
                    this.zoomTransform = event.transform;
                });

            svg.call(zoom);

            if (this.zoomTransform) {
                svg.call(zoom.transform, this.zoomTransform);
            }

            this.createTitle();
            this.createLegend();

            this.emitSvgData();
        },

        applyTransformElementIntoZoomAndPan(transform) {
            // 1. We need to get the SVG element
            const svg = d3.select(this.$refs.plotSvgRef || this.$refs.elemTwo);
            const svgElement = svg.node();
            const width = svgElement.clientWidth;
            const height = svgElement.clientHeight;

            // 2. We need to calculate the center of the SVG
            const centerX = width / 2;
            const centerY = height / 2;

            // 3. Convert the transform object to a D3 transform centered
            const d3Transform = d3.zoomIdentity
                .translate(centerX, centerY)
                .scale(transform.scale)
                .translate(-centerX + transform.x, -centerY + transform.y);

            const g = svg.select("g");

            const zoom = d3
                .zoom()
                .scaleExtent([0.5, 20])
                .on("zoom", (event) => {
                    g.attr("transform", event.transform);
                    this.zoomTransform = event.transform;
                });

            svg.call(zoom);
            svg.call(zoom.transform, d3Transform);
            this.zoomTransform = d3Transform;
        },

        emitSvgData() {
            const svgElement = this.$refs.plotSvgRef;
            const originalTransform = this.zoomTransform;

            this.resetZoom();

            const originalWidth = svgElement.getAttribute("width");
            const originalHeight = svgElement.getAttribute("height");

            const bbox = svgElement.getBBox();
            svgElement.setAttribute(
                "viewBox",
                `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`
            );
            svgElement.setAttribute("width", bbox.width);
            svgElement.setAttribute("height", bbox.height);

            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgElement);

            this.$emit("update-svg", svgString);

            svgElement.setAttribute("width", originalWidth);
            svgElement.setAttribute("height", originalHeight);
            this.applyZoomTransform(originalTransform);
        },

        updatePoints(g = d3.select(this.$refs.plotSvgRef).select("g")) {
            g.selectAll("circle")
                .data(this.filteredData)
                .join(
                    (enter) =>
                        enter
                            .append("circle")
                            .attr("cx", (d) => this.xScale(d.x))
                            .attr("cy", (d) => this.yScale(d.y))
                            .attr("r", this.pointSize)
                            .style("fill", (d) => this.colorScale(d.value)),
                    (update) =>
                        update
                            .attr("r", this.pointSize)
                            .style("fill", (d) => this.colorScale(d.value)),
                    (exit) => exit.remove()
                );

            this.emitSvgData();
        },

        resetZoom() {
            this.applyZoomTransform(d3.zoomIdentity);
        },

        createTitle() {
            const titleSvg = d3.select(this.$refs.titleRef).attr("height", 100);

            titleSvg.selectAll("*").remove();

            titleSvg
                .append("text")
                .attr("x", 200)
                .attr("y", 20)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .text(this.expression);

            titleSvg
                .append("text")
                .attr("x", 320)
                .attr("y", 20)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .text(`sample: ${this.title}`);
        },

        createLegend() {
            const legendWidth = 40;
            const legendHeight = 300;

            const legendSvg = d3
                .select(this.$refs.legendRef)
                .attr("width", legendWidth + 50)
                .attr("height", legendHeight + 50);

            legendSvg.selectAll("*").remove();

            const gradient = legendSvg
                .append("defs")
                .append("linearGradient")
                .attr("id", "legend-gradient")
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
                .style("fill", "url(#legend-gradient)");

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

        zoomIn() {
            const zoomFactor = 1.2;
            const svg = d3.select(this.$refs.plotSvgRef);
            const width = svg.attr("width");
            const height = svg.attr("height");
            const transform = this.zoomTransform
                .translate(
                    (width / 2) * (1 - zoomFactor),
                    (height / 2) * (1 - zoomFactor)
                )
                .scale(zoomFactor);

            this.applyZoomTransform(transform);
        },

        zoomOut() {
            const zoomFactor = 0.8;
            const svg = d3.select(this.$refs.plotSvgRef);
            const width = svg.attr("width");
            const height = svg.attr("height");
            const transform = this.zoomTransform
                .translate(
                    (width / 2) * (1 - zoomFactor),
                    (height / 2) * (1 - zoomFactor)
                )
                .scale(zoomFactor);

            this.applyZoomTransform(transform);
        },

        resetZoom() {
            this.applyZoomTransform(d3.zoomIdentity);
        },

        pan(dx, dy) {
            this.applyZoomTransform(this.zoomTransform.translate(dx, dy));
        },

        applyZoomTransform(transform) {
            const svg = d3.select(this.$refs.plotSvgRef);
            const g = svg.select("g");

            const zoom = d3
                .zoom()
                .scaleExtent([0.5, 20])
                .on("zoom", (event) => {
                    g.attr("transform", event.transform);
                    this.zoomTransform = event.transform;
                });

            svg.call(zoom);
            svg.call(zoom.transform, transform);
            this.zoomTransform = transform;
        },

        handleThresholdChange(e) {
            this.expressionThreshold = e.target.value;
        },

        filterData() {
            this.filteredData = this.data.filter(
                (d) => d.value >= this.expressionThreshold
            );
        },

        recreatePlot() {
            this.clearPlot();
            this.createPlot();
        },

        clearPlot() {
            d3.select(this.$refs.plotSvgRef).selectAll("*").remove();
        },

        toggleControls() {
            this.showControls = !this.showControls;
        },

        toggleExport() {
            this.exportDefault = !this.exportDefault;
        },

        exportToPNG() {
            html2canvas(this.$refs.plotRef).then((canvas) => {
                const link = document.createElement("a");
                link.download = "quilt-plot.png";
                link.href = canvas.toDataURL();
                link.click();
            });
        },

        exportToPDF() {
            const combinedSVG = this.getSVG();
            const doc = new jsPDF({
                orientation: "landscape",
                unit: "pt",
                format: [this.plotWidth, this.plotHeight],
            });

            const canvas = document.createElement("canvas");
            canvas.width = this.plotWidth;
            canvas.height = this.plotHeight;
            const ctx = canvas.getContext("2d");

            const DOMURL = window.URL || window.webkitURL || window;
            const img = new Image();
            const svgBlob = new Blob([combinedSVG], {
                type: "image/svg+xml;charset=utf-8",
            });
            const url = DOMURL.createObjectURL(svgBlob);

            img.onload = () => {
                ctx.drawImage(img, 0, 0);
                DOMURL.revokeObjectURL(url);

                const imgData = canvas.toDataURL("image/png");
                doc.addImage(
                    imgData,
                    "PNG",
                    0,
                    0,
                    this.plotWidth,
                    this.plotHeight
                );
                doc.save("quilt-plot.pdf");
            };

            img.src = url;
        },

        getSVG() {
            const titleSvg = this.$refs.plotRef.querySelector("svg");
            const plotSvg = this.$refs.plotSvgRef;
            const legendSvg = this.$refs.legendRef;
            const svg1Content = new XMLSerializer().serializeToString(titleSvg);
            const plotContent = new XMLSerializer().serializeToString(plotSvg);
            const legendContent = new XMLSerializer().serializeToString(
                legendSvg
            );
            const combinedWidth = Math.max(
                titleSvg.width.baseVal.value,
                plotSvg.width.baseVal.value,
                legendSvg.width.baseVal.value
            );
            const combinedHeight =
                titleSvg.height.baseVal.value +
                plotSvg.height.baseVal.value +
                legendSvg.height.baseVal.value;

            const combinedSVG = `
            <svg width="${combinedWidth}" height="${combinedHeight}" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <g id="combined">
                <g transform="translate(0, 0)">
                ${svg1Content}
                </g>
                <g transform="translate(150, ${
                    titleSvg.height.baseVal.value - 100
                })">
                ${plotContent}
                </g>
                <g transform="translate(0, ${titleSvg.height.baseVal.value})">
                ${legendContent}
                </g>
            </g>
            </svg>
        `;
            return combinedSVG;
        },

        exportToSVG() {
            const combinedSVG = this.getSVG();
            const blob = new Blob([combinedSVG], {
                type: "image/svg+xml;charset=utf-8",
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = "combined.svg";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        resetPlot() {
            this.pointSize = 1.5;
            this.expressionThreshold = 0;
            this.zoomTransform = d3.zoomIdentity;
            this.filteredData = this.data;
            this.recreatePlot();
        },

        overlayWidthUpdate(isOverlayIncreasing) {
            if (isOverlayIncreasing) {
                this.plotWidth += this.increaseRatio;
            } else {
                this.plotWidth = Math.max(
                    100,
                    this.plotWidth - this.increaseRatio
                );
            }
            this.sharedState.plotWidth = this.plotWidth;
            this.recreatePlot();
        },
    },
};
</script>

<style scoped>
.editor-container {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    width: 100%;
    height: 100%;
}

.plot-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    height: 100%;
}

.title-container {
    width: 100%;
    display: flex;
    justify-content: center;
}

.title-svg {
    width: 100%;
    height: 40px;
}

.plot-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: calc(100% - 50px);
}

.plot-svg {
    width: 80%;
    height: 90%;
    cursor: move;
}

.legend-svg {
    width: 20%;
    height: 100%;
}

.buttons-panel {
    display: flex;
    flex-direction: column;
    gap: 5px;
    position: absolute;
    right: 20px;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.controls {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.8rem;
}

.toggle-controls {
    position: absolute;
    bottom: 10px;
    right: 10px;
    align-items: center;
    border-radius: 8px;
}

.toggle-export {
    position: absolute;
    top: 5px;
    right: 10px;
    align-items: center;
    border-radius: 8px;
}
</style>
