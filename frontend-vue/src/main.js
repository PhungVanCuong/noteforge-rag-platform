import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'
import { dragScroll } from './directives/dragScroll'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.directive('drag-scroll', dragScroll)

app.mount('#app')
