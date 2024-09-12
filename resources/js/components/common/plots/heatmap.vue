<template>

    <div ref="heatmapContainer" class="heatmap-container">
        <svg width="100%" height="100%">
            <svg ref="heatmapRef" class="heatmap"></svg>
            <svg ref="legendRef" class="legend-svg"></svg>
        </svg>
    </div>
</template>


<script>
import "@fortawesome/fontawesome-free/css/all.css";
import "bootstrap/dist/js/bootstrap.js";
import * as d3 from "d3";

export default {

    name: 'heatmap',
    props: {
        data: { type: Array, required: false },
        color_palette: { type: Object, required: false },
        csv_file: { type: String, required: true },
        heatmap_title: { type: String, required: true },
        csvHeaderGeneName: { type: String, required: true },
    },

    data() {
        return {
            //csvHeaderGeneName: 'gene_set',
            width: 600,
            height: 800,
            margin: { top: 50, right: 150, bottom: 350, left: 100 },
            xLabelHeight: 60,
            legendWidth: 40,
            legendHeight: 300,
            legendMin: 0,
            legendMax: 0,
            numSample: 0,
            numGene: 10,
            // colorPalette: ['blue', 'gray', 'red'],
            xValues: [],
            yValues: [],
            heatmapData: []
        };
    },

    computed: {
        svgWidth() {
            return this.width + this.margin.left + this.margin.right;
        },

        svgHeight() {
            return this.height + this.margin.top + this.margin.bottom;
        },

        /*
        heatmapColorScale() {

            return d3.scaleLinear()
                .domain([this.legendMin,
                        (this.legendMax - this.legendMin) / 2,
                        this.legendMax
                ])
                .range(this.color_palette);
        },

        legendColors() {
            return d3.range(0, 1.01, 0.01).map(d => this.colorScale(d));
        },
        */
    },

    async mounted() {

        console.log('...... Loading CSV file ' + this.csv_file);

        let csv_data_array = await d3.csv(`${this.csv_file}`)
            .then(data => {
                // The 'data' variable contains the parsed CSV data
                // console.log('Loading CSV file ' + this.csv_file + '; Data = ' + data);
                return data.slice(0,60);
            }).catch(error => {
                // Handle any errors that occur while loading the CSV file
                console.error('!!! Error loading CSV file:', error);
                return [];
            });

        console.log(':::::::::::: Loaded CSV file ' + this.csv_file + ': csv_data_array = ');
        console.log(csv_data_array);


        this.getHeatmapData(csv_data_array);
        this.drawHeatmap(this.heatmapData);
        this.drawGradientLegend();
    },

    methods: {

        /**
         * Convert the array of data objects from the CSV file to heatmap required data
         *
         * @param csv_data_array
         */
        getHeatmapData(csv_data_array) {

            let isInitLendengValueSet = false;
            csv_data_array.forEach((obj, index) => {
                // console.log('++++++ CSV file ' + this.csv_file + ': row ' + index);
                if (index < 1) {
                    const csv_header_array = Object.keys(obj);

                    // xValues is the list of all samples (the CSV file header except the 1st column 'gene_name')
                    this.xValues = csv_header_array.slice(1);
                    this.numSample = this.xValues.length;
                    console.log(this.xValues);
                }

                let yLabel = '';

                Object.entries(obj).forEach(([key, value]) => {
                    // console.log('     key = ' + key + '; value = ' + value);

                    if (key == this.csvHeaderGeneName) {
                        yLabel = value;

                        // yValues are the gene names -- values of the 1st column in CSV file
                        if (!this.yValues.includes(yLabel))
                            this.yValues.push(value);

                    } else {
                        // Get the min and max input values (map to the min and max colors)
                        if (!isInitLendengValueSet) {
                            if (!this.isEmpty(value)) {
                                this.legendMin = parseFloat(value);
                                this.legendMax = parseFloat(value);

                                isInitLendengValueSet = true;
                            }
                        } else {
                            this.legendMin = (!this.isEmpty(value) && parseFloat(value) < this.legendMin) ?
                                                        parseFloat(value) : this.legendMin;
                            this.legendMax = (!this.isEmpty(value) && parseFloat(value) > this.legendMax) ?
                                                        parseFloat(value) : this.legendMax;

                            // console.log("       index = " + index + "; key = " + key + "; typeof(value) = " + typeof(value)
                            //    + "; value = " + value + "; this.legendMin = " + this.legendMin + "; this.legendMax = " + this.legendMax);
                        }

                        let obj = {};
                        obj.x = key;
                        obj.y = yLabel;
                        obj.value = value;
                        this.heatmapData.push(obj);
                    }
                    // num++;
                });
                this.numGene = this.yValues.length;
            });

            console.log('====== this.numSample = ' + this.numSample + '; this.numGene = ' + this.numGene
                        + '; this.legendMin = ' + this.legendMin + '; this.legendMax = ' + this.legendMax);

            console.log(this.heatmapData);
        },

        /*
         * Draw heatmap from the input CSV data file of samples and genes
         *
         * @param in_data
         */
        drawHeatmap(in_data) {

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

            // Build the vertical X axis labels
            svg.append("g")
                .style("font-size", 15)
                .attr("transform", `translate(0, ${this.height})`)
                .call(d3.axisBottom(x).tickSize(0))
                .selectAll('text')
                .style("stroke", "none")
                .style('text-anchor', 'end')            // Anchor text to the end
                .attr('dx', '-1em')                     // Move text to the left
                .attr('dy', '.5em')                     // Move text up slightly
                .attr('transform', 'rotate(-90)')       // Rotate text vertically
                .select(".domain").remove();

            // Build Y scales and axis:
            const y = d3.scaleBand()
                .domain(this.yValues)
                .range([this.height, 0])
                .padding(0.05);

            svg.append("g")
                .attr('class', 'y-axis')
                .style("font-size", 15)
                .attr('transform', `translate(${this.width}, 0)`)
                .call(d3.axisRight(y).tickSize(0))
                .selectAll("text")
                .style("stroke", "none")
                .style("text-anchor", "start")    // Aligns text to start to fit in the available space
                .attr("dx", "0.5em")              // Offset to ensure labels don't overlap
                .attr("dy", "0.35em")
                .select(".domain").remove();

            // Build color scale for the heatmap
            const myColor = d3.scaleLinear()
                .domain([this.legendMin,
                        (this.legendMax - this.legendMin) / 2,
                        this.legendMax
                ])
                .range(this.color_palette);

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
                    .style("left", (event.x) / 2 + "px")
                    .style("top", (event.y) / 2 + "px")
            }

            const mouseleave = function (event, d) {
                tooltip
                    .style("opacity", 0)
                d3.select(this)
                    .style("stroke", "none")
                    .style("opacity", 0.8)
            }

            // Add the squares of the heatmap cell
            svg.selectAll()
                .data(in_data, function (d) { return d.x + ':' + d.y; })
                .join("rect")
                .attr("x", function (d) { return x(d.x) })
                .attr("y", function (d) { return y(d.y) })
                // .attr("rx", 4)
                // .attr("ry", 4)
                .attr("width", x.bandwidth() + 3.5)
                .attr("height", y.bandwidth() + 1)
                .style("fill", function (d) { return myColor(d.value) })
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
                .text(this.heatmap_title);
        },

        /**
         * Draw a vertical gradient legend
         */
        drawGradientLegend() {

            const legendSvg = d3
                .select(this.$refs.legendRef)
                .attr("width", this.legendWidth + 50)
                .attr("height", this.legendHeight + 50);

            legendSvg.selectAll("*").remove();
            console.log("------ drawGradientLegend(): this.color_palette = ")
            console.log(this.color_palette)

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
                .attr("stop-color", this.color_palette[2]);

            gradient
                .append("stop")
                .attr("offset", "50%")
                .attr("stop-color", this.color_palette[1]);

            gradient
                .append("stop")
                .attr("offset", "100%")
                .attr("stop-color", this.color_palette[0]);

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
        }
    }
};
</script>

<style scoped src="bootstrap/dist/css/bootstrap.css"></style>

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
</style>
