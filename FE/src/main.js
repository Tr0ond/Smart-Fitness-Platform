import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './assets/main.css'
import App from './App.vue'
import boDinhTuyen from './router'

createApp(App).use(createPinia()).use(boDinhTuyen).mount('#app')
