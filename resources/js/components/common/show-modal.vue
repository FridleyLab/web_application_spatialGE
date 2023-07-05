<template>
        <input v-if="!visible" type="button" class="btn btn-lg mt-2 mb-0" @click="showContent" value="help" />
        <div v-if="visible" :class="visible ? 'popup-center' : ''" class="border border-1 rounded rounded-2 bg-gray-400 p-4">
            <div class="text-info text-bold">
                <div class="text-center">
                    Heading
                </div>
                <div class="py-2 justify-content-center align-items-center text-center">
                    contents
                </div>
                <div>
                    <input type="button" class="btn btn-lg mt-2 mb-0" @click="hideContent" value="Hide" />
                </div>
            </div>
        </div>
</template>

<script>
export default {
    name: 'showModal',

    emits: ['showmodal', 'hidemodal'],

    props: {

    },

    data() {
        return {
            visible: false,
        }
    },

    watch: {

    },

    mounted() {

    },

    methods: {
        showContent() {
            this.visible = true;
            this.$emit('showmodal');
        },

        hideContent() {
            this.visible = false;
            this.$emit('hidemodal');
        },
    },
}
</script>
