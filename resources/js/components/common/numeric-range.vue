<template>
    <div>
        <div v-if="title.length" class="d-flex mb-4">
            {{ title }}
        </div>
        <div class="d-flex">
            <div class="w-10">
                <input class="w-100 text-end border border-1 rounded px-1" type="text" v-model="value[0]">
            </div>
            <div class="w-80 ps-3 pe-4 pt-3">
                <Slider :min="min" :max="max" :step="step" v-model="value" />
            </div>
            <div class="w-10">
                <input class="w-100 text-end border border-1 rounded px-1" type="text" v-model="value[1]">
            </div>
        </div>
    </div>
</template>

<script>
import Slider from '@vueform/slider'
    export default {
        components: {
            Slider,
        },
        name: 'numericRange',
        emits: ['updated'],

        props: {
            title: {type: String, default: ''},
            min: {type: Number, default: 0},
            max: {type: Number, default: 100},
            step: {type: Number, default: 1},
        },

        data() {
            return {

                value: [this.min, this.max],

                minValue: Number(this.startDefault),
                maxValue: Number(this.endDefault),

                enabled: true,
            }
        },

        watch: {
            value(newValue, oldValue) {
                this.$emit('updated', this.value[0], this.value[1]);
            },
        },

    }

</script>

<style src="@vueform/slider/themes/default.css"></style>
