import { createRouter, createWebHistory } from 'vue-router'
import BoCucChinh from '../layouts/BoCucChinh.vue'
import TrangKhoiTao from '../pages/TrangKhoiTao.vue'

const boDinhTuyen = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: BoCucChinh,
      children: [{ path: '', name: 'khoi-tao', component: TrangKhoiTao }],
    },
  ],
})

export default boDinhTuyen
