import './bootstrap';
import '../css/app.css';


//Modules
import sideMenu from "./components/ui/side-menu.vue";
import navbar from "./components/ui/navbar.vue";
import graphics from "./components/ui/graphics.vue";
import signUp from "./components/ui/sign-up.vue";
import signIn from "./components/ui/sign-in.vue";
import errorMessage from "./components/common/error-message.vue";
import wizard from "./components/wizard/wizard.vue";

import {createApp} from "vue";


const app = createApp({});

//TODO: delete these two components
app.component('side-menu', sideMenu);
app.component('nav-bar', navbar);

//TODO: check if it's going to be needed or else, delete it
app.component('graficos', graphics);

app.component('sign-up', signUp);
app.component('sign-in', signIn);
app.component('error-message', errorMessage);
app.component('wizard', wizard)

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

