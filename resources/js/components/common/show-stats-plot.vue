<template>

    <div class="w-xxl-90 w-95 container-fluid">
        <div class="mt-4">

            <DxChart
                id="chart"
                :data-source="plotData"
                title="RAM by process and samples"
                @pointClick="onPointClick"
            >
                <DxCommonSeriesSettings
                    argument-field="process"
                    type="bar"
                    hover-mode="allArgumentPoints"
                    selection-mode="allArgumentPoints"
                >
                    <DxLabel :visible="true">
                        <DxFormat
                            :precision="0"
                            type="fixedPoint"
                        />
                    </DxLabel>
                </DxCommonSeriesSettings>


                <template v-for="key in Object.keys(plotData[0])">
                    <DxSeries v-if="key !== 'process'"
                        :value-field="key"
                        :name="key"
                    />
                </template>

                <DxLegend
                    vertical-alignment="bottom"
                    horizontal-alignment="center"
                />
                <DxExport :enabled="true"/>

            </DxChart>

        </div>
    </div>

</template>
<script>

//Style to apply to the DataGrid
import 'devextreme/dist/css/dx.light.css';

import {
    DxChart,
    DxSeries,
    DxCommonSeriesSettings,
    DxLabel,
    DxFormat,
    DxLegend,
    DxExport,
} from 'devextreme-vue/chart';

export default {
    name: 'showStatsPlot',

    components: {
        DxChart,
        DxSeries,
        DxCommonSeriesSettings,
        DxLabel,
        DxFormat,
        DxLegend,
        DxExport,
    },

    props: {
        plotData: Array,
    },

    data() {
        return {
        };
    },
    methods: {
        onPointClick({ target }) {
            target.select();
        },
    },
};
</script>
<style>
#chart {
    height: 600px;
}
</style>
