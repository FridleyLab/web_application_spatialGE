import './bootstrap';
import '../css/app.css';

//Modules
import sideMenu from "./components/ui/side-menu.vue";
import navbar from "./components/ui/navbar.vue";
import graphics from "./components/ui/graphics.vue";
import signUp from "./components/ui/sign-up.vue";
import signIn from "./components/ui/sign-in.vue";
import signInPasswordReset from "./components/ui/sign-in-password-reset.vue";
import showMessage from "./components/common/show-message.vue";
import fileUpload from "./components/common/file-upload.vue";
import fileUploadDragDrop from "./components/common/file-upload-drag-drop.vue";
import importData from "./components/wizard/import-data.vue";
import newProject from './views/projects/new.vue';
import myProjects from './views/projects/index.vue';
import projectSamples from './views/projects/samples.vue';
import qcDataTransformation from "./components/wizard/qc-data-transformation.vue";
import qcDtFilter from "./components/wizard/components/qc-dt-filter.vue";
import qcDtNormalize from "./components/wizard/components/qc-dt-normalize.vue";
import qcDtPca from "./components/wizard/components/qc-dt-pca.vue";
import qcDtQuilt from "./components/wizard/components/qc-dt-quilt.vue";
import projectSummaryTable from "./components/wizard/components/project-summary-table.vue";
import showPlot from "./components/common/show-plot.vue";
import numericRange from "./components/common/numeric-range.vue";
import showModal from "./components/common/show-modal.vue";
import showModalContent from "./components/common/show-modal-content.vue";
import showVignette from "./components/common/show-vignette.vue";
import contactUs from "./components/ui/contact-us.vue";
import dataGrid from "./components/common/data-grid.vue";
import stDiffRenameAnnotationsClusters from './components/common/stdiff-rename-annotations-clusters.vue';

import ColorPalettes from "./components/common/color-palettes.vue";

import showStats from "./components/common/show-stats.vue";
import showStatsPlot from "./components/common/show-stats-plot.vue";
import showStatsPlotDetail from "./components/common/show-stats-plot-detail.vue";



import stplotVisualization from "./components/wizard/stplot-visualization.vue";
import stplotQuilt from "./components/wizard/components/stplot-quilt.vue";
import stplotExpressionSurface from "./components/wizard/components/stplot-expression-surface.vue";

import sthetSpatialHet from "./components/wizard/sthet-spatial-het.vue";
import sthetPlot from "./components/wizard/components/sthet-plot.vue";

import sendJobButton from "./components/common/send-job-button.vue";

import spatialDomainDetection from "./components/wizard/spatial-domain-detection.vue";
import sddStclust from "./components/wizard/components/sdd-stclust.vue";
import sddSpagcn from "./components/wizard/components/sdd-spagcn.vue";
import sddMilwrm from "./components/wizard/components/sdd-milwrm.vue";

import differentialExpression from "./components/wizard/differential-expression.vue";
import stdeNonSpatial from "./components/wizard/components/stde-non-spatial.vue";
import stdeSpatial from "./components/wizard/components/stde-spatial.vue";

import spatialGeneSetEnrichment from "./components/wizard/spatial-gene-set-enrichment.vue";
import stenrich from "./components/wizard/components/stenrich.vue";

import spatialGradients from "./components/wizard/spatial-gradients.vue";
import stgradients from "./components/wizard/components/stgradients.vue";

import phenotyping from "./components/wizard/phenotyping.vue";
import stdeconvolve from "./components/wizard/components/phenotyping-stdeconvolve.vue";
import stdeconvolve_suggested_ks from "./components/wizard/components/phenotyping-stdeconvolve-suggested-ks.vue";
import insitutype from "./components/wizard/components/phenotyping-insitutype.vue";

import sparkx from "./components/wizard/sparkx.vue";
import spark from "./components/wizard/components/spark.vue";

//Client-side Plots
import PlotsComponent from "./components/common/plots/PlotsComponent.vue";
import PlotHolder from "./components/common/plots/PlotHolder.vue";
import Heatmap from "./components/common/plots/heatmap.vue";
// import SideBySidePlot from "./components/common/plots/Editor.vue";
// import OverlayEditor from "./components/common/plots/OverlayEditor.vue";
// import PlotViewer from "./components/common/plots/PlotViewer.vue";


//Global event emitter
import mitt from 'mitt';
const emitter = mitt();




// Vuetify
/*import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
const vuetify = createVuetify({
    components,
    directives,
})*/


import {createApp} from "vue";
import StdiffRenameAnnotationsClusters from './components/common/stdiff-rename-annotations-clusters.vue';


const app = createApp({})/*.use(vuetify)*/;

//TODO: delete these two components
app.component('side-menu', sideMenu);
app.component('nav-bar', navbar);

//TODO: check if it's going to be needed or else, delete it
app.component('graficos', graphics);

app.component('sign-up', signUp);
app.component('sign-in', signIn);
app.component('sign-in-password-reset', signInPasswordReset);
app.component('show-message', showMessage);
// app.component('numeric-slider', numericSlider);
app.component('numeric-range', numericRange);
// app.component('multiselect', multiselect);
app.component('file-upload', fileUpload);
app.component('file-upload-drag-drop', fileUploadDragDrop);
app.component('new-project', newProject);
app.component('my-projects', myProjects);
app.component('project-samples', projectSamples);
app.component('import-data', importData);
app.component('qc-data-transformation', qcDataTransformation);
app.component('qc-dt-filter', qcDtFilter);
app.component('qc-dt-normalize', qcDtNormalize);
app.component('qc-dt-pca', qcDtPca);
app.component('qc-dt-quilt', qcDtQuilt);
app.component('project-summary-table', projectSummaryTable);
app.component('show-plot', showPlot);
app.component('show-modal', showModal);
app.component('show-modal-content', showModalContent);
app.component('show-vignette', showVignette);
app.component('contact-us', contactUs);
app.component('data-grid', dataGrid);
app.component('stdiff-rename-annotations-clusters', StdiffRenameAnnotationsClusters);

app.component('show-stats', showStats);
app.component('show-stats-plot', showStatsPlot);
app.component('show-stats-plot-detail', showStatsPlot);


app.component('stplot-visualization', stplotVisualization);
app.component('stplot-quilt', stplotQuilt);
app.component('stplot-expression-surface', stplotExpressionSurface);

app.component('sthet-spatial-het', sthetSpatialHet);
app.component('sthet-plot', sthetPlot);

app.component('send-job-button', sendJobButton);

app.component('spatial-domain-detection', spatialDomainDetection);
app.component('sdd-stclust', sddStclust);
app.component('sdd-spagcn', sddSpagcn);
app.component('sdd-milwrm', sddMilwrm);

app.component('differential-expression', differentialExpression);
app.component('stde-non-spatial', stdeNonSpatial);
app.component('stde-spatial', stdeSpatial);

app.component('spatial-gene-set-enrichment', spatialGeneSetEnrichment);
app.component('stenrich', stenrich);

app.component('spatial-gradients', spatialGradients);
app.component('stgradients', stgradients);

app.component('phenotyping', phenotyping);
app.component('stdeconvolve', stdeconvolve);
app.component('insitutype', insitutype);
app.component('stdeconvolve-suggested-ks', stdeconvolve_suggested_ks);

app.component('sparkx', sparkx);
app.component('spark', spark);

app.component('plots-component', PlotsComponent);
app.component('plot-holder', PlotHolder);
app.component('heatmap', Heatmap);
// app.component('side-by-side-plot', SideBySidePlot);
// app.component('PlotViewer', PlotViewer);
// app.component('OverlayEditor', OverlayEditor);

app.component('color-palettes', ColorPalettes);


//Register the window as a global variable, so it can be accessed everywhere
app.config.globalProperties.window = window;



//Define helper functions

const getProjectParameters = async (projectId) => {
    /*if(this.project === null) {
        this.$emit('completed');
    }*/

    const response = await  axios.get('/projects/' + projectId + '/get-project-parameters');
        /*.then((response) => {
            this.project.project_parameters = response.data;
            this.$emit('completed');
        })
        .catch((error) => console.log(error));*/

    return response.data;

}
app.config.globalProperties.$getProjectParameters = getProjectParameters;

const getProjectSTdiffAnnotations = async (projectId) => {
    const response = await  axios.get('/projects/' + projectId + '/get-stdiff-annotations');
    return response.data;
}
app.config.globalProperties.$getProjectSTdiffAnnotations = getProjectSTdiffAnnotations;

const getProjectSTdiffAnnotationsBySample = async (projectId, method) => {
    const response = await  axios.get('/projects/' + projectId + '/get-stdiff-annotations-by-sample/' + method);
    return response.data;
}
app.config.globalProperties.$getProjectSTdiffAnnotationsBySample = getProjectSTdiffAnnotationsBySample;

const getJobPositionInQueue = async (projectId, command) => {
    const response = await axios.get('/projects/' + projectId + '/get-job-position-in-queue', {params :{'command': command}});
    return response.data;
}
app.config.globalProperties.$getJobPositionInQueue = getJobPositionInQueue;

const getJobParameters = async (projectId, command) => {
    const response = await axios.get('/projects/' + projectId + '/get-job-parameters', {params :{'command': command}});
    return response.data;
}
app.config.globalProperties.$getJobParameters = getJobParameters;

const enableWizardStep = (step) => {
    ['a', 'i', 'span'].forEach(tag => {
        let element = window.document.getElementById(step + '-' + tag);
        element.classList.remove('disabled');
        element.classList.remove('text-secondary');
    });
}
app.config.globalProperties.$enableWizardStep = enableWizardStep;

//Register the global event emitter
app.config.globalProperties.emitter = emitter;

app.mount('#app');


//Insert scripts that need access to elements in the 'app' tag (i.e. where the vue app in mounted)
let scripts = [
    '/assets/js/core/popper.min.js',
    '/assets/js/core/bootstrap.min.js',
    '/assets/js/plugins/perfect-scrollbar.min.js',
    '/assets/js/plugins/smooth-scrollbar.min.js',
    '/assets/js/scrollbar.js',
    '/assets/js/material-dashboard.js?v=3.0.4'];
scripts.forEach((_script) => {
    const code = document.createElement("script");
    //code.setAttribute("src",_script);
    //code.setAttribute("async",false);
    code.src=_script;
    code.async = false;
    document.getElementById('app').appendChild(code);
});

