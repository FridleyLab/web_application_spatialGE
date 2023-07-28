<template>


    <button type="button" class="btn btn-sm btn-outline-secondary" @click="scrollingMode = scrollingMode === 'standard' ? 'virtual' : 'standard'">{{ scrollingMode === 'standard' ? 'Continuous scrolling' : 'Paginated view' }}</button>
    <dx-data-grid

                  :style="scrollingMode !== 'standard' ? 'height: 70vh;' : ''"

                  class="dx-card wide-card"

                  :show-borders="true"
                  :allow-column-resizing="true"
                  :allow-column-reordering="true"


                  :column-auto-width="true"

                  :data-source="data"

                  :word-wrap-enabled="false"

                  @cell-prepared="onCellPrepared"
    >

        <DxPaging :page-size="15" :enabled="true" />
        <DxPager :show-page-size-selector="true"
                 :show-info="true"
                 :allowed-page-sizes="[10, 15, 20, 25, 30, 40, 50, 100]"
                 :show-navigation-buttons="true"
        />

<!--        <DxToolbar>-->
<!--            <DxItem template="scrollingModeTemplate" location="before" />-->
<!--            <DxItem name="columnChooserButton" />-->
<!--        </DxToolbar>-->

        <DxFilterRow :visible="true" />
        <DxHeaderFilter :visible="true" />
        <DxFilterPanel :visible="true" />

        <DxColumnChooser :enabled="true" />
        <DxSearchPanel :visible="true" />
        <DxGroupPanel :visible="true" />

        <DxScrolling :mode="scrollingMode"/>

        <DxColumn v-for="(column, index) in headers"
                  :data-field="column" :cell-template="column === 'gene' ? (column + '-cell') : ''"></DxColumn>

        <template #gene-cell="{ data }">
            <a :href="'https://www.genecards.org/cgi-bin/carddisp.pl?gene=' + data.text" class="text-info" target="_blank">{{ data.text }}</a>
        </template>

        <template #scrollingModeTemplate>
            <DxButton
                :text="scrollingMode === 'standard' ? 'Continuous scrolling' : 'Paginated view'"
                width="136"
                @click="scrollingMode = scrollingMode === 'standard' ? 'virtual' : 'standard'"
            />
        </template>

    </dx-data-grid>

</template>
<script>

//Style to apply to the DataGrid
import 'devextreme/dist/css/dx.light.css';

//Import the components to be used in the DataGrid
import DxDataGrid, {
    DxColumn,
    DxFilterRow,
    DxLookup,
    DxPager,
    DxPaging,
    DxColumnChooser,
    DxSearchPanel,
    DxGroupPanel,
    DxExport,
    DxSelection,
    DxHeaderFilter,
    DxFilterPanel,
    DxScrolling,
    DxLoadPanel,
    DxToolbar,
    DxItem,
    DxButton,
} from "devextreme-vue/data-grid";


export default {
    name: 'dataGrid',

    components: {

        DxDataGrid,
        DxColumn,
        DxFilterRow,
        DxLookup,
        DxPager,
        DxPaging,
        DxColumnChooser,
        DxSearchPanel,
        DxGroupPanel,
        DxExport,
        DxSelection,
        DxHeaderFilter,
        DxFilterPanel,
        DxScrolling,
        DxLoadPanel,
        DxToolbar,
        DxItem,
        DxButton,

    },

    props: {
        headers: Array,
        data: Array,
    },

    data() {
        return {
            scrollingMode: 'standard'
        }
    },

    mounted() {

    },

    watch: {

    },

    methods: {
        onCellPrepared(cell) {
            if(cell.column.dataField === 'gene')
                console.log()
                //cell.cellElement.href  = '<a href="https://google.com" target="_blank">Goo</a>';
        },
    },

}
</script>
