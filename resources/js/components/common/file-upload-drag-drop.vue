<template>
    <div class="text-center justify-content-center align-items-center w-100">


        <div class="justify-content-center mb-3" :title="tooltip">
            <form :id="'drop-form_' + code" class="text-center rounded" style="height: 150px" @drop="handleFileDrop( $event )">
                <label v-if="!file" class="pt-2 w-100 h-100 rounded cursor-pointer text-info border border-info border-1"> {{ caption }} <br />
                    <input type="file" v-on:change="handleFileDrop( $event )" style="display: none" />
                    <div class="icon icon-lg icon-shape bg-gradient-info shadow-dark">
                        <i class="material-icons opacity-10">add_circle_outline</i>
                    </div>
                    <div v-if="required" class="text-warning mt-1">Required</div>
                </label>
                <img v-if="file" class="img-thumbnail img-fluid h-100" ref="imgIcon" :title="file?.name" />
                <div v-if="file" class="text-center text-xs">
                    {{ file.name }}
                </div>
                <div v-if="file && !uploading" class="text-center">
                    <a class="btn btn-sm text-danger" v-on:click="removeFile()">Remove</a>
                </div>
                <div v-if="errorMessage.length" class="text-center text-danger text-xs">
                    {{ errorMessage }}
                </div>
<!--                <div class="mt-1"><i class="material-icons opacity-10 text-info">help</i></div>-->
                <div v-if="!file && !errorMessage.length">
                    Valid file types:
                    {{ this.acceptedFileTypes[this.code].join(' | ') }}
                </div>
            </form>

        </div>


    </div>
</template>

<script>

export default {

    name: 'fileUploadDragDrop',

    props: {
        project: Object,
        samples: Object,
        caption: {type: String, default: 'select or drop file'},
        required: {type: Boolean, default: false},
        code: String,
        tooltip: {type: String, default: ''},
        numberOfSamples: {type: Number, default: 0},
        excelMetadataUrl: {type: String, default: ''},
    },

    data(){
        return {
            dragAndDropCapable: false,
            file: null,
            uploadPercentage: 0,
            imgIdPrefix: 'drag-and-drop-preview-',

            knownFileTypes: 'tsv,csv,zip,gz,h5,txt,jpg,jpeg,png,json',

            uploading: false,

            sample_name: '',

            errorMessage: '',

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

        coordinatesColumnCount() {
            let platform_columns = {
                VISIUM: 6,
                GENERIC: 3,
            };

            //Error
            if(!(this.project.platform_name in platform_columns)) {
                return 99;
            }

            return platform_columns[this.project.platform_name];
        },

        fileExtension() {
            return this.file ?  this.file.name.split('.').pop() : '';
        },

        acceptedFileTypes() {
            if(this.project.platform_name === 'VISIUM') {
                return {
                    'expression': ['h5'],
                    'coordinates': ['csv'],
                    'image': ['png'],
                    'scale': ['json'],
                    'metadata': ['csv', 'tsv', 'txt', 'xls', 'xlsx']
                }
            }
            else if(this.project.platform_name === 'GENERIC') {
                return {
                    'expression': ['csv', 'tsv', 'txt'],
                    'coordinates': ['csv', 'tsv', 'txt'],
                    'image': ['png', 'jpg'],
                    'scale': ['json'],
                    'metadata': ['csv', 'tsv', 'txt', 'xls', 'xlsx']
                }
            }
        },

    },

    methods: {

        handleFileDrop( event ){

            if(this.uploading) return;

            this.file = 'dataTransfer' in event ? event.dataTransfer.files[0] : event.target.files[0];

            this.errorMessage = '';
            //TODO: after the first sample, all samples must match in format

            //Check the file type
            if(!(RegExp('\.(' + this.acceptedFileTypes[this.code].join('|') + ')$', 'i').test(this.file.name)))
            {
                this.errorMessage = 'File type must be: ' + this.acceptedFileTypes[this.code].join(' | ');
                this.file = null;
                return;
            }

            if(this.code === 'expression') {

                if(this.samples.length && this.samples[0].files.length) {

                    let thefile = this.file;

                    this.samples[0].files.map(
                        file => {
                            let parts = thefile.name.toLowerCase().split('.');
                            if(file.type === 'expressionFile' && file.extension.toLowerCase() !== parts[parts.length-1]) {
                                this.errorMessage = 'File type must be of type: ' + file.extension + ', as the other sample(s)!';
                                this.file = null;
                            }
                        });

                    if(this.errorMessage.length) return;
                }

                this.$emit('fileSelected', this.file);
            }
            else if(this.code === 'coordinates') {

                let reader = new FileReader();
                reader.addEventListener("load", (() => {
                    let contents = reader.result;
                    if(!this.checkCoordinatesData(contents) || this.errorMessage.length) {
                        this.file = null;
                    }
                    else
                        this.$emit('fileSelected', this.file);
                }).bind(this), false);
                reader.readAsText(this.file);
            }
            else if (this.code === 'image') {
                this.$emit('fileSelected', this.file);
            }
            else if (this.code === 'scale') {
                this.$emit('fileSelected', this.file);
            }
            else if (this.code === 'metadata') {
                this.processMetadata();
            }

            this.getImagePreviews();

        },


        getImagePreviews(){

            if ( /\.(jpe?g|png|gif|tiff)$/i.test( this.file.name ) ) {
                /*
                    Create a new FileReader object
                */
                let reader = new FileReader();

                /*
                    Add an event listener for when the file has been loaded
                    to update the src on the file preview.
                */
                reader.addEventListener("load", function(){
                    this.$refs.imgIcon.src = reader.result;
                    //document.getElementById('imgIcon').src = reader.result;
                }.bind(this), false);

                /*
                    Read the data for the file in through the reader. When it has
                    been loaded, we listen to the event propagated and set the image
                    src to what was loaded from the reader.
                */
                reader.readAsDataURL( this.file );
            }else{
                //const fileExtension = this.getFileExtension(this.file.name);
                let img = '/images/icons/' + (this.knownFileTypes.toLowerCase().includes(this.fileExtension) ? this.fileExtension : 'other') + '.png';

                /*
                    We do the next tick so the reference is bound and we can access it.
                */
                this.$nextTick(function(){
                    this.$refs.imgIcon.src = img;
                    //document.getElementById('imgIcon').src = img;
                });


                // if ( /\.(json|csv|tsv)$/i.test( this.file.name ) ) {
                //
                // }

            }
        },

        removeFile(){
            this.file = null;
            //this.getImagePreviews();

            this.$emit('fileRemoved', this.file);
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

        bindEvents() {
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
                document.getElementById('drop-form_' + this.code).addEventListener(evt, function(e){
                    e.preventDefault();
                    e.stopPropagation();
                }.bind(this), false);
            }.bind(this));
        },

        checkCoordinatesData(data) {

            const columnCount = this.coordinatesColumnCount;

            console.log(columnCount);

            let lines = data.split(/\r?\n|\r|\n/g);
            if((!lines.length || !lines[0].length || lines[0].split(/\t|,/g).length !== columnCount)) {
               this.errorMessage = 'File should be "tissue_positions.csv" from space ranger and have ' + columnCount + ' columns';
               return false;
            }

            let i = 1;
            lines.forEach((line) => {
                let values = line.split(/\t|,/g);

                if(values.length !== columnCount && (i < lines.length) && !this.errorMessage.length ) {
                    this.errorMessage = 'Line ' + i + ': number of columns is not ' + columnCount;
                    return false;
                }

                //if cols is 3, check [2] and [3], if 6 columns check 5 and 6
                /*if((Number(values[columnCount-2]) < 100 || Number(values[columnCount-1]) < 100) && (i < lines.length) && !this.errorMessage.length ) {
                    this.errorMessage = 'Line ' + i + ': coordinates too small';
                    return false;
                }*/

                i++;
            });

            return true;

        },

        checkMetadata(data) {
            let lines = data.split(/\r?\n|\r|\n/g);

            //check the metadata file to see if it has a heading line and a line for each sample
            if(!lines.length || lines.length < (this.numberOfSamples+1) || lines[0].split(/\t|,/g).length < 2) {
                this.errorMessage = 'The file should have ' + (this.numberOfSamples+1) + ' lines and at least 2 columns';
                return '';
            }


            let metadata = [];
            let headings = lines[0].split(/\t|,/g);

            for(let j = 1; j < headings.length; j++) {
                metadata[j-1] = {"name": headings[j], "values": {}};

                for(let i = 1; i < lines.length; i++) {
                    let values = lines[i].split(/\t|,/g);
                    if(lines[i].length && values.length === headings.length)
                        metadata[j-1].values[values[0]] = values[j];
                }
            }

            return metadata;
        },

        processMetadata() {
            if(RegExp('\.(xls|xlsx)$', 'i').test(this.file.name)) {

                let formData = new FormData();
                formData.append('metadata', this.file);

                axios.post(this.excelMetadataUrl, formData, {headers: {'Content-Type': 'multipart/form-data'}}
                ).then((response) => {
                        let metadata = this.checkMetadata(response.data);
                        if (typeof metadata === 'string' || this.errorMessage.length)
                            this.file = null;
                        else
                            this.$emit('fileSelected', this.file, metadata);
                    }
                ).catch((error) => {
                    console.log('Error while processing Excel file with metadata: ', error.message);
                });

            }
            else {
                let reader = new FileReader();
                reader.addEventListener("load", (() => {
                    let contents = reader.result;
                    let metadata = this.checkMetadata(contents);
                    if (typeof metadata === 'string' || this.errorMessage.length)
                        this.file = null;
                    else
                        this.$emit('fileSelected', this.file, metadata);
                }).bind(this), false);
                reader.readAsText(this.file);
            }
        },
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
