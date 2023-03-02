<template>
    <div class="container m-2">
        <div class="text-center">
            <div>
                <div v-if="!checkFileType" class="text-info">{{ info }}</div>

                <label class="btn mb-0 toast-btn cols-sm-6 cols-md-3 cols-lg-2 cols-xl-1" :class="isRequired ? 'bg-gradient-info' : 'bg-gradient-secondary'">
                    <input type="file" v-on:change="handleFileUpload( $event )" style="display: none" :accept="fileTypes" />
                    {{ !checkFileType ? 'Select' : 'Change' }} file...
                </label>
            </div>
            <div v-if="file">
                <span :class="checkFileType ? 'text-success' : 'text-danger'">{{ file.name }} - {{ formatFileSize(file.size) }}</span>
                <br>
                <progress v-if="uploading" max="100" :value.prop="uploadPercentage"></progress>
                <br>
                <button v-if="showUploadButton && !uploading" v-on:click="submitFile()" class="btn bg-gradient-warning w-25 mb-0 toast-btn">
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
        url: {type: String, default: '/files'},
        fileTypes: {type: String, default: '*.*'},
        info: {type: String, default: 'Please select a file to be uploaded'},
        showUploadButton: {type: Boolean, default: true},
        isRequired: {type: Boolean, default: true},
    },

    data(){
        return {
            file_id: 0,
            file: '',
            uploading: false,
            uploadPercentage: 0
        }
    },

    computed: {
        checkFileType() {
            return (typeof this.file === 'object')
                && (this.fileTypes.includes('*.*') || this.fileTypes.toLowerCase().includes(this.file.name.split('.').pop()));
        }
    },

    methods: {

        handleFileUpload( event ){
            this.file = event.target.files[0];

            this.$emit('validated', this.checkFileType);
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
                ).then((response) => {
                    //console.log('FILE UPLOAD SUCCESS!! -->' + response.data);
                    this.file_id = Number(response.data);
                    this.uploading = false;

                    this.$emit('uploaded', this.file_id);
                })
                .catch(() => {
                    console.log('FILE UPLOAD FAILURE!!');
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
