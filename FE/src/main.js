import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './assets/main.css'
import App from './App.vue'
import boDinhTuyen from './router'
import { useXacThucStore } from './stores/xac_thuc.store.js'
import { dieuPhoiSau401 } from './utils/dieu_phoi_xac_thuc.js'

const pinia = createPinia()
const ungDung = createApp(App)
const storeXacThuc = useXacThucStore(pinia)

storeXacThuc.dangKyCoCheAuth((vaiTroTruocKhiHetPhien) => (
  dieuPhoiSau401(boDinhTuyen, vaiTroTruocKhiHetPhien)
))

ungDung.use(pinia).use(boDinhTuyen).mount('#app')
