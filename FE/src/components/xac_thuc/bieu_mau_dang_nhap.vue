<template>
  <section
    class="bieu-mau-dang-nhap"
    aria-labelledby="tieu-de-dang-nhap"
  >
    <div class="bieu-mau-dang-nhap__dau">
      <span
        class="bieu-mau-dang-nhap__dau-hieu"
        aria-hidden="true"
      >
        <svg
          viewBox="0 0 32 32"
          fill="none"
        >
          <path
            d="M7 6v20M25 6v20M4 11h6M22 11h6M4 21h6M22 21h6"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linecap="round"
          />
          <path
            d="M12 16h8"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linecap="round"
          />
        </svg>
      </span>
      <div>
        <p class="bieu-mau-dang-nhap__nhan">
          {{ props.vaiTroNhan || 'Cổng Web Smart Fitness' }}
        </p>
        <h1 id="tieu-de-dang-nhap">
          {{ props.tieuDe }}
        </h1>
      </div>
    </div>

    <p class="bieu-mau-dang-nhap__mo-ta">
      {{ props.moTa || 'Đăng nhập bằng tài khoản được cấp quyền cho cổng Web.' }}
    </p>

    <div
      class="bieu-mau-dang-nhap__tin-hieu"
      aria-label="Bảo mật phiên làm việc"
    >
      <span
        class="bieu-mau-dang-nhap__tin-hieu-cham"
        aria-hidden="true"
      />
      <span>Phiên làm việc được bảo vệ</span>
    </div>

    <form
      class="bieu-mau-dang-nhap__form"
      :aria-busy="dangGui"
      @submit.prevent="xuLyGuiDangNhap"
    >
      <TruongBieuMau
        id="email-dang-nhap"
        nhan="Email công việc"
        tro-giup="Sử dụng email đã được cấp quyền trong hệ thống."
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
            placeholder="ten.cua.ban@smartfitness.vn"
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
            placeholder="Nhập mật khẩu của bạn"
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
        class="nut nut--chinh bieu-mau-dang-nhap__nut-gui"
        type="submit"
        :disabled="dangKhoaGui"
        :aria-busy="dangGui"
      >
        <span>{{ dangGui ? 'Đang xác thực…' : dangBiGioiHan ? `Thử lại sau ${giayChoConLai} giây` : 'Vào không gian làm việc' }}</span>
        <svg
          v-if="!dangGui && !dangBiGioiHan"
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="M4 10h11M10 5l5 5-5 5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
    </form>

    <div class="bieu-mau-dang-nhap__lien-ket">
      <RouterLink :to="{ name: 'quenMatKhau' }">
        Quên mật khẩu?
      </RouterLink>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import TruongBieuMau from '../dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../dung_chung/vung_thong_bao.vue'
import { suDungBieuMau } from '../../composables/su_dung_bieu_mau.js'

defineOptions({ name: 'BieuMauDangNhap' })

const props = defineProps({
  tieuDe: { type: String, required: true },
  vaiTroNhan: { type: String, default: '' },
  moTa: { type: String, default: '' },
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

<style scoped>
.bieu-mau-dang-nhap {
  width: 100%;
}

.bieu-mau-dang-nhap__dau {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.bieu-mau-dang-nhap__dau-hieu {
  display: inline-grid;
  width: 3rem;
  height: 3rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 0.85rem;
  color: #07151d;
  background: #b8f34a;
  box-shadow: 0 0 0 5px rgb(184 243 74 / 12%);
}

.bieu-mau-dang-nhap__dau-hieu svg {
  width: 1.65rem;
  height: 1.65rem;
}

.bieu-mau-dang-nhap__nhan {
  margin: 0 0 0.35rem;
  color: #f9b36b;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  text-transform: uppercase;
}

.bieu-mau-dang-nhap h1 {
  margin: 0;
  color: #10212b;
  font-size: clamp(2rem, 4vw, 2.55rem);
  line-height: 1;
}

.bieu-mau-dang-nhap__mo-ta {
  max-width: 32rem;
  margin: 1.35rem 0 0;
  color: #5c6c75;
  line-height: 1.6;
}

.bieu-mau-dang-nhap__tin-hieu {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  margin-top: 1.15rem;
  color: #6c7d84;
  font-size: 0.78rem;
  font-weight: 700;
}

.bieu-mau-dang-nhap__tin-hieu-cham {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: #49c878;
  box-shadow: 0 0 0 4px rgb(73 200 120 / 16%);
}

.bieu-mau-dang-nhap__form {
  display: grid;
  gap: 1.1rem;
  margin-top: 2rem;
}

.bieu-mau-dang-nhap__nut-gui {
  width: 100%;
  min-height: 3.25rem;
  justify-content: space-between;
  padding-inline: 1.1rem 1.25rem;
}

.bieu-mau-dang-nhap__nut-gui svg {
  width: 1.25rem;
  height: 1.25rem;
}

.bieu-mau-dang-nhap__lien-ket {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin-top: 1.25rem;
  font-size: 0.86rem;
  font-weight: 700;
}

.bieu-mau-dang-nhap__lien-ket a:last-child {
  color: #6c7d84;
}

@media (max-width: 520px) {
  .bieu-mau-dang-nhap__dau-hieu {
    width: 2.65rem;
    height: 2.65rem;
  }

  .bieu-mau-dang-nhap__lien-ket {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
