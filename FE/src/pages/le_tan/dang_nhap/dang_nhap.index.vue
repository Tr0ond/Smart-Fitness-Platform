<script setup>
import { useRouter } from 'vue-router'
import BieuMauDangNhap from '../../../components/xac_thuc/bieu_mau_dang_nhap.vue'
import { useXacThucStore } from '../../../stores/xac_thuc.store.js'
import { dieuPhoiSauDangNhap } from '../../../utils/dieu_phoi_xac_thuc.js'

const router = useRouter()
const store = useXacThucStore()

/**
 * Xu ly login tren entry Le tan va chi tiep tuc voi role authority tu /auth/me.
 *
 * Dau vao: email/password tu bieu mau dung chung.
 * Cach hoat dong: goi Auth Store, sau do dieu phoi neutral neu account multi-role/mismatch/member-only.
 * Ket qua: promise navigation noi bo; khong tao Le tan session neu Backend khong tra RECEPTIONIST.
 * Side effect: Store co the tao token/actor context theo contract; password khong duoc luu.
 * Business Rule: Frontend khong gui role RECEPTIONIST cho Backend va khong tu cap quyen.
 */
async function xuLyDangNhapLeTan(thongTinDangNhap) {
  await store.dangNhap(thongTinDangNhap)
  await dieuPhoiSauDangNhap(store, router, 'RECEPTIONIST')
}
</script>

<template>
  <BieuMauDangNhap
    tieu-de="Đăng nhập lễ tân"
    :dang-xu-ly-dang-nhap="xuLyDangNhapLeTan"
  />
</template>
