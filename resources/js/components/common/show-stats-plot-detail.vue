<template>
    <div id="chart-demo">
        <DxChart
            :data-source="plotData"
        >
            <DxSeries
                argument-field="time"
                value-field="memory"
                name="RAM"
                type="bar"
                color="#ffaa66"
            />
        </DxChart>

        <pre>
            {{ plotData }}
        </pre>

    </div>
</template>
<script>

import { DxChart, DxSeries } from 'devextreme-vue/chart';

export default {

    components: {
        DxChart,
        DxSeries,
    },

    props: {
        plotData: Array,
    },

    mounted() {
        console.log(this.plotData);
    },

    data() {
        return {

        };
    },
};
</script>
<style>
.options {
    padding: 20px;
    background-color: rgba(191, 191, 191, 0.15);
    margin-top: 20px;
}

.option {
    margin-top: 10px;
}

.caption {
    font-size: 18px;
    font-weight: 500;
}

.option > span {
    margin-right: 14px;
}

.option > .dx-widget {
    display: inline-block;
    vertical-align: middle;
}
</style>
