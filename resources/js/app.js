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


import stplotVisualization from "./components/wizard/stplot-visualization.vue";
import stplotQuilt from "./components/wizard/components/stplot-quilt.vue";
import stplotExpressionSurface from "./components/wizard/components/stplot-expression-surface.vue";

import sthetSpatialHet from "./components/wizard/sthet-spatial-het.vue";
import sthetPlot from "./components/wizard/components/sthet-plot.vue";

import sendJobButton from "./components/common/send-job-button.vue";

import spatialDomainDetection from "./components/wizard/spatial-domain-detection.vue";
import sddStclust from "./components/wizard/components/sdd-stclust.vue";

import differentialExpression from "./components/wizard/differential-expression.vue";
import stdeNonSpatial from "./components/wizard/components/stde-non-spatial.vue";
import stdeSpatial from "./components/wizard/components/stde-spatial.vue";

import spatialGeneSetEnrichment from "./components/wizard/spatial-gene-set-enrichment.vue";
import stenrich from "./components/wizard/components/stenrich.vue";

import spatialGradients from "./components/wizard/spatial-gradients.vue";
import stgradients from "./components/wizard/components/stgradients.vue";



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


app.component('stplot-visualization', stplotVisualization);
app.component('stplot-quilt', stplotQuilt);
app.component('stplot-expression-surface', stplotExpressionSurface);

app.component('sthet-spatial-het', sthetSpatialHet);
app.component('sthet-plot', sthetPlot);

app.component('send-job-button', sendJobButton);

app.component('spatial-domain-detection', spatialDomainDetection);
app.component('sdd-stclust', sddStclust);

app.component('differential-expression', differentialExpression);
app.component('stde-non-spatial', stdeNonSpatial);
app.component('stde-spatial', stdeSpatial);

app.component('spatial-gene-set-enrichment', spatialGeneSetEnrichment);
app.component('stenrich', stenrich);

app.component('spatial-gradients', spatialGradients);
app.component('stgradients', stgradients);



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

const getJobPositionInQueue = async (projectId, command) => {
    let position = 0;
    const response = await axios.get('/projects/' + projectId + '/get-job-position-in-queue', {params :{'command': command}});
    return response.data;
}
app.config.globalProperties.$getJobPositionInQueue = getJobPositionInQueue;
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

