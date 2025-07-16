<template>
    <div class="container">
        <div ref="plotContainer" class="plot-area"></div>
        <div class="legend-svg">
                <svg ref="legendRef" ></svg>
        </div>

        <div class="buttons-panel" v-if="loading">
            <div class="btn-group-vertical">
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


    </div>


</template>

<script>
import * as d3 from 'd3';
import { debounce } from 'lodash'
import { exportAsPDF, exportAsPNG, exportAsSVG } from './utils'

export default {
    props: {
        samples: {type: Array, required: true},
        colorPalette: { type: Object, required: true },
        title: {type: String, required: true},
        isRawCount: {type: Boolean, required: false, default: false}
    },
    data() {
        return {
            data: {},
            density: 0.02,
            loading: false,
            activeColors: {}
        };
    },
    created(){
        // Create debounced function when component is created
        this.delayedRedraw = debounce(() =>{
            d3.select(this.$refs.plotContainer).selectAll('*').remove()
            this.drawDensity()
        }, 200)

    },
    beforeMount() {
        // Cancel any pending debouce calls when component is destroyed
        if(this.delayedRedraw){
            this.delayedRedraw.cancel();
        }
    },
    mounted() {
        this.fetchDataSet()
        this.createClusterLegend()
        this.initializeActiveColors()

    },
    methods: {

        async fetchData(samples){
            let data = {}
            try{
                const results = await Promise.all(
                    samples.map(async sample => {
                        let res = await d3.csv(`${this.base_path}/normalized_plots_counts_data_sample_${sample}.csv`)
                        return { sample, res }
                    })
                );
                results.forEach(({sample, res}) => {
                    data[sample] = res
                })

            } catch (error){
                console.log(error)
            }
            return data
        },

        async fetchDataSet(){
            try{
                this.data = await this.fetchData(this.samples)
            } catch (error){
                console.error('error fetching data:', error)
            } finally{
                this.loading = true
            }
            this.drawDensityPlot()

        },

        drawDensityPlot() {
            const margin = { top: 20, right: 120, bottom: 30, left: 40 };
            const width = 800 - margin.left - margin.right;
            const height = 400 - margin.top - margin.bottom;

            d3.select(this.$refs.plotContainer).selectAll('*').remove();
            console.log(this.data)
            const svg = d3.select(this.$refs.plotContainer)
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom)
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            // Improved kernel density estimation
            const weightedKDE = (kernel, thresholds, values, counts) => {
                return thresholds.map(t => {
                    let weightedSum = 0;
                    let totalWeight = 0;

                    for(let i = 0; i < values.length; i++) {
                        const weight = counts[i];
                        weightedSum += weight * kernel(t - values[i]);
                        totalWeight += weight;
                    }

                    return [t, weightedSum / totalWeight];
                });
            }

            const kde = (kernel, thresholds, data) => {
                return thresholds.map(t => [t, d3.mean(data, d => kernel(t - d))]);
            };

            const epanechnikov = bandwidth => x =>
                Math.abs(x /= bandwidth) <= 1 ? 0.75 * (1 - x * x) / bandwidth : 0;

            // Get domain across all samples
            const allValues = Object.values(this.data).flat().map(d => +d.value);
            const xDomain = d3.extent(allValues);

            const x = d3.scaleLinear()
                .domain(xDomain)
                .range([0, width]);

            // Generate density for each sample
            const densities = Object.entries(this.data).map(([sampleName, values]) => {
                const thresholds = d3.range(xDomain[0], xDomain[1], (xDomain[1] - xDomain[0]) / 100);
                // const weightedValues = values.flatMap(d =>
                //     Array(Math.round(+d.count)).fill(+d.value)
                // );
                // const density = kde(epanechnikov(this.density), thresholds, weightedValues);
                const density = weightedKDE(
                    epanechnikov(this.density),
                    thresholds,
                    values.map(d => +d.value),
                    values.map(d => +d.count)
                );
                return { sampleName, density };
            });

            // Find max density for y-scale
            const maxDensity = d3.max(densities, d => d3.max(d.density, v => v[1]));

            const y = d3.scaleLinear()
                .domain([0, maxDensity])
                .range([height, 0]);

            // Draw density areas
            const area = d3.area()
                .x(d => x(d[0]))
                .y1(d => y(d[1]))
                .y0(height)
                .curve(d3.curveBasis);

            densities.forEach(({ sampleName, density }) => {
                svg.append('path')
                    .datum(density)
                    .attr('fill', this.colorPalette[sampleName])
                    .attr('fill-opacity', 0.2)
                    .attr('stroke', this.colorPalette[sampleName])
                    .attr('stroke-width', 1)
                    .attr('d', area);
            });

            // Add axes
            svg.append('g')
                .attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(x).ticks(8));

            svg.append('g')
                .call(d3.axisLeft(y));

            // Add labels
            svg.append('text')
                .attr('x', width / 2)
                .attr('y', height + margin.bottom)
                .style('text-anchor', 'middle')
                .text(this.isRawCount ? "Raw Count" : 'Normalized expression');

            svg.append('text')
                .attr('transform', 'rotate(-90)')
                .attr('y', -margin.left)
                .attr('x', -height / 2)
                .attr('dy', '1em')
                .style('text-anchor', 'middle')
                .text('Density');
        },
        createClusterLegend() {
            const legendWidth =  150

            const legendItems = Object.entries(this.colorPalette);

            const legendHeight =  legendItems.length * 35

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
                legendSvg.attr('overflow-y', 'scroll')
        },
        initializeActiveColors() {
            for (const key in this.colorPalette) {
                this.activeColors[this.colorPalette[key]] = true;
            }
        },
        handleLegendClick(color) {

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

        async exportToPDF() {
            const svgElement = this.$refs.plotContainer.querySelector('svg')
            const svgData = new XMLSerializer().serializeToString(svgElement)
            exportAsPDF(svgData, this.title, svgElement.clientWidth, svgElement.clientHeight)
        },

        async exportToPNG(){
            const svg = this.$refs.plotContainer.querySelector('svg')
            const svgData = new XMLSerializer().serializeToString(svg)
            exportAsPNG(svgData, this.title, svg.clientWidth, svg.clientHeight)
        },
        exportToSVG(){
            const svg = this.$refs.plotContainer.querySelector('svg')
            const svgData = new XMLSerializer().serializeToString(svg)
            exportAsSVG(svgData, this.title)
        }



    }



}


</script>
<style scoped>

.container{
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    width: 100%;
    height: 100%;
}

.slider-container {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100px;
}

.controls {
    max-width: 8vw;
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    flex-direction: column;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: #f8f9fa;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.1);
    font-size: 0.8rem;
}

.buttons-panel {
    display: flex;
    flex-direction: column;
    gap: 5px;
    position: absolute;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    padding: 5px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    /* z-index: 2; */
    /* height: 120px; */
}


.legend-svg {
    width: 15%;
    height: 50%;
    padding-right: 1vw;
    position: absolute;
    left: 0;
    overflow: auto;
}

.plot-area {
    display: flex;
    flex-direction: row;
    justify-content: center;
    width: 100%;
    height: 100%;

}




</style>
