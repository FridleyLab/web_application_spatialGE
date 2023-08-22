<template>

    <div class="w-xxl-90 w-95 container-fluid">
        <div class="mt-4">

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

                          key-expr="id"

                          @cell-prepared="onCellPrepared"

                          @selection-changed="onSelectionChanged"

                          @exporting="onExporting"
            >
                <DxSelection
                    mode="single"
                />

                <DxPaging :page-size="15" :enabled="true" />
                <DxPager :show-page-size-selector="true"
                         :show-info="true"
                         :allowed-page-sizes="[5, 10, 15, 20, 25, 30, 40, 50, 100]"
                         :show-navigation-buttons="true"
                />

        <!--        <DxToolbar>-->
        <!--            <DxItem template="scrollingModeTemplate" location="before" />-->
        <!--            <DxItem name="columnChooserButton" />-->
        <!--        </DxToolbar>-->


                <DxExport :enabled="true" :formats="['pdf', 'xlsx']" :allow-export-selected-data="false" />

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

                <DxMasterDetail
                    :enabled="true"
                    template="masterDetailTemplate"
                />
                <template #masterDetailTemplate="{ data: process }">
                    <div>

<!--                        <div class="my-6">-->
<!--                            <show-stats-plot-detail :plot-data="process.data.stats.map(obj => ({ time: obj.time, memory: obj.memory }))"></show-stats-plot-detail>-->
<!--                        </div>-->

                        <pre>
                            {{ process.data.output }}
                        </pre>
                    </div>
                </template>

            </dx-data-grid>
        </div>
    </div>

    <div class="my-6">
        <show-stats-plot :plot-data="plotData"></show-stats-plot>
    </div>

</template>
<script>

//Style to apply to the DataGrid
import 'devextreme/dist/css/dx.light.css';

import { exportDataGrid } from 'devextreme/excel_exporter';
import { Workbook } from 'exceljs';
import saveAs from 'file-saver';
import { jsPDF } from 'jspdf';
import { exportDataGrid as exportDataGridPdf } from 'devextreme/pdf_exporter';

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
    DxMasterDetail,

} from "devextreme-vue/data-grid";


export default {
    name: 'showStats',

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
        DxMasterDetail,
    },

    props: {
        headers: Object,
        data: Array,
        plotData: Array,
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


            if(cell.rowType === 'data' && cell.column.dataField === 'completed') {

                if(parseInt(cell.value) === 0 && cell.data.cancelled_at !== null) {
                    cell.cellElement.innerText = 'Cancelled';
                    cell.cellElement.style.cssText = "color:white; background-color: orange";
                }
                else if(cell.data.started_at !== null && cell.data.finished_at === null) {
                    cell.cellElement.innerText = 'Running';
                    cell.cellElement.style.cssText = "color:white; background-color: green";
                }
                else if(cell.data.started_at === null) {
                    cell.cellElement.innerText = 'Scheduled';
                    cell.cellElement.style.cssText = "color:white; background-color: green";
                }
                else if(parseInt(cell.value) === 1) {
                    cell.cellElement.innerText = 'OK';
                    cell.cellElement.style.cssText = "color:green;";
                }
                else if(parseInt(cell.value) === 0 && cell.data.finished_at !== null) {
                    cell.cellElement.innerText = 'Failed';
                    cell.cellElement.style.cssText = "color:white; background-color: red";
                }

            }

            /*if(['process_time', 'wait_time', 'total_time'].includes(cell.column.dataField)) {
                cell.displayValue = cell.value / 60;
                console.log(cell);
            }*/

        },

        onSelectionChanged(selectedRowsData) {
            const row = selectedRowsData['selectedRowsData'][0];
            console.log(row);
        },


        onExportingToExcel(e) {

            const workbook = new Workbook();
            const worksheet = workbook.addWorksheet('spatialGE statistics');
            exportDataGrid({
                component: e.component,
                worksheet: worksheet,
                customizeCell: function(options) {
                    options.excelCell.font = { name: 'Arial', size: 12 };
                    options.excelCell.alignment = { horizontal: 'left' };
                }
            }).then(function() {
                workbook.xlsx.writeBuffer()
                    .then(function(buffer) {
                        saveAs(new Blob([buffer], { type: 'application/octet-stream' }), 'spatialGE_' + Date.now() + '.xlsx');
                    });
            });
        },

        onExportingToPdf(e) {

            const doc = new jsPDF(
                {
                    orientation: "l"
                }
            );
            exportDataGridPdf({
                jsPDFDocument: doc,
                component: e.component,
                indent: 5
            }).then(() => {
                doc.save('spatialGE_' + Date.now() + '.pdf');
            });
        },

        onExporting(e) {
            console.log(e);
            if(e.format === 'xlsx') return this.onExportingToExcel(e);
            if(e.format === 'pdf') return this.onExportingToPdf(e);
        },

    },

}
</script>
