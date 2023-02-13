import './bootstrap';
import '../css/app.css';

/*import "./assets/js/core/popper.min.js";
import "./assets/js/core/bootstrap.min.js";
import "./assets/js/plugins/perfect-scrollbar.min.js";
import "./assets/js/plugins/smooth-scrollbar.min.js";
*/

//Modules
import sideMenu from "./components/side-menu.vue";
import graficos from "./components/graficos.vue";


import { createApp } from "vue";

const app = createApp({});


app.component('side-menu', sideMenu);
app.component('graficos', graficos);


















app.mount('#app');
