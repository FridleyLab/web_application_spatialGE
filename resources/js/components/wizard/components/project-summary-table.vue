<template>
    <div class="table-responsive mt-3">


        <data-grid :scrolling-toggle="false" :headers="main_data.headers" :data="main_data.rows" :show-gene-card="false" :allow-selection="allowSelection" key-attribute="sample_name" :page-size="10" :selected-keys="selectedKeys" @selected="selectedKeysChanged"></data-grid>

        <div v-if="downloadButton && url.length" class="float-end"><a class="text-info" :href="url" download>Download summary</a></div>


        <!-- <table class="table table-striped">
            <thead>
            <tr>
                <th></th>
                <th colspan="6" class="text-center">per spot/cell metrics</th>
            </tr>
            <tr>
                <th v-for="header in main_data.headers" scope="col">{{ header in header_names ? header_names[header] : header.replace('_per_spotcell', '').replace('_', ' ').replace('mean', 'average') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(row, row_index) in main_data.rows">
                <td v-for="(attr, col_index) in main_data.headers" class="text-center">
                    <div v-if="showReference" class="text-xs text-secondary">{{ (attr !== 'sample_name') ? formatValue(reference_data.rows[row_index][attr]) : 'original value' }}</div>
                    <div :class="showReference ? 'text-info' : ''">{{ formatValue(row[attr]) }}</div>
                </td>
            </tr>

            </tbody>
        </table>
        <div v-if="url.length" class="float-end"><a class="text-info" :href="url" download>Download summary</a></div> -->
    </div>
</template>

<script>
    export default {
        name: 'projectSummaryTable',

        emits: ['selected'],

        props: {
            data: String,
            reference: {type: String, default: ''},
            url: {type: String, default: ''},
            selectedKeys: {type: Object, default: []},
            allowSelection: {type: Boolean, default: true},
            downloadButton: {type: Boolean, default: false},
        },

        data() {
            return {
                main_data: {headers: [], rows: []},
                reference_data: {headers: [], rows: []},

                header_names: {
                    sample_name: 'Sample name',
                    spotscells: 'Total spots/cells',
                    genes: 'Total genes',
                    min_counts_per_spotcell: 'Min. counts',
                    mean_counts_per_spotcell: 'Avg. counts',
                    max_counts_per_spotcell: 'Max. counts',
                    min_genes_per_spotcell: 'Min. genes',
                    mean_genes_per_spotcell: 'Avg. genes',
                    max_genes_per_spotcell: 'Max. genes'
                },
            }
        },

        computed: {
            showReference() {
                return this.reference.length;
            }
        },

        created() {
            this.main_data = this.csvToJSON(this.data);
            this.reference_data = this.reference.length ? this.csvToJSON(this.reference) : {};

            //console.log(this.main_data);
        },

        methods: {
            csvToJSON: function(csv) {
                let lines = csv.split("\n");
                let rows = [];
                let _headers = lines[0].split(",");
                let headers = [];

                for (let i = 0; i < _headers.length; i++) {
                    headers.push( { value: _headers[i], text: this.header_names[_headers[i]] } );
                }

                for (let i = 1; i < lines.length; i++) {
                    let obj = {};

                    if(lines[i] === undefined || lines[i].trim() === "") {
                        continue;
                    }

                    let words = lines[i].split(",");
                    for(let j = 0; j < words.length; j++) {
                        obj[_headers[j].trim()] = j === 0 ? words[j] : Math.round(words[j]);
                    }

                    rows.push(obj);
                }
                return {headers: headers, rows: rows};
            },

            formatValue: function(value) {
                return isNaN(value) ? value : this.numberFormat(parseInt(value));
                // return isNaN(row[attr]) ? row[attr] : numberFormat(parseInt(row[attr]))
            },

            numberFormat: function (x) {
                return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            },

            selectedKeysChanged: function (keys) {
                //console.log(keys);
                this.$emit('selected', keys);
            }
        }
    }
</script>
