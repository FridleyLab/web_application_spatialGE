<template>

    <div class="w-xxl-90 w-95 container-fluid">
        <div v-if="data !== null">
            <div class="container">
                <div class="row row-cols-4">
                    <!-- <div v-for="file in process.data.downloadable" class="col my-2"> -->
                    <div v-for="file in data.files" class="col my-2">
                        <a class="border rounded rounded-2 border-1 px-2 py-1" :title="file" :href="'/admin-download-file/' + data.projectId + '/' + file" download>{{ getDisplayName(file) }}</a>
                    </div>
                </div>
            </div>
            <pre>
                {{ data.output }}
            </pre>
        </div>
        <div v-if="loading">
            Loading data... please wait!
        </div>
    </div>

</template>
<script>

//Style to apply to the DataGrid
import 'devextreme/dist/css/dx.light.css';

export default {
    name: 'showStatsProcessInfo',

    props: {
        processId: Number,
    },

    data() {
        return {
            loading: false,
            data: null,
        };
    },

    mounted() {
        console.log(this.processId);
        this.getData();
    },

    methods: {

        getData() {
            this.loading = true;
            axios.get('/show-stats-process-info/' + this.processId)
                .then(response => {
                    console.log(response.data);
                    this.data = response.data;
                    this.loading = false;
                })
                .catch(error => {
                    console.log(error);
                    this.loading = false;
                });
        },

        getDisplayName(fileName) {
            let displayName = fileName;
            if(fileName.length > 30) {
                let parts = fileName.split('.');
                displayName = parts[0].substring(0, 25) + '...';
                if(parts.length) {
                    displayName += parts.at(-1);
                }
            }
            return displayName;
        }

    },
};
</script>

