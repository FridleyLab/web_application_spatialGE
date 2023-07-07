<template>
        <div v-if="visible" :class="visible ? 'popup-center' : ''" class="border border-1 rounded rounded-xl p-4 z-index-0 bg-gradient-info max-width-500">
            <div class="">
<!--                <div class="text-center text-white">-->
<!--                    Heading-->
<!--                </div>-->
                <div class="py-2 justify-content-center align-items-center text-center text-white">
                    {{ toolTip() }}
                </div>
                <div>
                    <input type="button" class="btn btn-sm btn-info mt-2 mb-0 text-white float-end" @click="hideContent" value="close" />
                </div>
            </div>
        </div>
</template>

<script>

export default {
    name: 'showModalContent',

    props: {

    },

    data() {
        return {
            visible: false,

            tag: '',
            tool_tips: {
                //STgradient
                "stgradient_samples": "Select the samples to run STgradient",
                "stgradient_genes": "Maximum number of genes per sample to test for spatial gradients. The genes to be tested are selected based on standard deviation",

            },
        }
    },

    mounted() {
        this.emitter.on("show-tooltip", tag => {
            this.showContent(tag);
        });
    },

    methods: {
        showContent(tag) {
            this.tag = tag;
            this.visible = true;
            document.getElementById('_body').classList.add('disabled-clicks');
        },

        hideContent() {
            this.visible = false;
            document.getElementById('_body').classList.remove('disabled-clicks');
        },

        toolTip() {
            return this.tag in this.tool_tips ? this.tool_tips[this.tag] : '';
        }
    },
}
</script>
