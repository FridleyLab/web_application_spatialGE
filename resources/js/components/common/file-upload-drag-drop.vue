<template>
    <div class="row text-center justify-content-center align-items-center w-100">


        <form id="drop-form" class="col-6 text-center bg-gray-400 rounded" style="height: 150px" @drop="handleFileDrop( $event )">
            <label class="pt-2 w-100 h-100 cursor-pointer text-info font-weight-bold">Drop your files here or <br /> click to select them!
            <input type="file" v-on:change="handleFileDrop( $event )" style="display: none" multiple  />
            </label>
        </form>

<!--        <div v-for="(file, key) in files"-->
<!--             v-bind:key="'file-'+key"-->
<!--             class="file-listing">-->
<!--            <img class="preview" v-bind:id="imgIdPrefix+parseInt( key )" :title="file.name" />-->
<!--            <div class="remove-container">-->
<!--                <a class="remove" v-on:click="removeFile( key )">Remove</a>-->
<!--            </div>-->
<!--        </div>-->

        <div class="d-flex mt-3 align-items-center justify-content-center">
            <div v-for="(file, key) in files"
                 v-bind:key="'file-'+key"
                 class="col-2 mx-1">
                <img class="img-thumbnail img-fluid" v-bind:id="imgIdPrefix+parseInt( key )" :title="file.name" />
                <div class="text-center">
                    <a class="btn btn-sm text-danger" v-on:click="removeFile( key )">Remove</a>
                </div>
            </div>
        </div>


        <div class="input-group input-group-outline w-30">
            <label class="form-label">Sample name (optional)</label>
            <input type="text" class="form-control" name="sample_name" v-model="sample_name">
        </div>


        <progress v-show="uploading" max="100" :value.prop="uploadPercentage"></progress>

        <div class="p-2 w-100 text-center">
            <button @click="importSample" class="btn bg-gradient-success w-25 mb-0 toast-btn" :disabled="!canStartImportProcess">
                Import sample
            </button>
        </div>





<!--        <a class="submit-button" v-on:click="submitFiles()" v-show="files.length > 0">Submit</a>-->
    </div>
</template>

<script>

export default {

    name: 'fileUploadDragDrop',

    props: {
        project: Object,
    },

    data(){
        return {
            dragAndDropCapable: false,
            files: [],
            uploadPercentage: 0,
            imgIdPrefix: 'drag-and-drop-preview-',

            knownFileTypes: 'tsv,csv,zip,gz,h5,txt,jpg,jpeg,png,json',

            uploading: false,

            sample_name: '',
        }
    },

    mounted(){
        /*
            Determine if drag and drop functionality is capable in the browser
        */
        this.dragAndDropCapable = this.determineDragAndDropCapable();

        /*
            If drag and drop capable, then we continue to bind events to our elements.
        */
        if( this.dragAndDropCapable ){
            this.bindEvents();
        }
    },

    computed: {
        canStartImportProcess() {
            return this.hasFileType('h5') && this.hasFileType('csv');
        },
    },

    methods: {
        bindEvents(){
            /*
                Listen to all the drag events and bind an event listener to each
                for the fileform.
            */
            ['drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach( function( evt ) {
                /*
                    For each event add an event listener that prevents the default action
                    (opening the file in the browser) and stop the propagation of the event (so
                    no other elements open the file in the browser)
                */
                document.getElementById('drop-form').addEventListener(evt, function(e){
                    e.preventDefault();
                    e.stopPropagation();
                }.bind(this), false);
            }.bind(this));
        },

        handleFileDrop( event ){
            let files = 'dataTransfer' in event ? event.dataTransfer.files : event.target.files;

            for( let i = 0; i < files.length; i++ ){
                if(this.files.length < 4)
                    this.files.push( files[i] );
            }

            this.getImagePreviews();
        },

        determineDragAndDropCapable(){
            /*
                Create a test element to see if certain events
                are present that let us do drag and drop.
            */
            const div = document.createElement('div');

            /*
                Check to see if the `draggable` event is in the element
                or the `ondragstart` and `ondrop` events are in the element. If
                they are, then we have what we need for dragging and dropping files.

                We also check to see if the window has `FormData` and `FileReader` objects
                present, so we can do our AJAX uploading
            */
            return ( ( 'draggable' in div )
                    || ( 'ondragstart' in div && 'ondrop' in div ) )
                && 'FormData' in window
                && 'FileReader' in window;
        },

        getFileExtension(fileName) {
            return fileName.split('.').pop();
        },

        hasFileType(type) {
            return this.files.filter(file => {return this.getFileExtension(file.name).toLowerCase() === type.toLowerCase()}).length;
        },

        getImagePreviews(){
            /*
                Iterate over all the files and generate an image preview for each one.
            */
            for( let i = 0; i < this.files.length; i++ ){
                /*
                    Ensure the file is an image file
                */
                if ( /\.(jpe?g|png|gif)$/i.test( this.files[i].name ) ) {
                    /*
                        Create a new FileReader object
                    */
                    let reader = new FileReader();

                    /*
                        Add an event listener for when the file has been loaded
                        to update the src on the file preview.
                    */
                    reader.addEventListener("load", function(){
                        document.getElementById(this.imgIdPrefix+parseInt(i)).src = reader.result;
                    }.bind(this), false);

                    /*
                        Read the data for the file in through the reader. When it has
                        been loaded, we listen to the event propagated and set the image
                        src to what was loaded from the reader.
                    */
                    reader.readAsDataURL( this.files[i] );
                }else{
                    const fileExtension = this.getFileExtension(this.files[i].name);
                    let img = '/images/icons/' + (this.knownFileTypes.toLowerCase().includes(fileExtension) ? fileExtension : 'other') + '.png';

                    /*
                        We do the next tick so the reference is bound and we can access it.
                    */
                    this.$nextTick(function(){
                        document.getElementById(this.imgIdPrefix+parseInt(i)).src = img;
                    });
                }
            }
        },

        removeFile( key ){
            this.files.splice( key, 1 );
            this.getImagePreviews();
        },

        importSample(){
            /*
                Initialize the form data
            */
            let formData = new FormData();

            /*
                Iterate over any file sent over appending the files
                to the form data.
            */
            for( let i = 0; i < this.files.length; i++ ){
                let file = this.files[i];

                formData.append('files[' + i + ']', file);
            }

            formData.append('project_id', this.project.id);
            formData.append('sample_name', this.sample_name.trim());

            this.uploading = true;

            /*
                Make the request to the POST /file-drag-drop URL
            */
            axios.post( '/samples',
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
                console.log('SUCCESS!!');
                console.log(response.data);
                location.reload();
            })
            .catch((error) => {
                console.log('FAILURE!!');
                this.uploading = false;
            });
        }
    }
}
</script>

<style scoped>


div.file-listing{
    width: 50px;
    margin: auto;
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

div.file-listing img{
    height: 100px;
    display: block;
}

div.remove-container{
    text-align: center;
}

div.remove-container a{
    color: red;
    cursor: pointer;
}

a.submit-button{
    display: block;
    margin: auto;
    text-align: center;
    width: 200px;
    padding: 10px;
    text-transform: uppercase;
    background-color: #CCC;
    color: white;
    font-weight: bold;
    margin-top: 20px;
}

progress{
    width: 400px;
    margin: auto;
    display: block;
    margin-top: 20px;
    margin-bottom: 20px;
}
</style>
