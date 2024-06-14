<template>
    <div v-if="annotation !== undefined && loaded" class="d-xxl-flex">
        <div class="w-50 p-4">
            <span class="text-primary">Original annotation:</span> {{ annotation.originalName }} <br />
            <span class="text-primary">Modified annotation:</span> <input type="text" v-model="annotation.newName" @input="annotationNameChange" class="border border-1 rounded rounded-2 px-2" :class="annotationChanged ? 'text-warning border-warning' : ''"> <br />
            <span class="text-primary">Clusters:</span> <br />
            <table class="table w-80">
                <thead>
                    <tr>
                        <th class="p-2">
                            Original
                        </th>
                        <th class="p-2">
                            Modified
                        </th>
                    </tr>
                </thead>
                <tbody>

                        <tr v-for="cluster in clusters">
                            <td>
                                {{ cluster.originalName }}
                            </td>
                            <td>
                                <input type="text" v-model="cluster.newName" @input="clusterNameChange(cluster)" class="w-100 border border-1 rounded rounded-2 px-2" :class="cluster.changed ? 'text-warning border-warning' : ''">
                            </td>
                        </tr>

                </tbody>
            </table>
            <div v-if="hasChanged()" class="text-warning">
                Changes detected, please use the "Complete renaming" button above
            </div>
        </div>

        <div class="w-50">
            <data-grid :src="getJsonPath()" :scrolling-toggle="false" :allow-selection="false"></data-grid>
        </div>
    </div>
</template>

<script>
export default {
    name: 'stDiffRenameAnnotationsClusters',

    emits: ['changes'],

    props: {
        annotation: Object,
        sampleName: String,
        prefix: String,
        suffix: String,
        filePath: String,
    },

    data() {
        return {
            // originalName: this.annotation !== undefined ? this.annotation.originalName : '',
            // modifiedName: this.annotation !== undefined ? this.annotation.modifiedName : '',
            // newName: this.annotation !== undefined ? this.annotation.modifiedName : '',

            annotationChanged: false,

            clusters: this.annotation !== undefined ? this.annotation.clusters : [],

            loaded: false,
        }
    },

    watch: {

    },

    mounted() {
        for(let i = 0; i < this.clusters.length; i++) {
            this.clusters[i]['changed'] = false;
            this.clusters[i]['newName'] = this.clusters[i]['modifiedName'];
        }

        this.annotation['changed'] = false;
        this.annotation['newName'] = this.annotation.modifiedName;

        this.loaded = true;
    },

    methods: {

        hasChanged() {
            return this.annotationChanged || this.clusters.some(cluster => cluster.changed);
        },

        informChanges() {

            let hasChanged = this.hasChanged();

            //if(hasChanged && this.annotation.originalName === this.annotation.newName) this.annotation.newName += '_mod';

            this.$emit('changes', this.sampleName, this.annotation, hasChanged);
        },

        annotationNameChange() {
            this.annotation['changed'] = this.annotationChanged = this.annotation.modifiedName.trim() !== this.annotation.newName.trim();

            this.informChanges();
        },

        clusterNameChange(cluster) {
            cluster['changed'] = cluster.modifiedName.trim() !== cluster.newName.trim();

            this.informChanges();
        },

        getJsonPath() {

        /*let prefix = 'stclust_';
        let suffix = '_top_deg'*/

        // Split the path by '/'
        let parts = this.filePath.split('/');

        // Extract the filename (last part of the path)
        let filename = parts.pop();

        // Create the new filename with prefix and suffix
        let newFilename = `${this.prefix}${filename}${this.suffix}` + '.json';

        // Add the new filename back to the parts array
        parts.push(newFilename);

        // Join the parts array back into a single string with '/' delimiter
        let newPath = parts.join('/');

        return newPath;
        },

    },
}
</script>

<style>

</style>
