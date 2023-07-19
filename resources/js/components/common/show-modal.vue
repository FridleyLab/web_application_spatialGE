<template>
    <a href="#" v-on:click.stop.prevent="showContent" class="ms-1">
        <i class="material-icons text-secondary fs-5 opacity-20">help</i>
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
        showContent(event) {
            event.stopPropagation();
            this.emitter.emit('show-tooltip', this.tag);
        },
    },
}
</script>
