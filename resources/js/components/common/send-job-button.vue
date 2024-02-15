<template>
        <input v-if="startButtonVisible && !processing" type="button" class="btn btn-lg mt-2 mb-0" :class="((processing || disabled || otherJobsInQueue) ? 'disabled' : '') + (!secondary ? ' bg-gradient-info' : ' btn-sm btn-outline-info')" @click="sendStartSignal" :value="label" />
        <div v-if="otherJobsInQueue && startButtonVisible" class="mt-2 text-warning text-xxs">Running other process <show-modal tag="running_other_process"></show-modal></div>
        <div v-if="processing" :class="processing ? 'popup-center' : ''" class="border border-1 rounded rounded-2 bg-gray-400 p-4">
            <div class="text-info text-bold">
                <div class="text-center">
                    Process sent to server
                </div>
                <div class="py-2 justify-content-center align-items-center text-center">
                    <span class="text-lg me-2">Send email when completed?</span>
                    <div>
                        <label class="me-3">
                            <input type="radio" :value="0" v-model="sendEmail" @click="setEmailNofitication"> No
                        </label>
                        <label>
                            <input type="radio" :value="1" v-model="sendEmail" @click="setEmailNofitication"> Yes
                        </label>
                    </div>
                </div>
                <div v-if="queuePosition>0" class="text-center">
                    Queue position: <span class="text-warning text-lg">{{ queuePosition }}</span> <span v-if="queuePosition === 1" class="text-success text-lg">Processing...</span>
                </div>
                <div class="my-3 text-center">
                    <img src="/images/loading-circular.gif" class="" style="width:100px" />
                </div>

                <div class="mt-3 text-center" v-if="queuePosition >= 1">
                    <div v-if="!cancellingJob" class="">
                        <button class="btn btn-sm btn-danger mb-1" @click="cancellingJob = true">Cancel process</button>
<!--                        <div class="text-info text-xxs">Only if not started yet</div>-->
                    </div>
                    <div v-if="cancellingJob" class="">
                        <div class="text-danger">Are you sure?</div>
                        <div>
                            <button type="button" class="btn btn-sm btn-danger" @click="cancelJobInQueue">Yes</button>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" @click="cancellingJob = false">No</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="jobParameters" class="mt-2">
            <!-- <pre>
                {{ JSON.stringify(jobParameters, null, 2) }}
            </pre> -->
            <a role="button" class="text-info text-sm" @click="downloadJobParameters">Download parameters</a>
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
        project: Object,
        disabled: {type: Boolean, default: false},
        secondary: {type: Boolean, default: false},

        reload: {type: Boolean, default: false},
    },

    data() {
        return {
            queuePosition: -1,
            checkQueueIntervalId: 0,
            processing: false,
            sendEmail: 0,

            startButtonVisible: false,

            initialCheck: true,
            cancellingJob: false,

            otherJobsInQueue: 0,

            jobParameters: 0,
        }
    },

    watch: {
        queuePosition: {
            handler: async function (newValue, oldValue) {

                //console.log('**', oldValue, newValue);

                if(oldValue < 0) return;

                //console.log('Queue position ' + this.jobName + ': ' + this.queuePosition);

                if(!this.queuePosition) {
                    clearInterval(this.checkQueueIntervalId);

                    //reload the page if necessary
                    if(this.reload) window.document.location.href = window.document.location.href;

                    this.processing = false;
                    if(this.project === null) {
                        this.$emit('completed');
                    }
                    else {
                        console.log('job-button: before project parameters');
                        this.project.project_parameters = await this.$getProjectParameters(this.projectId);
                        console.log('job-button: after project parameters');
                        this.$emit('completed');
                    }

                    await this.getJobParameters();

                }

                if(this.queuePosition !== null && this.queuePosition>0)
                    this.processing = true;
            },
            immediate: true
        }
    },

    async mounted() {

        this.sendEmail = this.project.project_parameters.hasOwnProperty('job.' + this.jobName + '.email') ? parseInt(this.project.project_parameters['job.' + this.jobName + '.email']) : 0;
        //console.log(this.sendEmail);

        //this.updateJobPosition();
        this.setIntervalQueue();

        this.jobsInQueue();

        await this.getJobParameters();
    },

    methods: {
        sendStartSignal: function() {
            this.cancellingJob = false;
            this.sendEmail = 0;
            this.setIntervalQueue();
            this.processing = true;
            this.$emit('started');
        },


        getIntervalWaitTime() {
            if(!this.initialCheck) return 1100;
            this.initialCheck = false;
            return 500;
        },

        setIntervalQueue: function() {
            //Clear any previously running interval
            if(this.checkQueueIntervalId) clearInterval(this.checkQueueIntervalId);
            this.checkQueueIntervalId = 0;
            //Create the interval to check queue position
            this.checkQueueIntervalId = setInterval(async () => {
                this.queuePosition =  await this.$getJobPositionInQueue(this.projectId, this.jobName);
                if(!this.queuePosition) {
                    this.startButtonVisible = true;
                    clearInterval(this.checkQueueIntervalId);
                }
                else {
                    this.$emit('ongoing');
                    this.processing = true;
                }

            }, this.getIntervalWaitTime());
        },

        setEmailNofitication: _.debounce(function () {
            axios.get('/projects/' + this.projectId + '/set-job-email-notification', {params: {'command': this.jobName, 'sendemail': this.sendEmail}})
                .then((response) => {
                    //console.log(response.data);
                })
                .catch((error) => console.log(error));
        }, 100),

        cancelJobInQueue() {
            axios.get('/projects/' + this.projectId + '/cancel-job-in-queue', {params: {'command': this.jobName}})
                .then((response) => {
                    console.log(response.data);
                })
                .catch((error) => console.log(error));
        },

        jobsInQueue() {
            axios.get('/projects/' + this.projectId + '/get-jobs-in-queue', {params: {'command': this.jobName}})
                .then((response) => {
                    this.otherJobsInQueue = response.data;
                    //console.log(response.data);
                })
                .catch((error) => console.log(error));
        },

        async getJobParameters() {
            this.jobParameters = await this.$getJobParameters(this.projectId, this.jobName);
            console.log(JSON.stringify(this.jobParameters, null, 2));
        },

        downloadJobParameters() {
            const text = JSON.stringify(this.jobParameters, null, 4);
            const blob = new Blob([text], { type: "text/plain" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = this.jobName + ".json";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    },
}
</script>
