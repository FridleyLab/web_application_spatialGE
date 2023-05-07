<template>
        <input v-if="!processing" type="button" class="btn btn-outline-success" :class="processing ? 'disabled' : ''" @click="sendStartSignal" :value="label" />
        <div v-if="processing">
            <div class="text-info text-bold">
                Process sent to server<br />
                Queue position: <span class="text-warning text-lg">{{ queuePosition }}</span> <span v-if="queuePosition === 1" class="text-success text-lg">Processing...</span> <br />
                [] email me when completed<br />
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
    },

    data() {
        return {
            queuePosition: -1,
            checkQueueIntervalId: 0,
            processing: false,
        }
    },

    watch: {
        queuePosition: {
            handler: function (newValue, oldValue) {

                if(oldValue < 0) return;

                console.log('Queue position ' + this.jobName + ': ' + this.queuePosition);

                if(!this.queuePosition) {
                    clearInterval(this.checkQueueIntervalId);
                    this.processing = false;
                    this.$emit('completed')
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

            this.checkQueueIntervalId = setInterval(async () => {this.queuePosition =  await this.$getJobPositionInQueue(this.projectId, this.jobName);}, 1800);
            console.log('Interval set: ' + this.jobName);
        },
    },
}
</script>
