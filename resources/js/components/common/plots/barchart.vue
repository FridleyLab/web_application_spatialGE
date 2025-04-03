<template>

    <div :class="samples.length >  1 ? 'd-flex' : ''">
        <div v-if="samples.length === 1" class="p-1">
            Cell type proportions
        </div>
        <div ref="chart" ></div>
        <div v-if="samples.length > 1" :style="'margin-top: ' + margin.top + 'px'">
            <!-- <div v-for="(color, index) in samplesData[samples[0]]['colors']">
                <span :style="{color: color}">{{samplesData['names'][index]}}</span>
            </div> -->
            <div id="legend"></div>
        </div>
    </div>
    </template>


    <script>
    import * as d3 from "d3";
    export default {

      name: 'barchart',
      props: {
        cellTypeNames: { type: Array, required: true },
        counts:  { type: Array, required: true },
        colors:  { type: Array, required: true },
        samplesData:  { type: Object, required: true },
        samples: { type: Array, default: () => [] }
      },
      data() {
        return {
          data: [
            { category: 'A', group1: 30, group2: 50, group3: 20 },
            { category: 'B', group1: 80, group2: 35, group3: 55 },
            { category: 'C', group1: 45, group2: 85, group3: 40 }
          ],
          margin: { top: 30, right: 30, bottom: 70, left: 60 },
          width: 150,
          height: 700,
          processedCellTypeNames: this.cellTypeNames,
          processedCounts: this.counts,
          processedColors: this.colors
        };
      },
    //   watch: {
    //     colors: {
    //       handler() {
    //         this.processedCellTypeNames = [];
    //         this.processedCounts = [];
    //         this.processedColors = [];
    //         const svg = d3.select(this.$refs.chart);
    //         d3.selectAll("svg").remove();
    //         this.preprocessData();
    //         this.drawChart();
    //         console.log('colors changed');
    //       }
    //     }
    //   },
      computed: {
        chartWidth() {
          return this.width - this.margin.left - this.margin.right;
        },
        chartHeight() {
          return this.height - this.margin.top - this.margin.bottom;
        }
      },
      mounted() {
        //this.preprocessData();
        this.drawChart();

        // console.log(this.processedCellTypeNames, this.processedCounts, this.processedColors);

      },
      methods: {
        preprocessData() {
          const { cellTypeNames, counts, colors } = this;
          const nameMap = new Map();

          cellTypeNames.forEach((name, index) => {
            if (nameMap.has(name)) {
              // If the name already exists, perform union by rank
              const existingIndex = nameMap.get(name);
              if (this.processedCounts[existingIndex] <= counts[index]) {
                this.processedColors[existingIndex] = colors[index]; // Choose color based on higher count
              }
              this.processedCounts[existingIndex] += counts[index]; // Sum the counts

            } else {
              const newIndex = this.processedCellTypeNames.length;
              nameMap.set(name, newIndex);
              this.processedCellTypeNames.push(name);
              this.processedCounts.push(counts[index]);
              this.processedColors.push(colors[index]);
            }
          });
        },

        drawChart() {
          const svg = d3.select(this.$refs.chart)
            .append('svg')
            .attr('width', this.width/(this.samples.length > 1 ? 2 : 1) * this.samples.length)
            .attr('height', this.height + (this.samples.length > 1 ? 50 : 0))
            .append('g')
            .attr('transform', `translate(${this.margin.left},${this.margin.top})`);

        //   const sum = d3.sum(this.processedCounts)
        //   const data = [
        //     this.processedCellTypeNames.reduce((obj, name, index) => {
        //       obj[name] = (this.processedCounts[index] / sum ) * 100;
        //       return obj;
        //     }, { category: 'Cell Types' })
        //   ];



            const data = this.samples.map(function(sampleName) {
                const sum = d3.sum(this.samplesData[sampleName]['counts']);
                return this.samplesData['names'].reduce((obj, name, index) => {
                obj[name] = (this.samplesData[sampleName]['counts'][index] / sum ) * 100;
                return obj;
                }, { category: this.samples.length ===1 ? 'Cell Types' : sampleName, _counts: this.samplesData[sampleName]['counts'] });
            }, this);

        //   console.log(data);

          const subgroups = this.samplesData['names'];
          const groups = this.samples.length === 1 ? ['Cell Types'] : this.samples;

          const x = d3.scaleBand()
            .domain(groups)
            .range([0, this.chartWidth * this.samples.length])
            .padding([0.2]);
          if(this.samples.length === 1) {
            svg.append('g')
            .attr('transform', `translate(0,${this.chartHeight})`)
            .call(d3.axisBottom(x).tickSize(0));
          } else {
          svg.append("g")
            .attr("transform", `translate(0,${this.chartHeight})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .style("text-anchor", "end")
            .attr("dx", "-0.5em")
            .attr("dy", "0.5em")
            .attr("transform", "rotate(-45)");
        }

          const y = d3.scaleLinear()
            // .domain([0, d3.sum(this.processedCounts)])
            .domain([0, 100])
            .range([this.chartHeight, 0]);
          svg.append('g')
            .call(d3.axisLeft(y));


          const stackedData = d3.stack()
          .keys(subgroups)(data);

          const color = d3.scaleOrdinal()
            .domain(subgroups)
            .range(this.samplesData[this.samples[0]]['colors']);


          const tooltip = d3.select(this.$refs.chart)
          .append('div')
          .style('opacity', 0)
          .attr('class', 'tooltip')
          .style('position', 'relative')
          .style('background-color', 'white')
          .style('border', 'solid')
          .style('border-width', '1px')
          .style('border-radius', '5px')
          .style('padding', '5px');

          const showTooltip = (event, d) => {
            // console.log(`Type: ${d.key}, percentage: ${Math.round(d['1'] -d['0'])}%`);
            tooltip.transition()
              .duration(200)
              .style('opacity', 100);
            tooltip.html(`Type: ${d.key}, total counts: ${d['data']['_counts'][this.samplesData['names'].indexOf(d.key)]}, percentage: ${Math.round(d['1'] -d['0'])}%`)
            //   .style('left', `${event.pageX + 10}px`)
            //   .style('top', `${event.pageY - 28}px`);
          };

          const hideTooltip = () => {
            tooltip.transition()
              .duration(500)
              .style('opacity', 0);
          };


            svg.append('g')
            .selectAll('g')
            .data(stackedData)
            .enter().append('g')
            .attr('fill', d => color(d.key))
            .attr("class", d => `bar-group ${d.key.replaceAll('.', '-')}`)
            .selectAll('rect')
            .data(d => d.map(v => ({ ...v, key: d.key })))
            .enter().append('rect')
            .attr('x', d => x(d.data.category))
            .attr('y', d => y(d[1]))
            .attr('height', d => y(d[0]) - y(d[1]))
            .attr('width', x.bandwidth())
            .on('mouseover',  showTooltip)
            .on('mousemove', d => showTooltip)
            .on('mouseout', hideTooltip);


            // Create legend
            const legend = d3.select("#legend");
            legend.selectAll("div")
                .data(this.samplesData['names'])
                .enter()
                .append("div")
                .attr("class", "legend-item")
                .style("background", d => color(d))
                .text(d => d)
                .on("mouseover", function(event, key) {
                    console.log(key.replaceAll('.', '-'));
                    d3.selectAll(".bar-group").classed("dim", true);
                    d3.selectAll(`.${key.replaceAll('.', '-')}`).classed("highlight", true).classed("dim", false);
                })
                .on("mouseout", function() {
                    d3.selectAll(".bar-group").classed("dim", false).classed("highlight", false);
                });


        }
      }
    };
    </script>

    <style scoped>
    .chart-container {
      margin: 20px;
    }

    ::v-deep(.legend-item) { cursor: pointer; margin: 2px; display: block; padding: 1px; border-radius: 3px; color:white }
    ::v-deep(.highlight) { opacity: 1 !important; }
    ::v-deep(.dim) { opacity: 0.1; }

    </style>
