<template>
    <div>
        <div v-if="title.length" class="d-flex">
            <span class="text-bolder">{{ title }}</span>
            <div v-if="false" class="form-check form-switch ms-2 mt-1">
                <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" v-model="enabled" @click="toggle" />
            </div>
        </div>
        <div v-if="enabled" class="mt-2">
            <label class="form-label">{{ startLabel }}:</label> <input type="number" v-model="minValue" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15">
            <input type="range" :min="startMin" :max="startMax" :step="startStep" class="form-range" v-model="minValue">
        </div>
        <div v-if="enabled" class="mt-2">
            <label class="form-label">{{ endLabel }}:</label> <input type="number" v-model="maxValue" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15">
            <input type="range" :min="endMin" :max="endMax" :step="endStep" class="form-range" v-model="maxValue">
        </div>
    </div>
</template>
<script>

    import NumericSlider from "./numeric-slider.vue";

    export default {
        name: 'numericRange',
        emits: ['updated'],

        props: {
            title: {type: String, default: 'Range'},

            startMin: {type: Number, default: 0},
            startMax: {type: Number, default: 100},
            startStep: {type: Number, default: 1},
            startDefault: {type: Number, default: 0},
            startLabel: {type: String, default: 'min'},

            endMin: {type: Number, default: 0},
            endMax: {type: Number, default: 100},
            endStep: {type: Number, default: 1},
            endDefault: {type: Number, default: 0},
            endLabel: {type: String, default: 'max'},
        },

        data() {
            return {
                minValue: Number(this.startDefault),
                maxValue: Number(this.endDefault),

                enabled: true,
            }
        },

        watch: {
            minValue(newValue, oldValue) {
                this.minValue = newValue = Number(newValue);
                if(newValue > this.maxValue)
                    this.minValue = (this.maxValue - 500) > 0 ? this.maxValue - 500 : 0;

                this.$emit('updated', this.minValue, this.maxValue);
            },
            maxValue(newValue, oldValue) {
                this.maxValue = newValue = Number(newValue);
                if(newValue < this.minValue)
                    this.maxValue = this.minValue + 500;

                this.$emit('updated', this.minValue, this.maxValue);
            }
        },

        methods: {
            toggle(e) {
                console.log('chk', e.target.checked);
            }
        }

    }

</script>
