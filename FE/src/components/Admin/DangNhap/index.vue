<template>
  <section class="man-hinh-dang-nhap">
    <BieuMauDangNhap
      tieu-de="Đăng nhập quản trị viên"
      vai-tro-nhan="Quản trị hệ thống"
      mo-ta="Theo dõi vận hành, hội viên và các cấu hình cốt lõi của phòng tập."
      :dang-xu-ly-dang-nhap="xuLyDangNhapAdmin"
    />
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router'
import BieuMauDangNhap from '../../xac_thuc/bieu_mau_dang_nhap.vue'
import { useXacThucStore } from '../../../stores/xac_thuc.store.js'
import { dieuPhoiSauDangNhap } from '../../../utils/dieu_phoi_xac_thuc.js'

const router = useRouter()
const store = useXacThucStore()

/**
 * Xu ly dang nhap tai entry Admin va chi tiep tuc voi role do Backend revalidate.
 *
 * Dau vao: email/password tu bieu mau dung chung.
 * Cach hoat dong: goi Auth Store, sau do dieu phoi neutral neu account multi-role/mismatch/member-only.
 * Ket qua: promise navigation noi bo; khong tao Admin session neu Backend khong tra ADMIN.
 * Side effect: Store co the tao token/actor context theo contract; password khong duoc luu.
 * Business Rule: Frontend khong gui role ADMIN cho Backend va khong tu cap quyen.
 */
async function xuLyDangNhapAdmin(thongTinDangNhap) {
  await store.dangNhap(thongTinDangNhap)
  await dieuPhoiSauDangNhap(store, router, 'ADMIN')
}
</script>

<style scoped>
.man-hinh-dang-nhap {
  min-height: 100%;
  width: 100%;
}
</style>
