<template>

    <div class="editor-container">

        <div ref="plotRef" class="plot-area" >

            <div class="title-container">
                <svg ref="titleRef" class="title-svg"></svg>
            </div>

            <div class="plot-container">
                <Spinner :loading="loading" />
                <svg ref="legendRef" class="legend-svg"></svg>
                <svg ref="plotSvgRef" class="plot-svg"></svg>
                <div ref="tooltip" class="tooltip" style="opacity: 0;" ></div>
            </div>
            <!-- <canvas ref="canvas" :width="plotWidth" :height="plotHeight"></canvas> -->
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
                    v-if="!isHeavyPlotType"
                >
                    <i class="fas fa-file-pdf"></i>
                </button>
                <button
                    class="btn btn-sm btn-warning"
                    @click.prevent="exportToSVG"
                    title="Export to SVG"
                    v-if="!isHeavyPlotType"
                >
                    <i class="fas fa-file-code"></i>
                </button>
            </div>
        </div>

        <div class="clusterLegendToggle form-check form-switch" >
                <input
                    style="cursor: pointer"
                    class="form-check-input"
                    type="checkbox"
                    id="toggleControls"
                    @change="toggleClusters"
                    :checked="showAllClusters"
                />
                <label class="form-check-label" for="toggleControls">
                    {{ showAllClusters ? "Remove all clusters" : "Show all clusters" }}
                </label>
            </div>

        <div class="controls" v-if="showControls">
            <div class="mb-1">
                <label for="pointSizeSlider">Point size</label>
                <input
                    type="range"
                    id="pointSizeSlider"
                    min="0.5"
                    :max="maxPointSize"
                    step="0.5"
                    v-model="pointSize"
                    @input="updatePointSize"
                    class="form-range"
                />
            </div>
            <div class="mb-1" v-if="plotType === 'gradient'">
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
            v-if="showControls && !isPanZoomLocked && !isHeavyPlotType"
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
import { h, inject } from "vue";
import { Tooltip } from "bootstrap";
import jsPDF from "jspdf";
import html2canvas from "html2canvas";
import { throttle, debounce } from "lodash";
import Spinner from './Spinner.vue';
import regl from 'regl';
import { Canvg } from 'canvg';
import { UMAP2 } from 'umap-js'
import { initTransform } from "umap-js/dist/umap";

const PlotTypes = Object.freeze({
    GRADIENT: "gradient",
    CLUSTER: "cluster",
    FLIGHT: "flight",
    UMAP: "umap"
});

export default {
    props: {
        processedData: { type: Array, required: true },
        expression: { type: String, required: true },
        title: { type: String, required: true },
        plotType: { type: String, required: true },
        colorPalette: { type: Object, required: true },
        legendMin: { type: Number, required: true },
        legendMax: { type: Number, required: true },
        isPanZoomLocked: { type: Boolean, required: true },
        isYAxisInverted: { type: Boolean, required: true },
        labelData: {type: Array, required: false}
    },
    components:{
        Spinner
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
            activeColors: {},
            loading: null,
            maxPointSize: null,
            isHeavyPlotType: null,
            zoomTimeout: null,
            zoomDelay: 10,
            reglInstance: null,
            pointsBuffer: null,
            drawPoints: null,
            isClusterHighlighted: false,
            svgElement: null,
            showPlot:false,
            xMax: null,
            xMin: null,
            yMin: null,
            yMax: null,
            showAllClusters: true
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
        if (this.plotType === PlotTypes.CLUSTER || this.plotType === PlotTypes.FLIGHT || this.plotType === PlotTypes.UMAP) this.initializeActiveColors();
        if(this.plotType === PlotTypes.FLIGHT || this.plotType === PlotTypes.UMAP){
            this.maxPointSize = 3
            this.showAllClusters = true
            this.loading = true
            this.isHeavyPlotType = true
            this.exportDefault = false
        } else {
            this.maxPointSize = 5
            this.loading = false
            this.isHeavyPlotType = false
        }
        this.readData();
        this.createPlot();
        this.showPlot = true

        console.log(this.plotWidth);
        console.log(this.plotHeight);

    },

    methods: {
        readData() {

            this.data = this.processedData.map((d) => ({
                x: this.plotType === PlotTypes.UMAP ? d['UMAP1'] : +d.x,
                y: this.plotType === PlotTypes.UMAP ? d['UMAP2'] : +d.y,
                value:
                    this.plotType === PlotTypes.GRADIENT
                        ? +d[this.expression]
                        : this.plotType === PlotTypes.FLIGHT ? d["insitutype_cell_types"] : this.plotType === PlotTypes.UMAP ? d["insitutype_cell_types"] : +d["stclust_spw0.02_k5"],
            }));
            this.filteredData = [...this.data];
            if(!this.isHeavyPlotType || this.plotType !== PlotTypes.CLUSTER){
                // Only find max expression threshold if quilt plot type to avoid iterating over the array
                this.maxValue = d3.max(this.data, (d) => d.value);
            }
        },
        initializeActiveColors() {
            for (const key in this.colorPalette) {
                this.activeColors[this.colorPalette[key]] = true;
            }
        },
        generateQuilt(isDefault){
            const width = String(parseInt(this.plotWidth) + 100);
            const height = this.plotHeight;
            const padding = 25;
            const initialX = 0;
            const initialY = 0;
            const margin = { top: 20, right: 20, bottom: 20, left: 20 };
            const innerWidth = width - margin.left - margin.right;
            const innerHeight = height - margin.top - margin.bottom;


            const svg = isDefault ? d3.create('svg')
            .attr('width', width)
            .attr('height', height)
            .attr('xmlns', 'http://www.w3.org/2000/svg')
            .attr('xmlns:xlink', 'http://www.w3.org/1999/xlink')
            .attr("preserveAspectRatio", "xMidYMid meet")
            .attr("viewBox", `100 100 ${width} ${String(parseInt(height) + 50)}`)
            :
            d3.select(this.$refs.plotSvgRef)
            .attr("width", width)
            .attr("height", height)
            .attr("viewBox", `100 100 ${width} ${height}`)
            .attr("preserveAspectRatio", "xMidYMid meet");

            this.xMin = d3.min(this.data, d => d.x)
            this.xMax = d3.max(this.data, d => d.x)
            this.yMin = d3.min(this.data, d => +d.y)
            this.yMax = d3.max(this.data, d => +d.y)

            const xExtent = d3.extent(this.data, d => +d.x)
            const yExtent = d3.extent(this.data, d => +d.y)

            this.xScale = d3
                .scaleLinear()
                .domain(xExtent)
                // .domain(xExtent)
                .range([margin.left, innerWidth - margin.right]);

            this.yScale = d3
                .scaleLinear()
                .domain(
                    this.isYAxisInverted
                        ? [0, d3.max(this.data, (d) => d.y)]
                        : yExtent
                )
                .range([innerHeight - margin.bottom, margin.top]);


            this.colorScale = d3
                .scaleLinear()
                .domain([
                    0,
                    d3.max(this.data, (d) => d.value) / 2,
                    d3.max(this.data, (d) => d.value),
                ])
                .range(this.colorPalette);


            const g = svg.append("g")

            if(isDefault){
                // Add dots
                if(this.isHeavyPlotType){
                    svg.call(this.zoom.transform, d3.zoomIdentity.translate(this.plotWidth / 2, this.plotHeight / 2));
                }
                g.selectAll("dot")
                .data(isDefault ? this.data : this.filteredData)
                .enter()
                .append("circle")
                    .attr("cx", d => this.xScale(d.x))
                    .attr("cy", d=> this.yScale(d.y) )
                    .attr("r", isDefault ? 1.5 : this.pointSize)
                    .style("fill", d => this.isHeavyPlotType ? this.colorPalette[d.value] : this.colorScale(d.value));


            }
            return [svg, g]
        },
        createCanvas(){
            const canvas = this.$refs.canvas
            const ctx = canvas.getContext('2d')
            const umap = UMAP2({
                n_neighbors:10,
                min_dist:0.1,
                spread:1.0
            })
            const embedding = umap.fit(this.data)
            const quadtree = d3.quadtree().extent([[-1,-1], [1,1]]).addAll(embedding)
            function render() {
                ctx.clearRect(0,0, canvas.width, canvas.height)
                quadtree.visit(function(node,x0,y0,x1,y1){
                    const points = node.points
                    for(let i =0; i< points.length; i++){
                        const point = points[i]
                        ctx.beginPath();
                        ctx.arc(point.x, point.y, 2,0,2 * Math.PI)
                        ctx.fillStyle = this.colorPalette[point.value]
                        ctx.fill()
                    }
                })
                d3.timer(render)
            }
        },
        createPlot() {
            if (this.data.length === 0) return;

            d3.select(this.$refs.plotSvgRef).selectAll("*").remove();

            const width = this.sharedState.plotWidth;
            const height = this.sharedState.plotHeight;

            const margin = { top: 20, right: 20, bottom: 20, left: 20 };
            const innerWidth = width - margin.left - margin.right;
            const innerHeight = height - margin.top - margin.bottom;
            const [svg, g] = this.generateQuilt(false)
            this.svgElement = svg.node()

            this.zoom = d3.zoom()
                .scaleExtent(this.isHeavyPlotType ? [0.2, 10] :[1, 10])
                .on('zoom', (event) => {

                clearTimeout(this.zoomTimeout);
                this.zoomTimeout = setTimeout(() => {
                    g.attr("transform", event.transform);
                    this.zoomTransform = event.transform;
                }, this.zoomDelay);
                })
                .on('start', () => {
                    svg.style('cursor', 'move');
                })
                .on('end', () => {
                    svg.style('cursor', this.isHeavyPlotType ?  'crosshair' : 'move');
                });

            svg.call(this.zoom);
            if(this.isHeavyPlotType){
                let yOffset = this.plotType === PlotTypes.FLIGHT ? 100 : 200
                let scale = this.plotType === PlotTypes.FLIGHT ? 0.6 : 0.7
                const initialTransform = d3.zoomIdentity.translate(this.plotWidth / 2, (this.plotHeight / 2) - yOffset).scale(scale)
                svg.call(this.zoom.transform, initialTransform)
                this.zoomTransform = initialTransform
            }
            if (this.zoomTransform && !this.isHeavyPlotType) {
                svg.call(zoom.transform, this.zoomTransform);
            }

            this.updatePoints(g);
            this.createTitle();

            if (this.plotType === PlotTypes.GRADIENT) {
                this.createGradientLegend();
            } else if (this.plotType === PlotTypes.CLUSTER || this.isHeavyPlotType) {
                this.createClusterLegend();
                if(this.plotType === PlotTypes.FLIGHT){
                    this.addLabels(g)
                }
            }

            if(!(this.isHeavyPlotType)){
                this.emitSvgData();
            } else{
                svg.style('cursor', 'crosshair');
                this.addTooltip(svg)
                this.loading = false
            }


        },
        addTooltip(svg){
            const tooltip = d3.select(this.$refs.tooltip);
            svg.selectAll('circle')
            .data(this.filteredData)
            .on('mouseover', (event, d) => {
                svg.style('cursor', 'crosshair');
                tooltip.transition()
                    .duration(200)
                    .style('opacity', .9);
                tooltip.html(`Cell Type: ${d.value}`)
                    .style('left', `${event.pageX + 5}px`)

            })
            .on('mouseout', () => {
                tooltip.transition()
                    .duration(500)
                    .style('opacity', 0);
            });
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

            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgElement);

            this.$emit("update-svg", svgString);

            svgElement.setAttribute("width", originalWidth);
            svgElement.setAttribute("height", originalHeight);
            this.applyZoomTransform(originalTransform);
        },
        updatePoints(g = d3.select(this.$refs.plotSvgRef).select("g")) {
            this.loading = this.isHeavyPlotType ? true : false
            g.selectAll("circle")
                .data(this.isHeavyPlotType ? this.data : this.filteredData)
                .join(
                    (enter) =>
                        enter
                            .append("circle")
                            .attr("cx", (d) => this.xScale(d.x))
                            .attr("cy", (d) => this.yScale(d.y))
                            .attr("r", this.pointSize)
                            .style("fill", (d) =>
                                this.plotType === PlotTypes.GRADIENT
                                    ? this.colorScale(d.value)
                                    : this.colorPalette[d.value]
                            )
                            .attr('visibility', d => this.activeColors[this.colorPalette[d.value]] ? 'visible' : 'hidden'),
                    (update) =>
                        update
                            .attr("r", this.pointSize)
                            .style("fill", (d) =>
                                this.plotType === PlotTypes.GRADIENT
                                    ? this.colorScale(d.value)
                                    : this.colorPalette[d.value]
                            )
                            .attr('visibility', d => this.activeColors[this.colorPalette[d.value]] ? 'visible' : 'hidden'),
                    (exit) => exit.remove()
                );

            if(!(this.isHeavyPlotType)){
                this.emitSvgData();
            }
            setTimeout(() =>{
                this.loading = false
            }, 100)

        },
        createTitle() {
            const titleSvg = d3.select(this.$refs.titleRef).attr("height", 100);

            titleSvg.selectAll("*").remove();
            let sampleTitle;
            if(!(this.isHeavyPlotType)){
                sampleTitle = "sample: " + this.title
                titleSvg
                    .append("text")
                    .attr("x", 200)
                    .attr("y", 20)
                    .attr("text-anchor", "middle")
                    .style("font-size", "16px")
                    .text(this.expression);
            } else {
                sampleTitle = this.title
            }

            titleSvg
                .append("text")
                .attr("x", 320)
                .attr("y", 20)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .text(sampleTitle);
        },
        createClusterLegend() {
            const legendWidth = this.isHeavyPlotType ? 220:150;
            const legendHeight = this.isHeavyPlotType ? 600:200;

            const legendItems = Object.entries(this.colorPalette);

            const legendSvg = d3
                .select(this.$refs.legendRef)
                .attr("width", legendWidth)
                .attr("height", legendHeight);

            legendSvg.selectAll("*").remove();

            legendItems.forEach(([key, color], index) => {
                const yPosition = index * 30 + 20;
                const isActive = this.activeColors[color];


                legendSvg
                    .append("circle")
                    .attr("cx", 20)
                    .attr("cy", yPosition)
                    .attr("r", 10)
                    .style("fill", isActive ? color : "grey")
                    .style("cursor", "pointer")
                    .on('mouseover', (event, d) => {
                        this.handleLegendHover(key)
                    })
                    .on('mouseout', () => {
                        this.handleLegendHoverOut(key)
                    })
                    .on("click", () => this.handleLegendClick(color));

                legendSvg
                    .append("text")
                    .attr("x", 40)
                    .attr("y", yPosition)
                    .attr("dy", "0.35em")
                    .attr("font-size", "12px")
                    .attr("font-family", "Arial, sans-serif")
                    .style("fill", isActive ? "black" : "grey")
                    .text(`${key}`)
                    .style("cursor", "pointer")
                    .on('mouseover', (event, d) => {
                        this.handleLegendHover(key)
                    })
                    .on('mouseout', () => {
                        this.handleLegendHoverOut(key)
                    })
                    .on("click", () => this.handleLegendClick(color));
            });
        },
        getCircleGroup(){
            return d3.select(this.$refs.plotSvgRef).select('g')
        },
        handleLegendHover(cell){
            if (!this.isClusterHighlighted){
                const g = this.getCircleGroup()
                g.selectAll('circle').attr("r", d => d.value === cell ? this.pointSize * 1.7 : this.pointSize)
                this.isClusterHighlighted = true
            }
        },
        handleLegendHoverOut(cell){
            const g = this.getCircleGroup()
            g.selectAll('circle').attr("r", d => this.pointSize)
            this.isClusterHighlighted = false
        },
        handleLegendClick(color) {
            this.loading = true
            this.activeColors[color] = !this.activeColors[color];
            this.filteredData = this.data.filter(
                (d) => this.activeColors[this.colorPalette[d.value]]
            );

            const isVisible = (cluster) => {
                return this.activeColors[this.colorPalette[cluster]] ? 'visible' : 'hidden'
            }
            const g = this.getCircleGroup()
            g.selectAll('circle').attr('visibility', d => this.activeColors[this.colorPalette[d.value]] ? 'visible' : 'hidden')
            const clusters = Object.keys(this.colorPalette)

            g.selectAll('text').attr('visibility', function(){
                const clusterName = d3.select(this).text().split(' ')[0].trim()
                return isVisible(clusterName)
            })

            // Adding a delay for better UX (disable interface inputs) for when re rendering the SVG plot
            setTimeout(() => {
                // this.createPlot();
                this.createClusterLegend()
                this.loading = false
            }, 200)



        },
        createGradientLegend() {
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
        addLabels(g){
            this.labelData.forEach(label => {
                g.append('text')
                .attr('x', this.xScale(label.x))
                .attr('y', this.yScale(label.y))
                .attr('dy', '-0.5em')
                .attr('text-anchor', 'middle')
                .attr('fill', 'black')
                .attr('visibility', 'visible')
                .style('font-size', '20px')
                .style('font-weight', 'bold')
                .text(label.insitutype_cell_types);
            })
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
            console.log(transform)
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
            console.log('reset zoom', d3.zoomIdentity)
            this.applyZoomTransform(d3.zoomIdentity);
        },

        pan(dx, dy) {
            this.applyZoomTransform(this.zoomTransform.translate(dx, dy));
            console.log(dx, dy)
            console.log('transform',this.zoomTransform)
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
        toggleClusters(){
            this.loading = true
            this.showAllClusters = !this.showAllClusters;
            if(this.showAllClusters){
                for(const color in this.activeColors){
                    this.activeColors[color] = true
                }
            } else {
                for(const color in this.activeColors){
                    this.activeColors[color] = false
                }
            }
            this.filteredData = this.data.filter(
                (d) => this.activeColors[this.colorPalette[d.value]]
            );
            const isVisible = (cluster) => {
                return this.activeColors[this.colorPalette[cluster]] ? 'visible' : 'hidden'
            }
            const g = this.getCircleGroup()
            g.selectAll('circle').attr('visibility', d => this.activeColors[this.colorPalette[d.value]] ? 'visible' : 'hidden')
            g.selectAll('text').attr('visibility', function(){
                const clusterName = d3.select(this).text().split(' ')[0].trim()
                return isVisible(clusterName)
            })

            // Adding a delay for better UX (disable interface inputs) for when re rendering the SVG plot
            setTimeout(() => {
                // this.createPlot();
                this.createClusterLegend()
                this.loading = false
            }, 200)



        },
        toggleExport() {
            this.exportDefault = !this.exportDefault;
        },

        exportToPNG() {
            if(this.exportDefault){
                const [svg, g] = this.generateQuilt(true)

                const combinedSVG = this.getSVG(svg.node())

                const svgBlob = new Blob([combinedSVG], { type: 'image/svg+xml;charset=utf-8' });
                const url = URL.createObjectURL(svgBlob);
                const img = new Image();

                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1200;
                    canvas.height = this.plotHeight;
                    const context = canvas.getContext('2d');
                    // Set the background color to white (or any other color)
                    context.fillStyle = 'white';
                    context.fillRect(0, 0, canvas.width, canvas.height);

                    context.drawImage(img, 0, 0);
                    URL.revokeObjectURL(url);

                    canvas.toBlob((blob) => {
                        console.log(blob)
                        const link = document.createElement('a');
                        link.download = 'quilt_plot.png';
                        link.href = URL.createObjectURL(blob);
                        link.click();
                    }, 'image/png');
                };
                img.src = url;
            } else{
                if(this.isHeavyPlotType){
                    // Create a canvas element
                    const canvas = document.createElement('canvas');
                    canvas.width = 800;  // Set canvas width
                    canvas.height = 800; // Set canvas height

                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = 'white';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    // Convert the SVG to a canvas using canvg
                    const combinedSVG = this.getSVG(this.$refs.plotSvgRef)
                    // const svgString = new XMLSerializer().serializeToString(this.svgElement);
                    const svgBlob = new Blob([combinedSVG], { type: 'image/svg+xml;charset=utf-8' });
                    const url = URL.createObjectURL(svgBlob);
                    const img = new Image();

                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        canvas.width = 1200;
                        canvas.height = this.plotType === PlotTypes.UMAP ? String(parseInt(this.plotHeight) + 100) : this.plotHeight;
                        const context = canvas.getContext('2d');
                        // Set the background color to white (or any other color)
                        context.fillStyle = 'white';
                        context.fillRect(0, 0, canvas.width, canvas.height);

                        context.drawImage(img, 0, 0);
                        URL.revokeObjectURL(url);

                        canvas.toBlob((blob) => {
                            const link = document.createElement('a');
                            link.download = this.plotType + '_plot.png';
                            link.href = URL.createObjectURL(blob);
                            link.click();
                        }, 'image/png');
                    };
                    img.src = url;
                } else {
                    html2canvas(this.$refs.plotRef).then(canvas => {
                        const link = document.createElement('a');
                        link.download = 'quilt-plot.png';
                        link.href = canvas.toDataURL();
                        link.click();
                    });
                }

            }
        },

        async exportToPDF() {
            let combinedSVG
            if(this.exportDefault){
                const [svg, g] = this.generateQuilt(true)
                combinedSVG = this.getSVG(svg.node())
            } else{
                combinedSVG = this.getSVG(this.$refs.plotSvgRef)
            }

            // Create a canvas to render the SVGs
            const canvas = document.createElement('canvas');
            canvas.width = this.plotWidth;
            canvas.height = this.plotHeight;
            const ctx = canvas.getContext('2d');

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = combinedSVG
            document.body.appendChild(tempDiv);

            html2canvas(tempDiv).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                orientation: 'landscape',
                unit: 'px',
                format: [canvas.width, canvas.height]
                });
                pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
                pdf.save('quilt-plot.pdf');
            });
            tempDiv.innerHTML = ""
        },
        getSVG(refPointer){
            const titleSvg = this.$refs.plotRef.querySelector('svg');
            const plotSvg = refPointer
            const legendSvg = this.$refs.legendRef
            const svg1Content = new XMLSerializer().serializeToString(titleSvg);
            const plotContent = new XMLSerializer().serializeToString(plotSvg);
            const legendContent = new XMLSerializer().serializeToString(legendSvg);

            const combinedSVG = `
                <svg width="${this.isHeavyPlotType ? 1200: this.plotWidth}" height="${this.isHeavyPlotType ? this.plotHeight + 100 : this.plotHeight}"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                <g id="combined">
                    <g transform="translate(0, 0)">
                    ${svg1Content}
                    </g>
                    <g transform="translate(${this.isHeavyPlotType ? 150: 150}, ${this.isHeavyPlotType ? titleSvg.height.baseVal.value - 70 : titleSvg.height.baseVal.value - 70})">
                    ${plotContent}
                    </g>
                    <g transform="translate(20, ${titleSvg.height.baseVal.value})">
                    ${legendContent}
                    </g>
                </g>
                </svg>
            `;
            return combinedSVG
        },
        exportToSVG(){
            let svgPointer = this.exportDefault ? this.generateQuilt(true)[0] : this.$refs.plotSvgRef
            const combinedSVG = this.getSVG(this.exportDefault ? svgPointer.node() : svgPointer)
            const blob = new Blob([combinedSVG], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'quilt-plot.svg';
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
    position: absolute;
}

.plot-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

.plot-svg {
    width: 100%;
    min-height: 600px;
    cursor: move;
    /* border: 1px dashed; */
}

.legend-svg {
    width: 20%;
    height: 100%;
    position: absolute;
    left: 0;
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
.clusterLegendToggle {
    position: absolute;
    bottom: 100px;
    left:10px;
    align-items: center;
    border-radius: 8px;
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
.spinner{
    position: absolute;
    margin: auto;
    /* top: 5px; */
    align-items: center;
    stroke-width: 4;
    fill: none;
}
.tooltip {
  position: absolute;
  text-align: center;
  width: 120px;
  height: auto;
  padding: 8px;
  font-size: 12px;
  background: lightsteelblue;
  border: 0px;
  border-radius: 8px;
  pointer-events: none;
  z-index: 1001;
  top:15px;
  right:10px
}
</style>
