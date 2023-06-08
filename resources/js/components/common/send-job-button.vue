<template>
<!--        <input v-if="!processing" type="button" class="btn btn-outline-success" :class="(processing || disabled) ? 'disabled' : ''" @click="sendStartSignal" :value="label" />-->
        <input v-if="startButtonVisible && !processing" type="button" class="btn btn-lg mt-2 mb-0" :class="((processing || disabled) ? 'disabled' : '') + (!secondary ? ' bg-gradient-info' : ' btn-sm btn-outline-info')" @click="sendStartSignal" :value="label" />
        <div v-if="processing" :class="processing ? 'popup-center' : ''" class="border border-1 rounded rounded-2 bg-gray-400 p-4">
            <div class="text-info text-bold">
                <div class="text-center">
                    Process sent to server
                </div>
                <div class="py-2 justify-content-center align-items-center text-center">
                    <span class="text-lg me-2">Send email when completed?</span>
                    <button type="button" @click="setEmailNofitication" class="btn btn-sm text-sm" :class="sendEmail ? 'btn-outline-success' : 'btn-outline-secondary'">
                        {{ sendEmail ? 'YES' : 'NO' }}
                    </button>
                </div>
                <div v-if="queuePosition>0" class="text-center">
                    Queue position: <span class="text-warning text-lg">{{ queuePosition }}</span> <span v-if="queuePosition === 1" class="text-success text-lg">Processing...</span>
                </div>
                <div class="my-3 text-center">
                    <img src="/images/loading-circular.gif" class="" style="width:100px" />
                </div>
            </div>

        </div>
</template>

<script>
export default {
    name: 'sendJobButton',

    emits: ['started', 'ongoing', 'completed'],

    props: {

        //classes: {type: String, default: ''},
        label: String,
        projectId: Number,
        jobName: String,
        project: {type: Object, default: null},
        disabled: {type: Boolean, default: false},
        secondary: {type: Boolean, default: false},

        reload: {type: Boolean, default: false}
    },

    data() {
        return {
            queuePosition: -1,
            checkQueueIntervalId: 0,
            processing: false,
            sendEmail: 0,

            startButtonVisible: false
        }
    },

    watch: {
        queuePosition: {
            handler: function (newValue, oldValue) {

                console.log('**', oldValue, newValue);

                if(oldValue < 0) return;

                //console.log('Queue position ' + this.jobName + ': ' + this.queuePosition);

                if(!this.queuePosition) {
                    clearInterval(this.checkQueueIntervalId);

                    //reload the page if necessary
                    if(this.reload) window.document.location.href = window.document.location.href;

                    this.processing = false;
                    this.updateProjectParameters();
                }

                if(this.queuePosition !== null && this.queuePosition>0)
                    this.processing = true;
            },
            immediate: true
        }
    },

    mounted() {
        //this.updateJobPosition();
        this.setIntervalQueue();
    },

    methods: {
        sendStartSignal: function() {
            this.sendEmail = 0;
            this.setIntervalQueue();
            this.processing = true;
            this.$emit('started');
        },


        setIntervalQueue: function() {
            //Clear any previously running interval
            if(this.checkQueueIntervalId) clearInterval(this.checkQueueIntervalId);
            this.checkQueueIntervalId = 0;
            //Create the interval to check queue position
            this.checkQueueIntervalId = setInterval(async () => {
                this.queuePosition =  await this.$getJobPositionInQueue(this.projectId, this.jobName);
                console.log('ASYNC --', this.jobName, this.queuePosition);
                if(!this.queuePosition) {
                    this.startButtonVisible = true;
                    clearInterval(this.checkQueueIntervalId);
                }
                else {
                    this.$emit('ongoing');
                    this.processing = true;
                }

            }, 1300);
        },

        setEmailNofitication: _.debounce(function () {
            this.sendEmail = this.sendEmail ? 0 : 1;
            console.log('email: ' + this.sendEmail);
            axios.get('/projects/' + this.projectId + '/set-job-email-notification', {params: {'command': this.jobName, 'sendemail': this.sendEmail}})
                .then((response) => {
                    console.log(response.data);
                })
                .catch((error) => console.log(error));
        }, 100),

        updateProjectParameters: function() {
            if(this.project === null) {
                this.$emit('completed');
            }
            else {
                axios.get('/projects/' + this.project.id + '/get-project-parameters')
                    .then((response) => {
                        this.project.project_parameters = response.data;
                        this.$emit('completed');
                    })
                    .catch((error) => console.log(error));
            }
        }
    },
}
</script>
