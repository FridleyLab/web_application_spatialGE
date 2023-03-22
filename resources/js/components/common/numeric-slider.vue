<template>
    <div class="mt-2">
        <label for="customRange2" class="form-label">{{ label }}:</label> <input type="number" v-model="value" @input="$emit('update:modelValue', Number($event.target.value))" class="text-end text-sm border border-1 rounded w-25 w-md-35 w-xxl-15">
        <input type="range" :min="min" :max="max" :step="step" class="form-range" v-model="value" @input="$emit('update:modelValue', Number($event.target.value))">
    </div>
</template>
<script>

    export default {
        name: 'numericSlider',

        emits: ['update:modelValue'],

        props: {
            min: {type: Number, default: 0},
            max: {type: Number, default: 100},
            cap: {type: Number, default: 100},
            step: {type: Number, default: 1},
            // default: {type: Number, default: 0},
            label: {type: String, default: 'Range'},
            modelValue: {type: Number, default: 0},
        },

        data() {
            return {
                _value: this.modelValue,
            }
        },

        computed: {
            value: {
                get() {
                    return this._value;
                },
                set(newValue) {
                    let _val = newValue > this.cap ? this.cap : newValue;
                    this._value = _val;
                    return _val;
                }
            }
        },

        watch: {
            modelValue(oldValue, newValue) {
                console.log('<<>>' + this.modelValue);
                this.value = this.modelValue;
            }
        },
    }

</script>
