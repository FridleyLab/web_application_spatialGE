<template>
    <div ref="violinPlotContainer"></div>
</template>


<script>
import * as d3 from 'd3';


export default {
    props: {
        data: { type: Array, required: true },
        colorPalette: { type: Object, required: true },
        plotType: { type: String, required: true },
        plotVariable: { type: String, default: 'total_counts' },
    },
    data() {

    },

    async mounted() {

        await this.drawPlot();

    },

    watch: {
        plotVariable() {
            this.drawPlot();
        }
    },

    methods: {

        async drawPlot() {

            if (this.plotType === "violin"){
                this.drawViolinHistogram()
            } else {
                this.drawBoxPlot()
            }
        },


        drawViolin(){
            const container = d3.select(this.$refs.violinPlotContainer);

            const margin = { top: 20, right: 30, bottom: 40, left: 40 };
            const width = 800;
            const height = 800;

            // Create the SVG
            const svg = container
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom)
                .append('g')
                .attr("viewBox", `100 100 ${width} ${height}`)
                .attr("preserveAspectRatio", "xMidYMid meet")
                .attr('transform', `translate(${margin.left},${margin.top})`);

            // Group data by samplename
            const groupedData = d3.groups(this.data, d => d.samplename);

            // Create Scales
            const xScale = d3.scaleBand()
                .domain(groupedData.map(d => d[0])) // Use the samplename
                .range([0, width])
                .padding(0.05);

            const yScale = d3.scaleLinear()
                .domain([d3.min(this.data, d => d[this.plotVariable]), d3.max(this.data, d => d[this.plotVariable])])
                .range([height, 0]);

            const countsScale = d3.scaleLinear()
                .domain([0, d3.max(groupedData, ([, sampleData]) => sampleData.length)]) // Based on group size
                .range([0, xScale.bandwidth() / 2]);

            // Kernel Density Estimation for total_counts
            const kernelDensityEstimator = (kernel, X) => {
                return X.map(x => [x, d3.mean(X, v => kernel(x - v))]);
            };
            // const kernelDensityEstimator = d3.kernelDensityEstimator(d3.kernelEpanechnikov(.2), y.ticks(50))

            const kernelEpanechnikov = bandwidth => {
                return x => Math.abs(x /= bandwidth) <= 1 ? 0.75 * (1 - x * x) / bandwidth : 0;
            };

            groupedData.forEach(([samplename, sampleData]) => {
                // Kernel Density Estimation for the total_counts within this group
                const density = kernelDensityEstimator(
                kernelEpanechnikov(50), // Adjust bandwidth for smoothness
                yScale.ticks(40)
                );

                // Create Violin Path for each sample
                const area = d3.area()
                .x0(d => -countsScale(d[1])) // Left side of the violin
                .x1(d => countsScale(d[1]))  // Right side of the violin
                .y(d => yScale(d[0]))
                .curve(d3.curveCatmullRom); // Smooth curve

                svg.append('path')
                .datum(density)
                .attr('transform', `translate(${xScale(samplename) + xScale.bandwidth() / 2},0)`)
                .attr('fill', this.colorPalette[samplename])
                .attr('stroke', '#000')
                .attr('stroke-width', 1)
                .attr('d', area);
            });

            // Add X Axis (samplenames)
            svg.append('g')
                .attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(xScale));

            // Add Y Axis (total_counts)
            svg.append('g')
                .call(d3.axisLeft(yScale));
        },
        drawViolinHistogram(){
            const container = d3.select(this.$refs.violinPlotContainer);

            const margin = { top: 20, right: 30, bottom: 40, left: 40 };
            const width = 800;
            const height = 600;

            // Create the SVG
            const svg = container
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom +100)
                .append('g')
                .attr("viewBox", `100 100 ${width} ${height}`)
                .attr("preserveAspectRatio", "xMidYMid meet")
                .attr('transform', `translate(${margin.left},${margin.top})`);

            // allow_list for testing/validation purposes with Oscar
            // const allow_list = ['Lung5_Rep1_fov_1', 'Lung5_Rep1_fov_10', 'Lung5_Rep1_fov_12', 'Lung5_Rep1_fov_2', 'Lung5_Rep1_fov_30', 'Lung5_Rep1_fov_11', 'Lung5_Rep1_fov_22', 'Lung5_Rep1_fov_29',
            //     'Lung5_Rep1_fov_9', 'Lung5_Rep1_fov_13', 'Lung5_Rep1_fov_16', 'Lung5_Rep1_fov_27', 'Lung5_Rep1_fov_14', 'Lung5_Rep1_fov_4', 'Lung5_Rep1_fov_5', 'Lung5_Rep1_fov_7', 'Lung5_Rep1_fov_33', 'Lung5_Rep1_fov_35', 'Lung5_Rep1_fov_40', 'Lung5_Rep1_fov_43'
            // ]
            // Group data by samplename
            let groupedData = d3.groups(this.data, d => d.samplename);
            // groupedData = groupedData.filter(([samplename, sampleData]) => allow_list.includes(samplename))

            const xScale = d3.scaleBand()
                .domain(groupedData.map(d => d[0]))
                .range([0, width])
                .padding(0.05);

            const yScale = d3.scaleLinear()
                .domain([d3.min(this.data, d => +d[this.plotVariable]), d3.max(this.data, d => +d[this.plotVariable])])
                .range([height, 0]);

            groupedData.forEach(([samplename, sampleData]) => {
                const totalCounts = sampleData.map(d => +d[this.plotVariable]);
                let max = d3.max(totalCounts) // local max of samplename data

                // Create a histogram for the total_counts values
                const histogram = d3.histogram()
                .domain([0, max]) // Use the max point of the sample data to scale the domain
                .thresholds(yScale.ticks(20)) // Adjust the number of bins
                (totalCounts);

                // Creates symmetry from left/right side
                const countsScale = d3.scaleLinear()
                .domain([0, d3.max(histogram, d => d.length)]) // Maximum count in this group
                .range([0, xScale.bandwidth() / 2]);


                const area = d3.area()
                .x0(d => -countsScale(d.length)) // Left side of the violin (frequency counts)
                .x1(d => countsScale(d.length))  // Right side of the violin (mirrored frequency)
                .y(d => yScale(d.x0))
                .curve(d3.curveCatmullRom);

                svg.append('path')
                .datum(histogram)
                .attr('transform', `translate(${xScale(samplename) + xScale.bandwidth() / 2},0)`)
                .attr('fill', this.colorPalette[samplename])
                .attr('stroke', '#000')
                .attr('stroke-width', 1)
                .attr('d', area);

            });

            svg.append('g')
                .attr('transform', `translate(0,${height + 5})`)
                .call(d3.axisBottom(xScale))
                .selectAll("text") // Select all x-axis labels
                .attr("transform", "rotate(-45)") // Rotate the labels by -45 degrees
                .style("text-anchor", "end");

            // Add Y axis label for clarity (optional)

            svg.append('g')
                .call(d3.axisLeft(yScale));
            svg.append("text")
            .attr("transform", "rotate(-90)") // Rotate the text to align with the y-axis
            .attr("y", 0 - margin.left) // Adjust y position for label
            .attr("x", 0 - (height / 2)) // Position the label in the middle of the y-axis
            .attr("dy", "1em") // Padding for the text
            .style("text-anchor", "middle") // Center the text
            .text(this.plotVariable); // Label text
        },
        drawBoxPlot(){
            const container = d3.select(this.$refs.violinPlotContainer);

            const margin = { top: 20, right: 30, bottom: 40, left: 40 };
            const width = 800;
            const height = 800

            // Create the SVG
            const svg = container
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom + 100)
                .append('g')
                .attr("viewBox", `100 100 ${width} ${height}`)
                .attr("preserveAspectRatio", "xMidYMid meet")
                .attr('transform', `translate(${margin.left},${margin.top})`);

            // allow_list for testing/validation purposes with Oscar
            // const allow_list = ['Lung5_Rep1_fov_1', 'Lung5_Rep1_fov_10', 'Lung5_Rep1_fov_12', 'Lung5_Rep1_fov_2', 'Lung5_Rep1_fov_30', 'Lung5_Rep1_fov_11', 'Lung5_Rep1_fov_22', 'Lung5_Rep1_fov_29',
            // 'Lung5_Rep1_fov_9', 'Lung5_Rep1_fov_13', 'Lung5_Rep1_fov_16', 'Lung5_Rep1_fov_27', 'Lung5_Rep1_fov_14', 'Lung5_Rep1_fov_4', 'Lung5_Rep1_fov_5', 'Lung5_Rep1_fov_7', 'Lung5_Rep1_fov_33', 'Lung5_Rep1_fov_35', 'Lung5_Rep1_fov_40', 'Lung5_Rep1_fov_43'
            // ]
            const groupedData = d3.groups(this.data, d => d.samplename);

            // groupedData = groupedData.filter(([samplename, sampleData]) => allow_list.includes(samplename))
            const xScale = d3.scaleBand()
            .domain(groupedData.map(d => d[0]))
            .range([0, width])
            .padding(0.2);

            const yScale = d3.scaleLinear()
            .domain([d3.min(this.data, d => +d[this.plotVariable]), d3.max(this.data, d => +d[this.plotVariable])])
            .range([height, 0]);


            const boxWidth = xScale.bandwidth() * 0.5;
            groupedData.forEach(([samplename, sampleData]) => {
                const totalCounts = sampleData.map(d => +d[this.plotVariable])
                // Compute the necessary values for the box plot (quartiles, median, etc.)
                const q1 = d3.quantile(totalCounts.sort(d3.ascending), 0.25);
                const median = d3.quantile(totalCounts.sort(d3.ascending), 0.5);
                const q3 = d3.quantile(totalCounts.sort(d3.ascending), 0.75);
                const interQuantileRange = q3 - q1;
                const min = Math.max(q1 - 1.5 * interQuantileRange, 0);
                // d3.min(totalCounts)
                //
                const max = q3 + 1.5 * interQuantileRange;
                // d3.max(totalCounts)
                //
                const center = xScale(samplename) + xScale.bandwidth() / 2;

                svg.append('rect')
                .attr('x', center - boxWidth / 2) // Center the box in the middle of the category
                .attr('y', yScale(q3))
                .attr('height', yScale(q1) - yScale(q3)) // Height is q3 - q1
                .attr('width', boxWidth) // Box width
                .attr('stroke', 'black')
                .attr('fill', this.colorPalette[samplename]);

                // Median Line
                svg.append('line')
                .attr('x1', center - boxWidth / 2)
                .attr('x2', center + boxWidth / 2)
                .attr('y1', yScale(median))
                .attr('y2', yScale(median))
                .attr('stroke', 'black')
                .attr('stroke-width', 2);

                svg.append('line') // Min line
                    .attr('x1', xScale(samplename) + xScale.bandwidth() / 2)
                    .attr('x2', xScale(samplename) + xScale.bandwidth() / 2)
                    .attr('y1', yScale(min))
                    .attr('y2', yScale(q1))
                    .attr('stroke', 'black');

                svg.append('line') // Max line
                    .attr('x1', xScale(samplename) + xScale.bandwidth() / 2)
                    .attr('x2', xScale(samplename) + xScale.bandwidth() / 2)
                    .attr('y1', yScale(q3))
                    .attr('y2', yScale(max))
                    .attr('stroke', 'black');

                // Whiskers
                svg.append('line') // Bottom whisker
                    .attr('x1', xScale(samplename) + xScale.bandwidth() / 4)
                    .attr('x2', xScale(samplename) + 3 * xScale.bandwidth() / 4)
                    .attr('y1', yScale(min))
                    .attr('y2', yScale(min))
                    .attr('stroke', 'black');

                svg.append('line') // Top whisker
                    .attr('x1', xScale(samplename) + xScale.bandwidth() / 4)
                    .attr('x2', xScale(samplename) + 3 * xScale.bandwidth() / 4)
                    .attr('y1', yScale(max))
                    .attr('y2', yScale(max))
                    .attr('stroke', 'black');

                let outliers = totalCounts.filter(d => d > max || d < min)

                svg
                .selectAll("indPoints")
                .data(outliers)
                .enter()
                .append("circle")
                    .attr("cx", xScale(samplename) + xScale.bandwidth() / 2)
                    .attr("cy", d => yScale(d))
                    .attr("r", 2)
                    .style("fill", this.colorPalette[samplename])
                    .attr("stroke", "black")
            });

            // Add X and Y axis
            svg.append('g')
            .attr('transform', `translate(0,${height + 5})`) // height + 5 value may need to be altered for styling
            .call(d3.axisBottom(xScale))
            .selectAll("text") // Select all x-axis labels
            .attr("transform", "rotate(-45)") // Rotate the labels by -45 degrees
            .style("text-anchor", "end");

            svg.append('g')
            .call(d3.axisLeft(yScale));

            svg.append("text")
            .attr("transform", "rotate(-90)") // Rotate the text to align with the y-axis
            .attr("y", 0 - margin.left) // Adjust y position for label
            .attr("x", 0 - (height / 2)) // Position the label in the middle of the y-axis
            .attr("dy", "1em") // Padding for the text
            .style("text-anchor", "middle") // Center the text
            .text(this.plotVariable); // Label text
        }
    }




}



</script>
