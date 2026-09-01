<template>
  <section
    class="man-hinh-xac-thuc"
    aria-labelledby="tieu-de-quen-mat-khau"
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
            d="M7 10V8a5 5 0 0 1 10 0v2M5 10h14v10H5V10Z"
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
          Khôi phục quyền truy cập
        </p>
        <h1 id="tieu-de-quen-mat-khau">
          Quên mật khẩu?
        </h1>
      </div>
    </div>
    <p class="man-hinh-xac-thuc__mo-ta">
      Nhập email tài khoản. Nếu email tồn tại, hướng dẫn an toàn sẽ được gửi đến bạn.
    </p>

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
        class="nut nut--chinh man-hinh-xac-thuc__nut-gui"
        type="submit"
        :disabled="dangKhoaGui"
        :aria-busy="dangGui"
      >
        {{ dangGui ? 'Đang gửi…' : dangBiGioiHan ? `Thử lại sau ${giayChoConLai} giây` : 'Gửi hướng dẫn' }}
      </button>
    </form>

    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò làm việc
      </RouterLink>
    </p>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import TruongBieuMau from '../../dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../dung_chung/vung_thong_bao.vue'
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

<style scoped>
.man-hinh-xac-thuc {
  max-width: 32rem;
  margin: 0 auto;
  padding: 0.25rem 0;
}

.man-hinh-xac-thuc form {
  display: grid;
  gap: 1.1rem;
  margin-top: 2rem;
}

.man-hinh-xac-thuc__quay-lai {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #6c7d84;
  font-size: 0.84rem;
  font-weight: 700;
  text-decoration: none;
}

.man-hinh-xac-thuc__quay-lai:hover {
  color: #e66a2c;
}

.man-hinh-xac-thuc__quay-lai svg {
  width: 1rem;
  height: 1rem;
}

.man-hinh-xac-thuc__dau {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-top: 2rem;
}

.man-hinh-xac-thuc__icon {
  display: inline-grid;
  width: 3rem;
  height: 3rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 0.85rem;
  color: #10212b;
  background: #f9b36b;
}

.man-hinh-xac-thuc__icon svg {
  width: 1.5rem;
  height: 1.5rem;
}

.man-hinh-xac-thuc__nhan {
  margin: 0 0 0.35rem;
  color: #e66a2c;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

.man-hinh-xac-thuc h1 {
  margin: 0;
  color: #10212b;
  font-size: clamp(2rem, 4vw, 2.65rem);
  line-height: 1;
}

.man-hinh-xac-thuc__mo-ta {
  margin: 1.25rem 0 0;
  color: #62747b;
  line-height: 1.6;
}

.man-hinh-xac-thuc__nut-gui {
  width: 100%;
  min-height: 3.25rem;
}

.man-hinh-xac-thuc > p:last-child {
  margin-top: 1.4rem;
  font-size: 0.86rem;
  font-weight: 700;
}
</style>
