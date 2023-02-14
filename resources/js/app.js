import './bootstrap';
import '../css/app.css';


//Modules
import sideMenu from "./components/ui/side-menu.vue";
import navbar from "./components/ui/navbar.vue";
import graficos from "./components/ui/graficos.vue";


import {createApp} from "vue";

const app = createApp({});

app.component('side-menu', sideMenu);
app.component('nav-bar', navbar);
app.component('graficos', graficos);


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
