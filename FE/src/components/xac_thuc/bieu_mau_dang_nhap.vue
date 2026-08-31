<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import TruongBieuMau from '../dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../dung_chung/vung_thong_bao.vue'
import { suDungBieuMau } from '../../composables/su_dung_bieu_mau.js'

defineOptions({ name: 'BieuMauDangNhap' })

const props = defineProps({
  tieuDe: { type: String, required: true },
  dangXuLyDangNhap: { type: Function, required: true },
})

const email = ref('')
const matKhau = ref('')
const dangGui = ref(false)
const {
  loiTongQuat,
  giayChoConLai,
  dangBiGioiHan,
  thongBaoDemNguoc,
  layLoiTruong,
  xoaLoiTruong,
  xoaLoiHienThi,
  datLaiLoiBieuMau,
  apDungLoiApi,
} = suDungBieuMau(['email', 'password'])
const dangKhoaGui = computed(() => dangGui.value || dangBiGioiHan.value)

/**
 * Nhan submit login tu form dung chung va de page actor dieu phoi role sau /me.
 *
 * Dau vao: email va password hien tai cua nguoi dung.
 * Cach hoat dong: delegate xu ly cho actor page, hien normalized error neu that bai, xoa password sau success.
 * Ket qua: promise cua flow login; khong tu navigate va khong tu chon role.
 * Side effect: password chi nam trong component memory trong luc submit, khong persist.
 * Business Rule: khong retry, khong log credential va khong gui field role.
 */
async function xuLyGuiDangNhap() {
  if (dangKhoaGui.value) {
    return
  }

  xoaLoiHienThi()
  dangGui.value = true

  try {
    await props.dangXuLyDangNhap({ email: email.value, password: matKhau.value })
    matKhau.value = ''
    datLaiLoiBieuMau()
  } catch (error) {
    apDungLoiApi(error, 'Không thể đăng nhập. Vui lòng thử lại.')
  } finally {
    dangGui.value = false
  }
}
</script>

<template>
  <section aria-labelledby="tieu-de-dang-nhap">
    <h1 id="tieu-de-dang-nhap">
      {{ props.tieuDe }}
    </h1>
    <p>Đăng nhập bằng tài khoản được cấp quyền cho cổng Web.</p>

    <form
      :aria-busy="dangGui"
      @submit.prevent="xuLyGuiDangNhap"
    >
      <TruongBieuMau
        id="email-dang-nhap"
        nhan="Email"
        :bat-buoc="true"
        :loi="layLoiTruong('email')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <input
            :id="id"
            v-model="email"
            type="email"
            name="email"
            autocomplete="username"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
            required
            @input="xoaLoiTruong('email')"
          >
        </template>
      </TruongBieuMau>

      <TruongBieuMau
        id="mat-khau-dang-nhap"
        nhan="Mật khẩu"
        :bat-buoc="true"
        :loi="layLoiTruong('password')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <input
            :id="id"
            v-model="matKhau"
            type="password"
            name="password"
            autocomplete="current-password"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
            required
            @input="xoaLoiTruong('password')"
          >
        </template>
      </TruongBieuMau>

      <VungThongBao
        :danh-sach="[
          ...(loiTongQuat ? [{ id: 'loi-dang-nhap', kieu: 'nguy_hiem', noiDung: loiTongQuat }] : []),
          ...(thongBaoDemNguoc ? [{ id: 'dem-nguoc-dang-nhap', kieu: 'canh_bao', noiDung: thongBaoDemNguoc }] : []),
        ]"
      />
      <button
        class="nut nut--chinh"
        type="submit"
        :disabled="dangKhoaGui"
        :aria-busy="dangGui"
      >
        {{ dangGui ? 'Đang đăng nhập…' : dangBiGioiHan ? `Thử lại sau ${giayChoConLai} giây` : 'Đăng nhập' }}
      </button>
    </form>

    <p>
      <RouterLink :to="{ name: 'quenMatKhau' }">
        Quên mật khẩu?
      </RouterLink>
    </p>
    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò
      </RouterLink>
    </p>
  </section>
</template>
