import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import BieuMauDangNhapAdmin from '../pages/admin/dang_nhap/dang_nhap.index.vue'
import BieuMauDangNhapLeTan from '../pages/le_tan/dang_nhap/dang_nhap.index.vue'
import BieuMauDangNhapPt from '../pages/pt/dang_nhap/dang_nhap.index.vue'
import ChonVaiTro from '../pages/chung/chon_vai_tro/chon_vai_tro.index.vue'
import DatLaiMatKhau from '../pages/chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue'
import KhongCoQuyen from '../pages/chung/loi/khong_co_quyen.vue'
import KhongTimThay from '../pages/chung/loi/khong_tim_thay.vue'
import QuenMatKhau from '../pages/chung/quen_mat_khau/quen_mat_khau.index.vue'
import { useXacThucStore } from '../stores/xac_thuc.store.js'
import { taoBaoVeTuyenDuong } from './bao_ve_tuyen_duong.js'

const TrangDieuPhoiRong = { template: '<span aria-hidden="true"></span>' }
const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

/**
 * Dat focus vao heading chinh sau khi Vue Router render route moi.
 *
 * Dau vao: khong co; document cua SPA sau navigation.
 * Cach hoat dong: doi mot tick render, tim h1 dau tien, them tabindex -1 khi can
 * va focus khong cuon trang de screen reader/keyboard nhan biet context moi.
 * Ket qua: tra true neu focus duoc heading, false khi page khong co h1/document.
 * Side effect: co the them tabindex presentation vao heading va thay activeElement.
 * UI Rule: sau navigation focus khong duoc roi ve body.
 */
export async function datFocusVaoTieuDeSauDieuHuong() {
  await nextTick()

  if (typeof document === 'undefined') {
    return false
  }

  const tieuDe = document.querySelector('h1')

  if (!(tieuDe instanceof HTMLElement)) {
    return false
  }

  if (!tieuDe.hasAttribute('tabindex')) {
    tieuDe.setAttribute('tabindex', '-1')
  }

  tieuDe.focus({ preventScroll: true })
  return document.activeElement === tieuDe
}

const boDinhTuyen = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dieuPhoiTrangGoc',
      component: TrangDieuPhoiRong,
      meta: { tinhNang: 'dieuPhoiTrangGoc' },
    },
    {
      path: '/chon-vai-tro',
      name: 'chonVaiTro',
      component: ChonVaiTro,
      meta: { boCuc: 'cong_khai', yeuCauXacThuc: true, tinhNang: 'chonVaiTro' },
    },
    {
      path: '/quen-mat-khau',
      name: 'quenMatKhau',
      component: QuenMatKhau,
      meta: { boCuc: 'cong_khai', congKhai: true, tinhNang: 'quenMatKhau' },
    },
    {
      path: '/dat-lai-mat-khau',
      name: 'datLaiMatKhau',
      component: DatLaiMatKhau,
      meta: { boCuc: 'cong_khai', congKhai: true, tinhNang: 'datLaiMatKhau' },
    },
    {
      path: '/khong-co-quyen',
      name: 'khongCoQuyen',
      component: KhongCoQuyen,
      meta: { boCuc: 'loi', congKhai: true, tinhNang: 'khongCoQuyen' },
    },
    {
      path: '/khong-tim-thay',
      name: 'khongTimThay',
      component: KhongTimThay,
      meta: { boCuc: 'loi', congKhai: true, tinhNang: 'khongTimThay' },
    },
    {
      path: '/admin/dang-nhap',
      name: 'adminDangNhap',
      component: BieuMauDangNhapAdmin,
      meta: { boCuc: 'cong_khai', congKhai: true, vaiTro: 'ADMIN', tinhNang: 'adminDangNhap' },
    },
    {
      path: '/pt/dang-nhap',
      name: 'ptDangNhap',
      component: BieuMauDangNhapPt,
      meta: { boCuc: 'cong_khai', congKhai: true, vaiTro: 'PT', tinhNang: 'ptDangNhap' },
    },
    {
      path: '/le-tan/dang-nhap',
      name: 'leTanDangNhap',
      component: BieuMauDangNhapLeTan,
      meta: {
        boCuc: 'cong_khai',
        congKhai: true,
        vaiTro: 'RECEPTIONIST',
        tinhNang: 'leTanDangNhap',
      },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'khongTimThay' },
    },
  ],
})

boDinhTuyen.beforeEach(async (routeDich) => {
  const store = useXacThucStore()

  return baoVeTuyenDuong(routeDich, store)
})

boDinhTuyen.afterEach(() => {
  void datFocusVaoTieuDeSauDieuHuong()
})

export default boDinhTuyen
