import sideMenu from './components/side_menu.vue'

let app = {
    components: {
        sideMenu: sideMenu
    }
}

Vue.createApp(app).mount('#app');