<template>
    <div v-if="samples.length" class="p-3 text-end">
        <input v-if="!changingStep" type="button" class="btn btn-outline-success" :class="nextStepCssClasses" @click="nextStep" :value="nextStepLabel" />
        <div v-if="changingStep">
            <div class="text-info text-bold">
                The [Data import] job has been submitted. You will get an email notification when completed. <br />
                You can close this window or wait for it to reload when completed.<br />
            </div>
            <div v-if="jobPositionInQueue<=1">The job is being executed</div>
            <div v-if="jobPositionInQueue>1">
                The job position in the queue is: {{jobPositionInQueue}}
            </div>
        </div>

        <!--                        <img v-if="changingStep" src="/images/loading-circular.gif" class="me-6" style="width:100px" />-->

        <!--                        <input v-if="changingStep" type="button" class="btn btn-outline-warning me-2" @click="nextStep" value="Finished importing data, proceed" />-->
        <!--                        <input v-if="changingStep" type="button" class="btn btn-outline-danger" @click="changingStep = false" value="Cancel" />-->

    </div>
</template>

<script>
export default {
    name: 'sendJobButton',

    props: {
        processing: Boolean,
        classes: {type: String, default: ''},
        label: String,
        queuePosition: Number,
    }
}
</script>
