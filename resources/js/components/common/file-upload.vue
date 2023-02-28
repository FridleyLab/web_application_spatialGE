<template>
    <div class="container m-2">
        <div class="text-center">
            <label class="btn bg-gradient-info w-50 mb-0 toast-btn">
                <input type="file" v-on:change="handleFileUpload( $event )" style="display: none" />
                Select file...
            </label>
            <div v-if="file">
                {{ file.name }} - {{ formatFileSize(file.size) }}
                <br>
                <progress v-if="uploading" max="100" :value.prop="uploadPercentage"></progress>
                <br>
                <button v-if="!uploading" v-on:click="submitFile()" class="btn bg-gradient-warning w-25 mb-0 toast-btn">
                    Upload
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {

    name: 'fileUpload',

    props: {
        url: {type: String, default: '/file-upload'}
    },

    data(){
        return {
            file: '',
            uploading: false,
            uploadPercentage: 0
        }
    },

    methods: {
        handleFileUpload( event ){
            this.file = event.target.files[0];
        },

        submitFile(){
            /*
                Initialize the form data
            */
            let formData = new FormData();

            /*
                Add the form data we need to submit
            */
            formData.append('file', this.file);

            this.uploading = true;

            /*
                Make the request to the POST /single-file URL
            */
            axios.post(this.url,
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: function( progressEvent ) {
                        this.uploadPercentage = parseInt( Math.round( ( progressEvent.loaded / progressEvent.total ) * 100 ) );
                    }.bind(this)
                }
                ).then(() => {
                    console.log('SUCCESS!!');
                    this.uploading = false;
                })
                .catch(() => {
                    console.log('FAILURE!!');
                    this.uploading = false;
                });
        },

        formatFileSize (bytes) {
            const sufixes = ['B', 'kB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sufixes[i]}`;
        },
    }
}
</script>
