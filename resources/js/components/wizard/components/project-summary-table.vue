<template>
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th></th>
                <th colspan="6" class="text-center text-xs">per spot/cell</th>
            </tr>
            <tr>
                <th v-for="header in headers" scope="col">{{ header.replace('_per_spotcell', '').replace('_', ' ') }}</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in rows">
                <td v-for="attr in headers" class="text-center">{{ isNaN(row[attr]) ? row[attr] : parseInt(row[attr])  }}</td>
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
                headers: [],
                rows: [],

            }
        },

        created() {
            this.csvToJSON(this.data);
            console.log(this.headers);
            console.log(this.rows);
        },

        methods: {
            csvToJSON: function(csv) {
                let lines = csv.split("\n");
                let result = [];
                let headers;
                headers = lines[0].split(",");
                this.headers = headers;

                for (let i = 1; i < lines.length; i++) {
                    let obj = {};

                    if(lines[i] === undefined || lines[i].trim() === "") {
                        continue;
                    }

                    let words = lines[i].split(",");
                    for(let j = 0; j < words.length; j++) {
                        obj[headers[j].trim()] = words[j];
                    }

                    result.push(obj);
                }
                this.rows = result;
            },
        }
    }
</script>
