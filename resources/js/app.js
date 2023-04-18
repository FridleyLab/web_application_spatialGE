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
// import numericSlider from "./components/common/numeric-slider.vue";
import numericRange from "./components/common/numeric-range.vue";
// import multiselect from "./components/common/multiselect.vue";

import stplotVisualization from "./components/wizard/stplot-visualization.vue";
import stplotQuilt from "./components/wizard/components/stplot-quilt.vue";


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

app.component('stplot-visualization', stplotVisualization);
app.component('stplot-quilt', stplotQuilt);

//Register the window as a global variable, so it can be accessed everywhere
app.config.globalProperties.window = window;

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
    code.setAttribute(
        "src",
        _script
    );
    document.getElementById('app').appendChild(code);
});

