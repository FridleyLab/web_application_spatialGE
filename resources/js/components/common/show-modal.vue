<template>
    <a href="#" @click="showContent" class="ms-1">
        <i class="material-icons fs-5 text-info opacity-20">help</i>
    </a>
</template>

<script>

export default {
    name: 'showModal',

    props: {
        tag: String,
    },

    data() {
        return {
        }
    },

    methods: {
        showContent() {
            this.emitter.emit('show-tooltip', this.tag);
        },
    },
}
</script>
