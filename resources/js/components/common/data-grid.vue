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
                  :data-field="column"
                  :cell-template="column === 'gene' ? (column + '-cell') : ''"
                  :data-type="is_numeric_column(column) ? 'number' : ''"
                  :alignment="is_numeric_column(column) ? 'right' : ''"
        >
        </DxColumn>

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

        is_numeric_column(column) {
            return ['size_test', 'size_gene_set', 'p_value', 'adj_p_value', 'avg_log2fc', 'cluster_1', 'cluster_2',
                'wilcox_p_val', 'ttest_p_val', 'mm_p_val', 'adj_p_val', 'exp_p_val', 'exp_adj_p_val',
                'min_lm_coef', 'min_lm_pval', 'min_spearman_r', 'min_spearman_r_pval', 'min_spearman_r_pval_adj',
                'avg_lm_coef', 'avg_lm_pval', 'avg_spearman_r', 'avg_spearman_r_pval', 'avg_spearman_r_pval_adj'
            ].includes(column);
        }
    },

}
</script>
