<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import { suDungBieuMau } from '../../../composables/su_dung_bieu_mau.js'
import { guiYeuCauDatLaiMatKhau as guiYeuCauDatLaiMatKhauApi } from '../../../services/xac_thuc.api.js'

const email = ref('')
const thongBao = ref('')
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
} = suDungBieuMau(['email'])
const dangKhoaGui = computed(() => dangGui.value || dangBiGioiHan.value)

/**
 * Gui yeu cau reset voi UX tong quat, khong tiet lo email co ton tai.
 *
 * Dau vao: email tu form cong khai.
 * Cach hoat dong: goi exact forgot-password contract, hien cung thong bao cho email biet/khong biet khi Backend thanh cong.
 * Ket qua: trang thai thong bao generic; loi 422/429/5xx/network dung normalized error.
 * Side effect: xoa email khoi form sau submit thanh cong, khong luu reset token hay password.
 * Business Rule: khong enumeration, khong retry va khong them idempotency key.
 */
async function guiYeuCauDatLaiMatKhau() {
  if (dangKhoaGui.value) {
    return
  }

  thongBao.value = ''
  xoaLoiHienThi()
  dangGui.value = true

  try {
    await guiYeuCauDatLaiMatKhauApi(email.value)
    email.value = ''
    datLaiLoiBieuMau()
    thongBao.value = 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.'
  } catch (error) {
    apDungLoiApi(error, 'Không thể tiếp nhận yêu cầu. Vui lòng thử lại sau.')
  } finally {
    dangGui.value = false
  }
}
</script>

<template>
  <section aria-labelledby="tieu-de-quen-mat-khau">
    <h1 id="tieu-de-quen-mat-khau">
      Quên mật khẩu
    </h1>
    <p>Nhập email tài khoản. Nếu email tồn tại, hướng dẫn sẽ được gửi đến bạn.</p>

    <form
      :aria-busy="dangGui"
      @submit.prevent="guiYeuCauDatLaiMatKhau"
    >
      <TruongBieuMau
        id="email-quen-mat-khau"
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
      <VungThongBao
        :danh-sach="[
          ...(loiTongQuat ? [{ id: 'loi-quen-mat-khau', kieu: 'nguy_hiem', noiDung: loiTongQuat }] : []),
          ...(thongBaoDemNguoc ? [{ id: 'dem-nguoc-quen-mat-khau', kieu: 'canh_bao', noiDung: thongBaoDemNguoc }] : []),
          ...(thongBao ? [{ id: 'thong-bao-quen-mat-khau', kieu: 'thanh_cong', noiDung: thongBao }] : []),
        ]"
      />
      <button
        class="nut nut--chinh"
        type="submit"
        :disabled="dangKhoaGui"
        :aria-busy="dangGui"
      >
        {{ dangGui ? 'Đang gửi…' : dangBiGioiHan ? `Thử lại sau ${giayChoConLai} giây` : 'Gửi hướng dẫn' }}
      </button>
    </form>

    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò
      </RouterLink>
    </p>
  </section>
</template>
