<template>
    <div class="pca-container">
        <div class="controls" v-if="showAxisSelection">
            <div class="mb-3">
                <label for="xAxisSelect" class="form-label">X-axis:</label>
                <select v-model="xAxis" @change="updatePlot" class="form-select form-select-sm" id="xAxisSelect">
                    <option v-for="pc in filteredXAxis " :key="pc" :value="pc" class="text-dark">{{ pc }}</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="xAxisSelect" class="form-label">Y-axis:</label>
                <select v-model="yAxis" @change="updatePlot" class="form-select form-select-sm"  id="yAxisSelect">
                    <option v-for="pc in filteredYAxis" :key="pc" :value="pc" class="text-dark">{{ pc }}</option>
                </select>
            </div>
        </div>

        <svg ref="pcaSvg"></svg>
        <svg ref="legendRef" class="legend-svg"></svg>

    </div>
  </template>

  <script>
  import * as d3 from 'd3';

  export default {
    props: {
      data: Array,
      colorPalette: { type: Object, required: true },
        plotType: { type: String, required: true },
    },

    data() {
      return{
        xAxis: null,
        yAxis: null,
        pcOptions: [],
        yAxisOptions: [],
        processedData: {},
        isValidData: true,
        showAxisSelection: false
      }
    },
    computed: {
        filteredYAxis() {
            return this.pcOptions.filter(option => option !== this.xAxis)
        },
        filteredXAxis(){
            return this.pcOptions.filter(option => option !== this.yAxis)
        }
    },
    mounted() {
        this.processData()
        this.initializePCOptions();
        this.createPCAPlot();
    },
    methods: {
        processData(){
            this.processedData = this.data.map((d) => {
                let res = { sample_name: d.sample_name }
                const keys = Object.keys(d)
                keys.forEach(key => {
                    if (!key.startsWith('sample_name')) {
                        res[key] = Number(d[key]); // Typecast dynamically
                    }
                });
                if (keys.length >= 3){
                    this.xAxis = keys[1]
                    this.yAxis = keys[2]
                    if (keys.length > 3){
                        this.showAxisSelection = true
                    }
                } else {
                    // At least 3 columns expected => validate correct data is inputted
                    this.isValidData = false
                }

                return res
            });

        },
        initializePCOptions(){
            if(this.data.length > 0){
                this.pcOptions = Object.keys(this.data[0]).filter(key => key.startsWith('PC'))
            }
        },
        updatePlot(){
            d3.select(this.$refs.pcaSvg).selectAll("*").remove()
            this.createPCAPlot()
        },
        createPCAPlot() {

            // Set up dimensions
            const margin = { top: 20, right: 20, bottom: 75, left: 65 },
            width = 500 - margin.left - margin.right,
            height = 500 - margin.top - margin.bottom;

            // Append SVG
            const svg = d3.select(this.$refs.pcaSvg)
            .attr('width', width + margin.left + margin.right)
            .attr('height', height + margin.top + margin.bottom)
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

            const xExtent = d3.extent(this.processedData, d => d[this.xAxis]);
            const xPadding = (xExtent[1] - xExtent[0]) * 0.15   ; // 5% padding

            const yExtent = d3.extent(this.processedData, d => d[this.yAxis]);
            const yPadding = (yExtent[1] - yExtent[0]) * 0.05; // 5% padding

            // Set up x scale
            const x = d3.scaleLinear()
            .domain([xExtent[0] - xPadding, xExtent[1] + xPadding])
            .range([0, width]);

            // Set up y scale
            const y = d3.scaleLinear()
            .domain([yExtent[0] - yPadding, yExtent[1] + yPadding])
            .range([height, 0]);


            const customTickFormat = d => {
                // If the number has more than 4 digits or less than -4 digits, use scientific notation
                if (Math.abs(d) >= 10000 || Math.abs(d) < 0.001) {
                    if (d === 0){
                        return d3.format("~")(d);
                    } else{
                        return d3.format(".1e")(d); // Use scientific notation
                    }
                } else {
                    return d3.format("~")(d); // Use integer formatting
                }
            }

            // Add x-axis
            svg.append('g')
                .attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(x).tickFormat(customTickFormat))
                .selectAll("text") // Select all x-axis labels
                .attr("transform", "rotate(-45)") // Rotate the labels by -45 degrees
                .style("text-anchor", "end");


            // Add x-axis label
            svg.append('text')
                .attr('x', width / 2)
                .attr('y', height + margin.bottom)
                .attr('dy', '-0.5em')
                .attr('text-anchor', 'middle')
                .text(this.xAxis);

            // Add y-axis
            svg.append('g')
            .call(d3.axisLeft(y).tickFormat(customTickFormat));

            // Add y-axis label
            svg.append('text')
                .attr('transform', 'rotate(-90)')
                .attr('x', -height / 2)
                .attr('y', -margin.left)
                .attr('dy', '1em')
                .attr('text-anchor', 'middle')
                .text(this.yAxis);

            // Add points (circles)
            svg.selectAll('circle')
            .data(this.processedData)
            .enter().append('circle')
            .attr('cx', d => x(d[this.xAxis]))
            .attr('cy', d => y(d[this.yAxis]))
            .attr('r', 5)
            .style('fill', d => this.colorPalette[d.sample_name]);


           // Add labels for points
            const labels = svg.selectAll('.point-label')
                .data(this.processedData)
                .enter().append('text')
                .attr('class', 'point-label')
                .attr('x', d => x(d[this.xAxis]) + 7) // Increased offset
                .attr('y', d => y(d[this.yAxis]) + 3)
                .text(d => d.sample_name)
                .style('font-size', '10px')
                .style('fill', 'black')
                .style('pointer-events', 'none'); // Prevent labels from interfering with mouse events


            this.createClusterLegend()
        },
        createClusterLegend() {
            const legendWidth =  150
            const legendHeight =  200

            const legendItems = Object.entries(this.colorPalette);

            const legendSvg = d3
                .select(this.$refs.legendRef)
                .attr("width", legendWidth)
                .attr("height", legendHeight);

            legendSvg.selectAll("*").remove();

            legendItems.forEach(([key, color], index) => {
                const yPosition = index * 30 + 20;
                // const isActive = this.activeColors[color];


                legendSvg
                    .append("circle")
                    .attr("cx", 20)
                    .attr("cy", yPosition)
                    .attr("r", 10)
                    .style("fill",  color);

                legendSvg
                    .append("text")
                    .attr("x", 40)
                    .attr("y", yPosition)
                    .attr("dy", "0.35em")
                    .attr("font-size", "12px")
                    .attr("font-family", "Arial, sans-serif")
                    .style("fill", "black")
                    .text(`${key}`);
            });
        },
    }
  };
  </script>

  <style>
    .pca-container {
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        width: 100%;
        height: 100%;
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
    .legend-svg {
        width: 20%;
        position: absolute;
        left: 10px;
        top:5px;
        overflow: auto;
    }
  </style>
