<template>


    <button v-if="scrollingToggle" type="button" class="btn btn-sm btn-outline-secondary" @click="scrollingMode = scrollingMode === 'standard' ? 'virtual' : 'standard'">{{ scrollingMode === 'standard' ? 'Continuous scrolling' : 'Paginated view' }}</button>
    <dx-data-grid

        :style="scrollingMode !== 'standard' ? 'height: 70vh;' : ''"

        class="dx-card wide-card"

        :show-borders="true"
        :allow-column-resizing="true"
        :allow-column-reordering="true"

        :selected-row-keys="selectedKeys"


        :column-auto-width="true"

        :data-source="_data"

        :word-wrap-enabled="false"

        :row-alternation-enabled="true"

        :key-expr="keyAttribute"

        v-model:selected-row-keys="selectedRowKeys"
        @selection-changed="onSelectionChanged"
        @cell-prepared="onCellPrepared"

    >

        <DxPaging :page-size="pageSize" :enabled="true" />
        <DxPager :show-page-size-selector="true"
                 :show-info="true"
                 :allowed-page-sizes="[10, 15, 20, 25, 30, 40, 50, 100]"
                 :show-navigation-buttons="true"

        />

        <DxSelection v-if="allowSelection"
            mode="multiple"
            :allow-select-all="true"
            show-check-boxes-mode="always"
        />

<!--        <DxToolbar>-->
<!--            <DxItem template="scrollingModeTemplate" location="before" />-->
<!--            <DxItem name="columnChooserButton" />-->
<!--        </DxToolbar>-->

        <DxFilterRow :visible="showFilterRow" />
<!--        <DxHeaderFilter :visible="true" />-->
<!--        <DxFilterPanel :visible="true" />-->

        <DxColumnChooser :enabled="showColumnChooser" />
        <DxSearchPanel :visible="showSearchPanel" />
        <DxGroupPanel :visible="showGroupPanel" />

        <DxScrolling :mode="scrollingMode" use-native="true" />

        <DxColumn v-for="(column, index) in _headers"
                  :data-field="column.value"
                  :cell-template="showGeneCard && ['gene', 'genes'].includes(column.value) ? (column.value + '-cell') : ''"
                  :data-type="is_numeric_column(column.value) ? 'number' : ''"
                  :alignment="is_numeric_column(column.value) ? 'right' : ''"
                  :caption="'text' in column ? column.text : column.value"
        >
        </DxColumn>

        <template #gene-cell="{ data }">
            <a :href="'https://www.genecards.org/cgi-bin/carddisp.pl?gene=' + data.text" class="text-info" target="_blank">{{ data.text }}</a>
        </template>

        <template #genes-cell="{ data }">
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

    emits: ['selected'],

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
        headers: {type: Array, default: []},
        data: {type: Array, default: []},
        scrollingToggle: {type: Boolean, default: true},
        showFilterRow: {type: Boolean, default: true},
        showColumnChooser: {type: Boolean, default: true},
        showSearchPanel: {type: Boolean, default: true},
        showGroupPanel: {type: Boolean, default: false},
        showGeneCard: {type: Boolean, default: true},
        allowSelection: {type: Boolean, default: true},
        keyAttribute: {type: String, default: ''},
        pageSize: {type: Number, default: 15},
        selectedKeys: {type: Object, default: []},
        src: {type: String, default: ''},
    },

    data() {
        return {
            scrollingMode: 'standard',
            selectedRowKeys: [],
            _headers: this.headers,
            _data: this.data,
        }
    },

    mounted() {

        this.loadData();

    },

    watch: {

        headers() {
            this.loadData();
        },

        data() {
            this.loadData();
        }
    },

    methods: {

        loadData() {

            this._headers = this.headers;
            this._data = this.data;

            if(this.src.length) {

            axios.get(this.src + '?' + Date.now())
                .then((response) => {
                    this._data = response.data.items;
                    this._headers = response.data.headers;
                })
                .catch((error) => {
                    console.log(error.message);
                })
            }
        },

        onCellPrepared(cell) {
            /*if(cell.rowType === 'data' && this.is_numeric_column(cell.column.dataField)) {
                console.log(cell);
                cell.text = cell.data[cell.column.dataField];
                cell.displayValue = cell.data[cell.column.dataField].toString();
            }*/
        },

        onSelectionChanged(e) {
            const currentSelectedRowKeys = e.currentSelectedRowKeys;
            const currentDeselectedRowKeys = e.currentDeselectedRowKeys;
            const allSelectedRowKeys = e.selectedRowKeys;
            const allSelectedRowsData = e.selectedRowsData;

            // console.log(currentSelectedRowKeys);
            // console.log(allSelectedRowKeys);

            this.$emit('selected', allSelectedRowKeys);

        },

        is_numeric_column(column) {
            return ['size_test', 'size_gene_set', 'p_value', 'adj_p_value', 'avg_log2fc', 'cluster_1', 'cluster_2',
                'wilcox_p_val', 'ttest_p_val', 'mm_p_val', 'adj_p_val', 'exp_p_val', 'exp_adj_p_val',
                'min_lm_coef', 'min_lm_pval', 'min_spearman_r', 'min_spearman_r_pval', 'min_spearman_r_pval_adj',
                'avg_lm_coef', 'avg_lm_pval', 'avg_spearman_r', 'avg_spearman_r_pval', 'avg_spearman_r_pval_adj',
                'sscore', 'q_val', 'p_val', 'in_group_fraction', 'out_group_fraction', 'in_out_group_ratio', 'in_group_mean_exp',
                'out_group_mean_exp', 'fold_change', 'pvals_adj',

                'spotscells', 'genes', 'min_counts_per_spotcell', 'mean_counts_per_spotcell', 'max_counts_per_spotcell', 'min_genes_per_spotcell', 'mean_genes_per_spotcell', 'max_genes_per_spotcell'
            ].includes(column);
        }
    },

}
</script>
