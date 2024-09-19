<template>
    <div ref="heatmapContainer" class="heatmap-container">
        <span v-if="!isLoaded">Loading CSV File ...</span>
        <div v-if="isLoaded">
            <svg v-if="isLoaded" :width="svgWidth" :height="svgHeight">
                <svg ref="heatmapRef" class="heatmap"></svg>
                <svg ref="legendRef" class="legend-svg"></svg>
                <svg ref="metaLegendRef" class="legend-svg"></svg>
            </svg>
        </div>
    </div>
</template>


<script>
import * as d3 from "d3";

export default {

    name: 'heatmap',
    props: {
        // data: { type: Array, required: false },
        colorPalette: { type: Array, required: true },
        csvFile: { type: String, required: true },
        heatmapTitle: { type: String, required: true },
        csvHeaderGeneName: { type: String, required: true },
        visibleSamples: { type: Array, required: false },
        metadataPalette: { type: Object, required: true },
        metadataValues: { type: Object, required: true },
        numberOfRowsToShow: { type: Number, default: 0 },
        showRowsFrom: { type: String, default: 'top' },
        samplesOrder: { type: Array, default: [] },
    },

    data() {
        return {
            isLoaded: false,
            width: 300,
            height: 500,
            svgWidth: 400,
            svgHeight: 650,
            margin: { top: 50, right: 300, bottom: 350, left: 150 },
            xLabelHeight: 60,
            // Vertical gradient heatmap legend
            legendWidth: 40,
            legendHeight: 200,
            legendMin: 0,
            legendMax: 0,
            // Heatmap data properties,
            numSample: 0,
            numGene: 10,
            xValues: [],
            yValues: [],
            csvDataArray: [],
            heatmapData: [],
            // Metadata properties
            metadataKeys: [],
            metadataSamples: [],
            metaValueObj: {},           // {"patient":["patient_1101","patient_1102"],"therapy":["adriamycin","none"]}
            metaColorObj: {},           // {"patient":["black","#964B00"],"therapy":["yellow","#00FF00"]}
            metaHeatmapData: [],
            // Vertical metadata legend
            metaLegendWidth: 40,
            metaLegendSvgHeight: 100,
            metaLegendHeight: 100,
            metaLegendMargin: { top: 40, right: 30, bottom: 30, left: 10 },
            metaLegendData: {}          // {"patient":[{"label":"patient_1101","color":"black"},{"label":"patient_1102","color":"#964B00"}],
                                        // "therapy":[{"label":"adriamycin","color":"yellow"},{"label":"none","color":"#00FF00"}]}
        };
    },

    watch: {
        samplesOrder() {
            this.initialize();
        },

        numberOfRowsToShow() {
            this.initialize();
        },
    },

    async mounted() {
        await this.initialize();
    },

    methods: {

        async setInitialValues() {
            this.xValues = [];
            this.yValues = [];
            this.csvDataArray = [];
            this.heatmapData = [];
            // Metadata properties
            this.metadataKeys = [];
            this.metadataSamples = [];
            this.metaValueObj = {};
            this.metaColorObj = {};           // {"patient":["black";"#964B00"];"therapy":["yellow";"#00FF00"]}
            this.metaHeatmapData = [];
            // Vertical metadata legend
            this.metaLegendWidth = 40;
            this.metaLegendSvgHeight = 100;
            this.metaLegendHeight = 100;
            this.metaLegendMargin = { top: 40, right: 30, bottom: 30, left: 10 };
            this.metaLegendData = {};
        },

        async initialize() {

            await this.setInitialValues();

            const funcName = 'mounted()';

            console.log('>>>>>> ' + funcName + ': Loading CSV file ' + this.csvFile);

            await this.getMetadataColors();
            await this.getMetadataHeatmapValues();

            let csvDataArray = await d3.csv(this.csvFile)
                .then(data => {

                    if(this.numberOfRowsToShow && this.numberOfRowsToShow < data.length) {
                        if(this.showRowsFrom.toLowerCase() !== 'top') {
                            const halfN = Math.floor(this.numberOfRowsToShow / 2);

                            // Get the first N/2 elements
                            const firstHalf = data.slice(0, halfN);

                            // Get the last N/2 elements
                            const lastHalf = data.slice(-halfN);

                            // Combine both into a new array
                            data = [...firstHalf, ...lastHalf];
                        } else {
                            data = data.slice(0, this.numberOfRowsToShow);
                        }
                    }

                    console.log(data);

                    // console.log('Loading CSV file ' + this.csvFile + '; Data = ' + data);

                    const metaKeyCount = this.isEmpty(this.metadataKeys) ? 0 : this.metadataKeys.length;
                    const rowCount = this.isEmpty(data) ? (metaKeyCount + 50) : (metaKeyCount + data.length);
                    this.height = rowCount * 12;

                    const heatmapSvgHeight = this.height + this.margin.top + this.margin.bottom;
                    const metaSvgHeight = this.isEmpty(this.metadataKeys) ? 0 :
                                        ((this.metaLegendHeight + this.metaLegendMargin.top + this.metaLegendMargin.bottom))
                                                * this.metadataKeys.length;

                    this.svgHeight = heatmapSvgHeight > metaSvgHeight ? heatmapSvgHeight : metaSvgHeight;

                    console.log('     ' + funcName + ': heatmapSvgHeight = ' + heatmapSvgHeight + '; metaSvgHeight = ' + metaSvgHeight
                                + '; this.svgHeight = ' + this.svgHeight);


                    return data;
                }).catch(error => {
                    // Handle any errors that occur while loading the CSV file
                    console.error('!!! Error loading CSV file:', error);
                    return [];
                });

            // console.log(csvDataArray);

            console.log('         ' + funcName + ': Loaded CSV file ' + this.csvFile
                + '; csvDataArray.length = ' + csvDataArray.length
                + '; this.svgHeight = ' + this.svgHeight
                + '; this.isLoaded = ' + this.isLoaded
                + '; this.metaValueObj = ' + JSON.stringify(this.metaValueObj)
                + '; this.metaColorObj = ' + JSON.stringify(this.metaColorObj));

            await this.getHeatmapData(csvDataArray);

            this.width = this.xValues.length*50
            this.svgWidth = this.width + this.margin.left + this.margin.right;

            this.isLoaded = true;

            await this.adjustMetaHeatmap();

            await this.drawHeatmap(this.heatmapData);
            await this.drawGradientLegend();
            await this.drawMetaLegend();
            console.log('<<<<<<<<<<<< ' + funcName);
        },

        /**
         * Parse the prop metadataPalette to create the metadata color and mapped value array:
         *
         * this.metaValueObj = { "patient": ["patient_1101","patient_1102"], "therapy": ["adriamycin","none"] }
         * this.metaColorObj = { "patient": ["black","#964B00"], "therapy" ["yellow","#00FF00"] }
         */
        async getMetadataColors() {

            const funcName = 'getMetadataColors()';

            this.metaValueObj = {};
            this.metaColorObj = {};
            this.metaLegendData = {};

            if (!this.isEmpty(this.metadataPalette)) {
                this.metadataKeys = Object.keys(this.metadataPalette);

                Object.entries(this.metadataPalette).forEach(([key, value]) => {
                    console.log('.... ' + funcName + ': key = ' + key + '; value = ' + JSON.stringify(value));

                    if (!this.isEmpty(value)) {
                        let valueArray = [];
                        let colorArray = [];

                        let legendData = [];

                        value.forEach(obj => {
                            Object.entries(obj).forEach(([val, color]) => {
                                if (!this.isEmpty(val)) {
                                    valueArray.push(val);
                                }

                                if (!this.isEmpty(color)) {
                                    colorArray.push(color);
                                }

                                legendData.push({
                                    label: this.isEmpty(val) ? '' : val,
                                    color: this.isEmpty(color) ? 'white' : color
                                });
                            });
                        });
                        // console.log('         ' + funcName + ': valueArray = ' + JSON.stringify(valueArray));
                        // console.log('         ' + funcName + ': colorArray = ' + JSON.stringify(colorArray));

                        this.metaValueObj[key] = valueArray.slice();
                        this.metaColorObj[key] = colorArray.slice();
                        this.metaLegendData[key] = legendData.slice();
                    }
                });
            }

            // console.log('........ ' + funcName + ': this.metaValueObj = ' + JSON.stringify(this.metaValueObj));
            // console.log('........ ' + funcName + ': this.metaColorObj = ' + JSON.stringify(this.metaColorObj));
            // console.log('........ ' + funcName + ': this.metaLegendData = ' + JSON.stringify(this.metaLegendData));
        },

        /**
         * Parse the prop metadata-values to get the heatmap data of metadata:
         *
         * this.metaLegendData =
         *      {
         *          "patient":[
         *              {"label":"patient_1101","color":"black"},
         *              {"label":"patient_1102","color":"#964B00"}
         *          ],
         *           "therapy":[
         *              {"label":"adriamycin","color":"yellow"},
         *              {"label":"none","color":"#00FF00"}
         *          ]
         *      }
         *
         */
        async getMetadataHeatmapValues() {

            const funcName = 'getMetadataHeatmapValues()';

            this.metaHeatmapData = [];
            this.metadataSamples = [];
            let metaYValues = [];

            if (!this.isEmpty(this.metadataValues)) {

                Object.entries(this.metadataValues).forEach(([key, value]) => {
                    // console.log(':::: ' + funcName + ': key = ' + key + ': value = ' + JSON.stringify(value));

                    if (!this.yValues.includes(key))
                        metaYValues.push(key);

                    if (!this.isEmpty(value)) {
                        value.forEach(obj => {
                            // console.log(':::::: ' + funcName + ': obj = ' + JSON.stringify(obj));
                            let metaObj = {};

                            Object.entries(obj).forEach(([sample, val]) => {
                                metaObj.x = this.isEmpty(sample) ? '' : sample;
                                metaObj.y = this.isEmpty(key) ? '' : key;
                                metaObj.value = this.isEmpty(val) ? '' : val;

                                if (!this.metadataSamples.includes(sample))
                                    this.metadataSamples.push(sample);

                                this.metaHeatmapData.push(metaObj);
                                /*
                                console.log(':::::::: ' + funcName + ': sample = ' + sample + '; val = ' + val
                                            + '; metaObj = ' + JSON.stringify(metaObj)
                                            + '; this.metaHeatmapData = ' + JSON.stringify(this.metaHeatmapData));
                                */
                            });
                        });
                    }
                });
            }

            this.yValues = [...metaYValues.reverse()];

            console.log(':::::::::: ' + funcName + ': this.metadataSamples = ' + JSON.stringify(this.metadataSamples)
                    + '; this.metaHeatmapData = ' + JSON.stringify(this.metaHeatmapData));
        },

        /**
         * Convert the array of data objects from the CSV file to heatmap required data:
         *
         * this.heatmapData = [
         *      {x: 'Lung5_Rep2_fov_12', y: 'patient', value: 'patient_1101'},
         *      {x: 'Lung5_Rep2_fov_13', y: 'patient', value: 'patient_1102'},
         *      {x: 'Lung5_Rep2_fov_12', y: 'therapy', value: 'adriamycin'},
         *      {x: 'Lung5_Rep2_fov_13', y: 'therapy', value: 'none'},
         *      {x: 'Lung5_Rep2_fov_12', y: 'HALLMARK_HYPOXIA', value: '1'},
         *      {x: 'Lung5_Rep2_fov_13', y: 'HALLMARK_HYPOXIA', value: '0.147857142857143'},
         *      {x: 'Lung5_Rep2_fov_12', y: 'HALLMARK_CHOLESTEROL_HOMEOSTASIS', value: '0'}],
         *          :
         *          :
         *      {x: 'Lung5_Rep2_fov_12', y: 'HALLMARK_PEROXISOME', value: 'NA'},
         *      {x: 'Lung5_Rep2_fov_13', y: 'HALLMARK_PEROXISOME', value: 'NA'}
         *  ]
         *
         * @param csvDataArray
         */
        async getHeatmapData(csvDataArray) {

            const funcName = 'getHeatmapData()';

            this.heatmapData = [...this.metaHeatmapData];

            let isInitLendengValueSet = false;
            csvDataArray.forEach((obj, index) => {
                // console.log('---- ' + funcName + ': CSV file ' + this.csvFile + ': row ' + index);
                if (index < 1) {
                    const csvHeaderArray = Object.keys(obj);

                    /**
                     * xValues is the array of all samples (the CSV file header except the 1st column)
                     * if input prop visibleSamples is empty. Otherwise is visibleSamples
                     */
                    this.xValues = this.isEmpty(this.visibleSamples) ? csvHeaderArray.slice(1) : this.visibleSamples;
                    if(this.samplesOrder.length) {
                        this.xValues = this.samplesOrder.filter(sample => this.xValues.includes(sample));
                    }

                    //limit to 10 samples max
                    this.xValues = this.xValues.length > 10 ? this.xValues.slice(0,10) : this.xValues;

                    this.numSample = this.xValues.length;
                    console.log('|||||||||||||||\\\\\\\\\\\\\\\\\\------ ' + funcName + ': this.xValues = ' + String(this.xValues));
                }

                let yLabel = '';

                Object.entries(obj).forEach(([key, value]) => {
                    if (!this.isEmpty(key) && key == this.csvHeaderGeneName) {
                        yLabel = value;

                        // yValues are the gene names -- values of the 1st column in the CSV file
                        if (!this.yValues.includes(yLabel))
                            this.yValues.push(value);

                    } else if (!this.isEmpty(key) && this.xValues.includes(key)) {
                        /* ---------------------------------------------------------------------
                            Show all samples if the input prop visibleSamples array is empty.
                            Otherwise only show the samples in visibleSamples
                        --------------------------------------------------------------------- */
                        // console.log('     key = ' + key + '; value = ' + value);

                        // Get the numeric min and max input values (map to the min and max colors)
                        if (!this.isEmpty(value) && !isNaN(value)) {        // Only check numeric value
                            if (!isInitLendengValueSet) {                   // The first numeric value
                                this.legendMin = parseFloat(value);
                                this.legendMax = parseFloat(value);

                                isInitLendengValueSet = true;
                            } else {                                        // Not the first numeric value
                                this.legendMin = (!this.isEmpty(value) && parseFloat(value) < this.legendMin) ?
                                    parseFloat(value) : this.legendMin;
                                this.legendMax = (!this.isEmpty(value) && parseFloat(value) > this.legendMax) ?
                                    parseFloat(value) : this.legendMax;

                                // console.log(funcName + ": index = " + index + "; key = " + key + "; value = "
                                //      + value + "; this.legendMin = " + this.legendMin + "; this.legendMax = " + this.legendMax);
                            }
                        }

                        let obj = {};
                        obj.x = key;
                        obj.y = yLabel;
                        obj.value = value;
                        this.heatmapData.push(obj);
                    }
                });
                this.numGene = this.yValues.length;
            });

            console.log('-------- ' + funcName + ': this.numSample = ' + this.numSample + '; this.numGene = '
                + this.numGene + '; this.legendMin = ' + this.legendMin + '; this.legendMax = ' + this.legendMax);

            console.log(this.heatmapData);
        },

        /*
        * Draw heatmap from the input CSV data file of samples and genes
        *
        * @param inData
        */
        async drawHeatmap(inData) {

            const funcName = 'drawHeatmap()';

            if (!d3.select(this.$refs.heatmapRef).select("svg").empty()) {
                d3.select(this.$refs.heatmapRef).select("svg").remove();
            }

            // Append the svg object to the body of the page
            const svg = d3.select(this.$refs.heatmapRef)
                .append("svg")
                .attr("width", this.width + this.margin.left + this.margin.right)
                .attr("height", this.height + this.margin.top + this.margin.bottom)
                .append("g")
                .attr("transform", `translate(${this.margin.left}, ${this.margin.top})`);

            // Build X scales and axis:
            const x = d3.scaleBand()
                .domain(this.xValues)
                .range([0, this.width])
                .padding(0.05);

            // Build the horizontal X axis labels
            svg.append("g")
                .style("font-size", 12)
                .attr("transform", `translate(0, ${this.height})`)
                .call(d3.axisBottom(x).tickSize(0))
                .selectAll('text')
                .style("stroke", "none")
                .style('text-anchor', 'end')            // Anchor text to the end
                .attr('dx', '-1em')                     // Move text to the left
                .attr('dy', '.5em')                     // Move text up slightly
                .attr('transform', 'rotate(-90)')       // Rotate text vertically
                .select(".domain").remove();

            // Build the vertical Y scales and axis:
            const y = d3.scaleBand()
                .domain(this.yValues)
                .range([this.height, 0])
                .padding(0.05);

            svg.append("g")
                .attr('class', 'y-axis')
                .style("font-size", 10)
                .attr('transform', `translate(${this.width}, 0)`)
                .call(d3.axisRight(y).tickSize(0))
                .selectAll("text")
                .style("stroke", "none")
                .style("text-anchor", "start")    // Aligns text to start to fit in the available space
                .attr("dx", "0.5em")              // Offset to ensure labels don't overlap
                .attr("dy", "0.35em")
                .select(".domain").remove();

            // Build color scale for the heatmap
            const naColor = 'gray';

            const myColor = d3.scaleLinear()
                .domain([this.legendMin,
                (this.legendMax - this.legendMin) / 2,
                this.legendMax
                ])
                .range(this.colorPalette);

            // Build color scale for each Metadata key
            let metaColorScaleObj = {};

            if (!this.isEmpty(this.metadataKeys)) {
                this.metadataKeys.forEach(key => {
                    // console.log('     ' + funcName + ': key = ' + key);
                    const domainArray = this.metaValueObj[key];
                    const rangeArray = this.metaColorObj[key];

                    // Create the color scale for the current metadata key
                    const metaColor = d3.scaleOrdinal()
                        .domain(domainArray)
                        .range(rangeArray);

                    metaColorScaleObj[key] = metaColor;
                })
            }

            // Create a tooltip
            const tooltip = d3.select(this.$refs.heatmapContainer)
                .append("div")
                .style("opacity", 0)
                .attr("class", "tooltip")
                .style("background-color", "white")
                .style("border", "solid")
                .style("border-width", "2px")
                .style("border-radius", "5px")
                .style("padding", "5px");

            // Three function that change the tooltip when user hover / move / leave a cell
            const mouseover = function (event, d) {
                tooltip
                    .style("opacity", 1)
                d3.select(this)
                    .style("stroke", "black")
                    .style("opacity", 1)
            }

            const mousemove = function (event, d) {
                tooltip
                    .html("The exact value of<br>this cell is: " + d.value)
                    // .style("left", (event.x) / 2 + "px")
                    // .style("top", (event.y) / 2 + "px")
                    .style('left', (event.pageX + 5) + 'px')
                    .style('top', (event.pageY + 5) + 'px')
            }

            const mouseleave = function (event, d) {
                tooltip
                    .style("opacity", 0)
                d3.select(this)
                    .style("stroke", "none")
                    .style("opacity", 0.8)
            }

            // Expand the heatmap cell width based on the visible sample count
            // to remove the horizontal gap between cells
            let widthExpand = 0;
            if (this.numSample > 0 && this.numSample < 4 ) {
                widthExpand = (7 - this.numSample) * 1.5;
            } else if (this.numSample >= 4 && this.numSample < 14) {
                widthExpand = (14 - this.numSample) * 0.42;
            }
            console.log('|||| ' + funcName + ': this.numSample = ' + this.numSample + '; widthExpand = ' + widthExpand);

            // Get the bottom heatmap yValue to add a vertical padding below the heatmap cells.
            // This will show a gap between the heatmap cells and metadata cells
            const metaKeyLength = this.isEmpty(this.metadataKeys) ? 0 : this.metadataKeys.length;
            const bottomHeatmapYValue = this.isEmpty(this.yValues) ? '' : this.yValues[metaKeyLength];

            // Add the squares of the heatmap cell
            svg.selectAll()
                .data(inData, function (d) { return d.x + ':' + d.y; })
                .join("rect")
                .attr("x", function (d) { return x(d.x) })
                .attr("y", function (d) { return y(d.y) })
                // .attr("rx", 4)
                // .attr("ry", 4)
                .attr("width", x.bandwidth() + widthExpand)
                .attr("height", function (d) {
                    if (metaKeyLength > 0 && d.y && d.y == bottomHeatmapYValue) {
                        // Add a vertical gap between the bottom heatmap cell row and the top metadta cell row
                        return y.bandwidth() - 1.5;
                    } else {
                        return y.bandwidth() + 1;
                    }
                })
                .style("fill", function (d) {
                    if (isNaN(d.value)) {                                   // Non-numeric cell
                        if (d.value.toUpperCase() === 'NA') {
                            return naColor;
                        } else {                                            // metadata cell
                            try {
                                return metaColorScaleObj[d.y](d.value);     // Apply metaColorScale
                            } catch (error) {
                                console.log('!!! ' + funcName + ': fill() has error: ' + error);
                                return naColor;
                            }
                        }
                    } else {                                                // Numeric cell
                        return myColor(d.value);                            // Apply numeric color scale
                    }
                })
                // .style("stroke-width", 0)
                .style("stroke", "none")
                .style("opacity", 0.8)
                .on("mouseover", mouseover)
                .on("mousemove", mousemove)
                .on("mouseleave", mouseleave);

            // Add title to the heatmap
            svg.append("text")
                .attr("x", this.width / 2)
                .attr("y", -15)
                .attr("text-anchor", "middle")
                .style("font-size", "18px")
                .style("max-width", 400)
                .text(this.heatmapTitle);
        },

        /**
         * Draw a vertical gradient legend
         */
        async drawGradientLegend() {

            const funcName = 'drawGradientLegend()';

            const legendSvg = d3
                .select(this.$refs.legendRef)
                .attr("width", this.legendWidth + 50)
                .attr("height", this.legendHeight + 50);

            legendSvg.selectAll("*").remove();
            console.log('==== ' + funcName + ': drawGradientLegend(): this.colorPalette = '
                        + JSON.stringify(this.colorPalette));

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
                .attr("y", 13)
                .attr("width", this.legendWidth)
                .attr("height", this.legendHeight)
                .style("fill", "url(#legend-gradient)");

            legendSvg
                .append("text")
                .attr("x", 30)
                .attr("y", 7)
                .attr("text-anchor", "middle")
                .attr("dominant-baseline", "middle")
                .attr("font-size", "12px")
                .attr("font-family", "Arial, sans-serif")
                .text("value");

            const legendScale = d3
                .scaleLinear()
                .domain([this.legendMin, this.legendMax])
                .range([this.legendHeight, 0]);

            const legendAxis = d3.axisRight(legendScale).ticks(5);

            legendSvg
                .append("g")
                .attr("transform", `translate(${this.legendWidth + 10}, 12)`)
                .call(legendAxis);
            },

        /**
         * Draw the vertical legend for each metadata key
         */
        async drawMetaLegend() {

            const funcName = 'drawMetaLegend()';

            console.log('???? ' + funcName + ': this.metaLegendData = ' + JSON.stringify(this.metaLegendData));

            const metaLegendSvg = d3.select(this.$refs.metaLegendRef);
            metaLegendSvg.selectAll("*").remove();          // Reset the metadata legend

            const metaLegendCellHeight = 15;
            const metaLegendCellMargin = 5;

            if (!this.isEmpty(this.metaLegendData)) {

                let legendYOffset = this.legendHeight;
                let legendValueCount = 0;

                Object.entries(this.metaLegendData).forEach(([key, valueArray]) => {

                    if (!this.isEmpty(key) && Array.isArray(valueArray)) {

                        legendYOffset += ((metaLegendCellHeight + metaLegendCellMargin) * legendValueCount)
                                                    + this.metaLegendMargin.top + this.metaLegendMargin.bottom;
                        /*
                        console.log('???????? ' + funcName + ': key = ' + key + '; valueArray = '
                                    + JSON.stringify(valueArray) + '; legendValueCount = ' + legendValueCount
                                    + '; legendYOffset = ' + legendYOffset);
                        */
                        legendValueCount = valueArray.length;

                        // Create a group for the legend
                        const legendGroup = metaLegendSvg.append('g')
                            .attr('transform', `translate(${this.metaLegendMargin.left}, ${legendYOffset + 30})`);

                        // Add metadata legend items
                        const legendItems = legendGroup.selectAll('.meta-legend-item')
                            .data(valueArray)
                            .enter()
                            .append('g')
                            .attr('class', 'meta-legend-item')
                            .attr('transform', (d, i) => `translate(0, ${i * (metaLegendCellHeight + metaLegendCellMargin)})`);

                        // Add colored cell of the metadata legend
                        legendItems.append('rect')
                            .attr('width', metaLegendCellHeight)
                            .attr('height', metaLegendCellHeight)
                            .style('fill', d => d.color);

                        // Add Y labels of the metadata legend
                        legendItems.append('text')
                            .attr('x', metaLegendCellHeight + 5)
                            .attr('y', metaLegendCellHeight / 2)
                            .style("font-size", 10)
                            .attr('dy', '.3em')
                            .text(d => d.label);

                        // Add title to the metadata legend
                        metaLegendSvg.append("text")
                            .attr("x", this.metaLegendWidth / 2 + 10)
                            .attr("y", legendYOffset + 20)
                            .attr("text-anchor", "middle")
                            .style("font-size", "12px")
                            .style("max-width", 100)
                            .text(key);
                    }
                })
            }
        },

        /**
         * Check if a variable is undefined, NULL, or empty.
         * Return true if value = undefined, null, '', [], {}
         * Otherwise return false
         *
         * @param value
         */
        isEmpty(value) {
            return (
                // null or undefined
                (value == null) || (typeof value === "undefined") ||

                // has length === zero
                (value.hasOwnProperty('length') && value.length === 0) ||

                // is an Object and has no keys
                (value.constructor === Object && Object.keys(value).length === 0) ||

                // is an empty or blank string
                (typeof value === 'string' && value.trim().length === 0)
            )
        },

        /**
         * If matadata smaple count < visible heatmap sample count:
         *      Add matadata heatmap cell data:
         *          x = visible heatmap smaple that are not in the list of matadata samples,
         *          y = matadata key,
         *          value = 'NA'
         *
         * Else if matadata smaple count > visible heatmap sample count:
         *      Add heatmap cell data:
         *          x = metadata smaple that are not in the list of visible heatmap samples,
         *          y = heatmap yValue (geneName from CSV file),
         *          value = 'NA'
         */
        async adjustMetaHeatmap() {

            const funcName = 'adjustMetaHeatmap()';

            console.log('%%%% ' + funcName + ': this.metadataSamples = ' + JSON.stringify(this.metadataSamples)
                        + '; this.xValues = ' + JSON.stringify(this.xValues));

            if (!this.isEmpty(this.metadataSamples) && !this.isEmpty(this.xValues)
                                && this.metadataSamples.length != this.xValues.length) {

                // Add metadata cell with value = 'NA' if the metadata smaple count < visible heatmap sampe count
                if (this.metadataSamples.length < this.xValues.length) {
                    this.xValues.forEach(xValue => {
                        if (!this.isEmpty(xValue) && !this.metadataSamples.includes(xValue)) {
                            if (!this.isEmpty(this.metadataKeys)) {
                                this.metadataKeys.forEach(key => {
                                    // console.log('%%%% ' + funcName + ': xValue = ' + xValue + '; yValue = ' + key);
                                    this.heatmapData.push({x: xValue, y: key, value: 'NA'});
                                })
                            }
                        }
                    })
                } else {
                    // Add heatmap cell with value = 'NA' if the metadata smaple count < visible heatmap sampe count
                    // this.metadataSamples.forEach(sample => {
                    //     if (!this.isEmpty(sample) && !this.xValues.includes(sample)) {
                    //         this.xValues.push(sample);
                    //         if (!this.isEmpty(this.yValues)) {
                    //             this.yValues.forEach(yValue => {
                    //                 if (!this.isEmpty(yValue) && !this.isEmpty(this.metadataKeys)
                    //                                         && !this.metadataKeys.includes(yValue)) {
                    //                     // console.log('@@@@@@ ' + funcName + ': sample = ' + sample + '; yValue = ' + yValue);
                    //                     this.heatmapData.push({x: sample, y: yValue, value: 'NA'});
                    //                 }
                    //             })
                    //         }
                    //     }
                    // })
                }
                console.log('%%%%%%%%%%%% ' + funcName + ': this.heatmapData = ' + JSON.stringify(this.heatmapData));
            }
        },
    },
};
</script>

<style scoped>
.heatmap-container {
    width: 80%;
    height: 100%;
}

.heatmap rect {
    stroke: #ddd;
}

.legend rect {
    stroke: none;
}

.legend text {
    font-size: 12px;
}

.y-axis path,

.y-axis line {
    shape-rendering: crispEdges;
    stroke: none;
    /* Ensures crisp edges for axis lines */
}

.y-axis line,

.y-axis path {
    display: none;
    stroke: none;
    /* Hide the Y-axis line and ticks */
}

.legend-svg {
    width: 20%;
    height: 100%;
    position: absolute;
    left: 0;
}

.meta-legend-item text {
    font-size: "12px";
}
</style>
