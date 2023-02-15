<template>
    <div v-if="message.length" v-html="message"></div>
</template>
<script>
    export default {
        name: "errorMessage",

        props: {
            message: String,
        }
    }
</script>
