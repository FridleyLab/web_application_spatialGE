<template>
        <input v-if="!processing" type="button" class="btn btn-outline-success" :class="(processing || disabled) ? 'disabled' : ''" @click="sendStartSignal" :value="label" />
        <div v-if="processing">
            <div class="text-info text-bold">
                <div>
                    Process sent to server
                </div>
                <div class="py-2 justify-content-center align-items-center">
                    Send email when completed?
                    <button type="button" @click="setEmailNofitication" class="btn btn-sm" :class="sendEmail ? 'btn-outline-success' : 'btn-outline-secondary'">
                        {{ sendEmail ? 'YES' : 'NO' }}
                    </button>
                </div>
                <div v-if="queuePosition>0">
                    Queue position: <span class="text-warning text-lg">{{ queuePosition }}</span> <span v-if="queuePosition === 1" class="text-success text-lg">Processing...</span>
                </div>
            </div>
            <div class="my-3">
                <img src="/images/loading-circular.gif" class="me-6" style="width:100px" />
            </div>
        </div>
</template>

<script>
export default {
    name: 'sendJobButton',

    emits: ['started', 'completed'],

    props: {

        //classes: {type: String, default: ''},
        label: String,
        projectId: Number,
        jobName: String,
        project: {type: Object, default: null},
        disabled: {type: Boolean, default: false},
    },

    data() {
        return {
            queuePosition: -1,
            checkQueueIntervalId: 0,
            processing: false,
            sendEmail: 0,
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
            this.setIntervalQueue();
            this.processing = true;
            this.$emit('started');
        },


        setIntervalQueue: function() {
            //Clear any previously running interval
            if(this.checkQueueIntervalId) clearInterval(this.checkQueueIntervalId);
            this.checkQueueIntervalId = 0;
            //Create the interval to check queue position
            this.checkQueueIntervalId = setInterval(async () => {this.queuePosition =  await this.$getJobPositionInQueue(this.projectId, this.jobName); console.log('ASYNC --', this.jobName, this.queuePosition); if(!this.queuePosition) clearInterval(this.checkQueueIntervalId);}, 1800);
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
