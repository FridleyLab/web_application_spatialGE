<template>
<!--    <div class="hover-text text-info">hover me-->
<!--        <span class="tooltip-text" id="top-tt">{{ toolTip() }}</span>-->
<!--    </div>-->
    <span class="hovertext" data-hover="Hello, this is the tooltip">
        Try hover over me
    </span>
</template>

<script>
export default {
    name: 'toolTip',

    props: {
        tag: String,
    },

    data() {
        return {
            tool_tips: {
                //STgradient
                "stgradient_samples": "Select the samples to run STgradient",
            },
        }
    },

    watch: {

    },

    mounted() {

    },

    methods: {
        toolTip() {
            return this.tag in this.tool_tips ? this.tool_tips[this.tag] : '';
        }
    },
}
</script>

<style>
.hovertext {
    position: relative;
    border-bottom: 1px dotted black;
}

.hovertext:before {
    content: attr(data-hover);
    visibility: hidden;
    opacity: 0;
    width: 140px;
    background-color: black;
    color: #fff;
    text-align: center;
    border-radius: 5px;
    padding: 5px 0;
    transition: opacity 1s ease-in-out;

    position: absolute;
    z-index: 10;
    left: 0;
    top: 110%;
}

.hovertext:hover:before {
    opacity: 1;
    visibility: visible;
}
</style>

<!--<style>-->
<!--.tooltip-text {-->
<!--    visibility: hidden;-->
<!--    position: absolute;-->
<!--    z-index: 1;-->
<!--    //width: 100px;-->
<!--    color: white;-->
<!--    font-size: 12px;-->
<!--    background-color: #192733;-->
<!--    border-radius: 10px;-->
<!--    padding: 10px 15px 10px 15px;-->
<!--}-->

<!--.tooltip-text::before {-->
<!--    content: "";-->
<!--    position: absolute;-->
<!--    transform: rotate(45deg);-->
<!--    background-color: #192733;-->
<!--    padding: 5px;-->
<!--    z-index: 1;-->
<!--}-->

<!--.hover-text:hover .tooltip-text {-->
<!--    visibility: visible;-->
<!--}-->

<!--#top-tt {-->
<!--    top: -40px;-->
<!--    left: -50%;-->
<!--}-->

<!--#top-tt::before {-->
<!--    top: 80%;-->
<!--    left: 45%;-->
<!--}-->

<!--#bottom {-->
<!--    top: 25px;-->
<!--    left: -50%;-->
<!--}-->

<!--#bottom::before {-->
<!--    top: -5%;-->
<!--    left: 45%;-->
<!--}-->

<!--#left {-->
<!--    top: -8px;-->
<!--    right: 120%;-->
<!--}-->

<!--#left::before {-->
<!--    top: 35%;-->
<!--    left: 94%;-->
<!--}-->

<!--#right {-->
<!--    top: -8px;-->
<!--    left: 120%;-->
<!--}-->

<!--#right::before {-->
<!--    top: 35%;-->
<!--    left: -2%;-->
<!--}-->

<!--.hover-text {-->
<!--    position: relative;-->
<!--    display: block;-->
<!--    margin: 40px;-->
<!--    font-family: Arial;-->
<!--    text-align: center;-->
<!--}-->
<!--</style>-->
