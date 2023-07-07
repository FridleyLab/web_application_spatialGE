<template>
    <button type="button" class="btn btn-lg" @click="showContent">
        <i class="material-icons text-info opacity-20">help</i>
    </button>
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
