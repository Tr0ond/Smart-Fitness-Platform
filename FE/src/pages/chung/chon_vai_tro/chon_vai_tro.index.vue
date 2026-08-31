<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { CAC_VAI_TRO_WEB, dieuPhoiTheoVaiTro } from '../../../router/bao_ve_tuyen_duong.js'
import { useXacThucStore } from '../../../stores/xac_thuc.store.js'

const router = useRouter()
const store = useXacThucStore()
const dangThuLaiKhoiPhuc = ref(false)
const tenVaiTro = Object.freeze({
  ADMIN: 'Quản trị viên',
  PT: 'Huấn luyện viên',
  RECEPTIONIST: 'Lễ tân',
})

const danhSachVaiTroWeb = computed(() => {
  if (!Array.isArray(store.vaiTro)) {
    return []
  }

  return store.vaiTro.filter((vaiTro) => CAC_VAI_TRO_WEB.includes(vaiTro))
})

const daCoPhien = computed(() => typeof store.token === 'string' && store.token !== '' && store.nguoiDung !== null)
const laTaiKhoanKhongHoTro = computed(() => daCoPhien.value && danhSachVaiTroWeb.value.length === 0)

/**
 * Thu lai GET /auth/me sau network/timeout/5xx ma khong tao token moi.
 *
 * Dau vao: token dang duoc giu trong sessionStorage boi Auth Store.
 * Cach hoat dong: goi lai lifecycle restore; Store tiep tuc fail-closed trong luc pending
 * va chi khoi phuc user/roles/actor khi /me thanh cong.
 * Ket qua: selector hien roles moi, login neutral neu token invalid, hoac loi tam thoi moi.
 * Side effect: mot request /me; khong tu retry lap va khong mo protected content tu cache.
 * Business Rule: 401 xoa phien, network/timeout/5xx giu token de nguoi dung chu dong retry.
 */
async function thuLaiKhoiPhucPhien() {
  if (dangThuLaiKhoiPhuc.value || store.dangKhoiPhucPhien) {
    return
  }

  dangThuLaiKhoiPhuc.value = true

  try {
    await store.khoiPhucPhien()
  } finally {
    dangThuLaiKhoiPhuc.value = false
  }
}

/**
 * Chon actor context tu danh sach role da revalidate boi /auth/me.
 *
 * Dau vao: mot Web role dang hien thi trong selector.
 * Cach hoat dong: goi action Store allow-list, sau do chi dieu huong neu da co home route dang ky.
 * Ket qua: selector giu nguyen neu chua co business home; khong tao route gia.
 * Side effect: luu actor context hop le vao sessionStorage qua Store.
 * Business Rule: khong tu uu tien role, khong chon MEMBER va khong goi API role mutation.
 */
async function chonCongViec(vaiTro) {
  store.chonVaiTroDangDung(vaiTro)
  const diemDen = dieuPhoiTheoVaiTro(store, vaiTro)

  if (diemDen !== null) {
    await router.push(diemDen)
  }
}

/**
 * Dang xuat khoi neutral selector va xoa phien local/remote theo Auth Store.
 *
 * Dau vao: khong co.
 * Cach hoat dong: goi logout best-effort cua Store, sau do quay ve chooser cong khai.
 * Ket qua: route chooser khong xac thuc.
 * Side effect: Auth Store luon xoa token, actor, user va roles local.
 * Business Rule: logout khong phu thuoc remote logout thanh cong moi cleanup.
 */
async function xuLyDangXuat() {
  await store.dangXuat()
  await router.replace({ name: 'chonVaiTro' })
}
</script>

<template>
  <section aria-labelledby="tieu-de-chon-vai-tro">
    <h1 id="tieu-de-chon-vai-tro">
      Chọn vai trò làm việc
    </h1>

    <TrangThaiTaiDuLieu
      v-if="store.dangKhoiPhucPhien || dangThuLaiKhoiPhuc"
      nhan="Đang xác minh lại phiên làm việc…"
    />

    <TrangThaiLoi
      v-else-if="store.loiKhoiPhucPhien"
      :thong-bao="store.loiKhoiPhucPhien.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="dangThuLaiKhoiPhuc"
      @thu-lai="thuLaiKhoiPhucPhien"
    />

    <template v-else-if="!daCoPhien">
      <p>Cổng Web hỗ trợ Quản trị viên, Huấn luyện viên và Lễ tân.</p>
      <p>Hãy chọn trang đăng nhập phù hợp với tài khoản của bạn.</p>
      <nav aria-label="Các trang đăng nhập">
        <ul>
          <li>
            <RouterLink :to="{ name: 'adminDangNhap' }">
              Đăng nhập quản trị viên
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'ptDangNhap' }">
              Đăng nhập huấn luyện viên
            </RouterLink>
          </li>
          <li>
            <RouterLink :to="{ name: 'leTanDangNhap' }">
              Đăng nhập lễ tân
            </RouterLink>
          </li>
        </ul>
      </nav>
      <p>
        <RouterLink :to="{ name: 'quenMatKhau' }">
          Quên mật khẩu?
        </RouterLink>
      </p>
    </template>

    <template v-else-if="laTaiKhoanKhongHoTro">
      <p>Cổng Web này chỉ hỗ trợ Quản trị viên, Huấn luyện viên và Lễ tân.</p>
      <p>Tài khoản hội viên không có khu vực làm việc trên cổng Web.</p>
      <button
        class="nut nut--phu"
        type="button"
        @click="xuLyDangXuat"
      >
        Đăng xuất
      </button>
    </template>

    <template v-else>
      <p>Chọn khu vực làm việc cho phiên hiện tại.</p>
      <ul>
        <li
          v-for="vaiTro in danhSachVaiTroWeb"
          :key="vaiTro"
        >
          <button
            class="nut nut--phu"
            type="button"
            @click="chonCongViec(vaiTro)"
          >
            {{ tenVaiTro[vaiTro] }}
          </button>
        </li>
      </ul>
      <p v-if="store.vaiTroDangDung">
        Vai trò đang dùng: {{ tenVaiTro[store.vaiTroDangDung] }}
      </p>
      <button
        class="nut nut--phu"
        type="button"
        @click="xuLyDangXuat"
      >
        Đăng xuất
      </button>
    </template>
  </section>
</template>
