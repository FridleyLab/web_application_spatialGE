<template>
    <div v-if="message.length" v-html="message" :class="'text-' + role"></div>
</template>
<script>
    export default {
        name: "showMessage",

        props: {
            message: String,
            role: {type: String, default: 'info'}
        },

        data() {
            return {

            }
        }

    }
</script>
