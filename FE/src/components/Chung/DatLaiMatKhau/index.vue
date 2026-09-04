<template>
  <section
    class="man-hinh-xac-thuc"
    aria-labelledby="tieu-de-dat-lai-mat-khau"
  >
    <RouterLink
      class="man-hinh-xac-thuc__quay-lai"
      :to="{ name: 'chonVaiTro' }"
    >
      <svg
        viewBox="0 0 20 20"
        fill="none"
        aria-hidden="true"
      >
        <path
          d="M15 10H5M9 5l-5 5 5 5"
          stroke="currentColor"
          stroke-width="1.7"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
      Cổng Web
    </RouterLink>

    <div class="man-hinh-xac-thuc__dau">
      <span
        class="man-hinh-xac-thuc__icon"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
        >
          <path
            d="M12 3a5 5 0 0 1 5 5v2H7V8a5 5 0 0 1 5-5Z"
            stroke="currentColor"
            stroke-width="1.7"
          />
          <path
            d="M5 10h14v10H5V10Z"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linejoin="round"
          />
          <path
            d="M12 14v2"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
          />
        </svg>
      </span>
      <div>
        <p class="man-hinh-xac-thuc__nhan">
          Bảo vệ tài khoản
        </p>
        <h1 id="tieu-de-dat-lai-mat-khau">
          Đặt lại mật khẩu
        </h1>
      </div>
    </div>

    <p class="man-hinh-xac-thuc__mo-ta">
      Tạo một mật khẩu mới để tiếp tục sử dụng không gian làm việc của bạn.
    </p>

    <template v-if="!coThamSoHopLe">
      <VungThongBao
        :danh-sach="[{ id: 'loi-tham-so-dat-lai', kieu: 'nguy_hiem', noiDung: 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.' }]"
      />
    </template>

    <form
      v-else
      :aria-busy="dangGui"
      @submit.prevent="datLaiMatKhau"
    >
      <TruongBieuMau
        id="mat-khau-moi"
        nhan="Mật khẩu mới"
        :bat-buoc="true"
        :loi="layLoiTruong('password')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <input
            :id="id"
            v-model="matKhau"
            type="password"
            name="password"
            autocomplete="new-password"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
            required
            @input="xoaLoiTruong('password')"
          >
        </template>
      </TruongBieuMau>

      <TruongBieuMau
        id="xac-nhan-mat-khau-moi"
        nhan="Xác nhận mật khẩu mới"
        :bat-buoc="true"
        :loi="layLoiTruong('password_confirmation')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <input
            :id="id"
            v-model="xacNhanMatKhau"
            type="password"
            name="password_confirmation"
            autocomplete="new-password"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
            required
            @input="xoaLoiTruong('password_confirmation')"
          >
        </template>
      </TruongBieuMau>

      <VungThongBao
        :danh-sach="[
          ...(loiTongQuat ? [{ id: 'loi-dat-lai-mat-khau', kieu: 'nguy_hiem', noiDung: loiTongQuat }] : []),
          ...(thongBaoDemNguoc ? [{ id: 'dem-nguoc-dat-lai-mat-khau', kieu: 'canh_bao', noiDung: thongBaoDemNguoc }] : []),
          ...(thongBao ? [{ id: 'thong-bao-dat-lai-mat-khau', kieu: 'thanh_cong', noiDung: thongBao }] : []),
        ]"
      />
      <button
        class="nut nut--chinh man-hinh-xac-thuc__nut-gui"
        type="submit"
        :disabled="dangKhoaGui"
        :aria-busy="dangGui"
      >
        {{ dangGui ? 'Đang cập nhật…' : dangBiGioiHan ? `Thử lại sau ${giayChoConLai} giây` : 'Đặt lại mật khẩu' }}
      </button>
    </form>

    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò
      </RouterLink>
    </p>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import TruongBieuMau from '../../dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../dung_chung/vung_thong_bao.vue'
import { suDungBieuMau } from '../../../composables/su_dung_bieu_mau.js'
import { datLaiMatKhau as datLaiMatKhauApi } from '../../../services/xac_thuc.api.js'

const route = useRoute()
const router = useRouter()
const matKhau = ref('')
const xacNhanMatKhau = ref('')
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
} = suDungBieuMau(['password', 'password_confirmation'])
const dangKhoaGui = computed(() => dangGui.value || dangBiGioiHan.value)

function layTokenDatLai() {
  return typeof route.query.token === 'string' ? route.query.token : ''
}

/**
 * Kiem tra reset token tu query ma khong luu hoac ghi log token.
 *
 * Dau vao: query token cua route hien tai.
 * Cach hoat dong: chi chap nhan chuoi hex 64 ky tu dung format Backend validation.
 * Ket qua: boolean de chan submit khi thieu/sai token.
 * Side effect: khong goi API, khong persist token va khong expose token ra UI.
 * Business Rule: token la credential nhay cam, chi duoc dung mot lan trong request reset.
 */
function kiemTraThamSoDatLai() {
  return /^[0-9a-f]{64}$/.test(layTokenDatLai())
}

const coThamSoHopLe = computed(() => kiemTraThamSoDatLai())

/**
 * Gui password moi theo exact reset contract va xoa state nhay cam sau thanh cong.
 *
 * Dau vao: token hop le tu query, password va password_confirmation tu form.
 * Cach hoat dong: goi Backend reset-password; khong gui email, user id hay raw redirect.
 * Ket qua: thong bao loi normalized hoac thay doi route ve neutral auth entry sau success.
 * Side effect: xoa password local, thay URL de loai query token va khong luu token vao storage.
 * Business Rule: khong retry; Backend consume token mot lan va revoke cac phien cu.
 */
async function datLaiMatKhau() {
  if (dangKhoaGui.value) {
    return
  }

  thongBao.value = ''
  xoaLoiHienThi()

  if (!coThamSoHopLe.value) {
    loiTongQuat.value = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
    return
  }

  dangGui.value = true

  try {
    await datLaiMatKhauApi({
      token: layTokenDatLai(),
      password: matKhau.value,
      password_confirmation: xacNhanMatKhau.value,
    })
    matKhau.value = ''
    xacNhanMatKhau.value = ''
    datLaiLoiBieuMau()
    thongBao.value = 'Đặt lại mật khẩu thành công.'
    await router.replace({ name: 'chonVaiTro' })
  } catch (error) {
    apDungLoiApi(error, 'Không thể đặt lại mật khẩu. Vui lòng thử lại sau.')
  } finally {
    dangGui.value = false
  }
}
</script>

<style scoped>
.man-hinh-xac-thuc {
  max-width: 32rem;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.man-hinh-xac-thuc__quay-lai {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.man-hinh-xac-thuc__quay-lai svg {
  width: 1.25rem;
  height: 1.25rem;
  flex: 0 0 1.25rem;
}

.man-hinh-xac-thuc__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.man-hinh-xac-thuc__icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.man-hinh-xac-thuc form {
  display: grid;
  gap: 1rem;
}
</style>
