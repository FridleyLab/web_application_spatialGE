import './bootstrap';
import '../css/app.css';


//Modules
import sideMenu from "./components/ui/side-menu.vue";
import navbar from "./components/ui/navbar.vue";
import graphics from "./components/ui/graphics.vue";
import signUp from "./components/ui/sign-up.vue";
import signIn from "./components/ui/sign-in.vue";
import showMessage from "./components/common/show-message.vue";
import fileUpload from "./components/common/file-upload.vue";
import importData from "./components/wizard/import-data.vue";
import newProject from './views/projects/new.vue';
import myProjects from './views/projects/index.vue';

import {createApp} from "vue";


const app = createApp({});

//TODO: delete these two components
app.component('side-menu', sideMenu);
app.component('nav-bar', navbar);

//TODO: check if it's going to be needed or else, delete it
app.component('graficos', graphics);

app.component('sign-up', signUp);
app.component('sign-in', signIn);
app.component('show-message', showMessage);
app.component('file-upload', fileUpload);
app.component('new-project', newProject);
app.component('my-projects', myProjects);
app.component('import-data', importData)

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

