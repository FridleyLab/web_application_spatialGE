<template>
    <div id="infotext">
        <a :href="url" class="text-info" target="_blank">{{ label }}</a>
    </div>
</template>
<script>
    export default {
        name: "showVignette",

        props: {
            url: String,
            label: {type: String, default: 'Documentation'}
        },

        data() {
            return {

            }
        }

    }
</script>

<style>
#infotext {
    animation: infoanimation 1.5s;
}

@keyframes infoanimation {
    50% {font-size: 20px;}
}
</style>
