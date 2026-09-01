<template>
  <section class="man-hinh-dang-nhap">
    <BieuMauDangNhap
      tieu-de="Đăng nhập huấn luyện viên"
      vai-tro-nhan="Không gian huấn luyện"
      mo-ta="Theo dõi hội viên được phân công và đồng hành cùng tiến độ tập luyện."
      :dang-xu-ly-dang-nhap="xuLyDangNhapPt"
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

/**`
 * Xu ly dang nhap tai entry PT va chi tiep tuc voi role do Backend revalidate.
 *
 * Dau vao: email/password tu bieu mau dung chung.
 * Cach hoat dong: goi Auth Store, sau do dieu phoi neutral neu account multi-role/mismatch/member-only.
 * Ket qua: promise navigation noi bo; khong tao PT session neu Backend khong tra PT.
 * Side effect: Store co the tao token/actor context theo contract; password khong duoc luu.
 * Business Rule: Frontend khong gui role PT cho Backend va khong tu cap quyen.
 */
async function xuLyDangNhapPt(thongTinDangNhap) {
  await store.dangNhap(thongTinDangNhap)
  await dieuPhoiSauDangNhap(store, router, 'PT')
}
</script>

<style scoped>
.man-hinh-dang-nhap {
  min-height: 100%;
  width: 100%;
}
</style>
