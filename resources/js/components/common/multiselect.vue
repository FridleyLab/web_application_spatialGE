<template>
    <div>
        <Multiselect
            v-model="value"
            :options="options"
            :searchable="true"
            @change="(value, select) => $emit('change',value,select)"
        />
    </div>
</template>

<script>
import Multiselect from '@vueform/multiselect'

export default {
    name: 'multiselect',
    components: {
        Multiselect,
    },
    emits: ['change'],
    props: {
        options: Object,
        label: {type: String, default: ''},
    },
    data() {
        return {
            value: null,
        }
    }
}
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
