<template>
    <div class="table-responsive mt-3">
        <table class="table table-striped">
            <thead>
            <tr>
                <th></th>
                <th colspan="6" class="text-center">per spot/cell metrics</th>
            </tr>
            <tr>
                <th v-for="header in main_data.headers" scope="col">{{ header.replace('_per_spotcell', '').replace('_', ' ').replace('mean', 'average') }}</th>
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
    </div>
</template>

<script>
    export default {
        name: 'projectSummaryTable',

        props: {
            data: String,
            reference: {type: String, default: ''},
        },

        data() {
            return {
                main_data: {headers: [], rows: []},
                reference_data: {headers: [], rows: []},
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
        },

        methods: {
            csvToJSON: function(csv) {
                let lines = csv.split("\n");
                let rows = [];
                let headers;
                headers = lines[0].split(",");

                for (let i = 1; i < lines.length; i++) {
                    let obj = {};

                    if(lines[i] === undefined || lines[i].trim() === "") {
                        continue;
                    }

                    let words = lines[i].split(",");
                    for(let j = 0; j < words.length; j++) {
                        obj[headers[j].trim()] = words[j];
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
            }
        }
    }
</script>
