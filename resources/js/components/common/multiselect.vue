<template>
    <div>
        <Multiselect
            v-model="value"
            :options="options"
            mode="tags"
            :searchable="true"
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
    data() {
        return {
            value: null,
            options: [
                'Batman',
                'Robin',
                'Joker',
                'Batman 2',
                'Robin 2',
                'Joker 2',
            ]
        }
    }
}
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
