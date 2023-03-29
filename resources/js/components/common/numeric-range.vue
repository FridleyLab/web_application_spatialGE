<template>
    <div>
        <div v-if="title.length" class="d-flex mb-4" :class="titleClass">
            {{ title }}
        </div>
        <div class="d-flex">
            <div :class="showPercentages ? 'w-15' : 'w-10'">
                <input class="text-end border border-info border-2 rounded px-1" :class="showPercentages ? 'w-65' : 'w-100'" type="text" v-model="value[0]">
                <span v-if="showPercentages" class="text-info ms-2 w-35">{{ Math.round(value[0]/max*100) }}%</span>
            </div>
            <div class="ps-3 pe-3 pt-3" :class="showPercentages ? 'w-70' : 'w-80'">
                <Slider :min="min" :max="max" :step="step" v-model="value" showTooltip="drag" />
            </div>
            <div :class="showPercentages ? 'w-15' : 'w-10'">
                <span v-if="showPercentages" class="text-info me-2 w-35">{{ Math.round(value[1]/max*100) }}%</span>
                <input class="text-end border border-info border-2 rounded px-1" :class="showPercentages ? 'w-65' : 'w-100'" type="text" v-model="value[1]">
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
            titleClass: {type: String, default: ''},
            min: {type: Number, default: 0},
            max: {type: Number, default: 100},
            step: {type: Number, default: 1},
            showPercentages: {type: Boolean, default: false},
        },

        data() {
            return {

                value: [this.min, this.max],

                // minValue: Number(this.startDefault),
                // maxValue: Number(this.endDefault),

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
<style>
:root {
    --slider-connect-bg: #3B82F6;
    --slider-tooltip-bg: #3B82F6;
    --slider-handle-ring-color: #3B82F630;
}
</style>
