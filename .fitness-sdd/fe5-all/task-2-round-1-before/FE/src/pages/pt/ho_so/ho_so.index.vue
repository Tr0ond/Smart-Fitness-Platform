<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import {
  capNhatHoSoCaNhanHuanLuyenVien,
  taiHoSoCaNhanHuanLuyenVien,
} from '../../../services/huan_luyen_vien.api.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const hoSo = ref(null)
const dangTai = ref(false)
const dangLuu = ref(false)
const loi = ref(null)
const loiLuu = ref(null)
const thongBao = ref('')
const daTaiLanDau = ref(false)
const banNhap = reactive({ introduction: '', specialties: '' })

const coTheThuLai = computed(() => loi.value?.isNetworkError === true
  || (Number.isInteger(loi.value?.httpStatus) && loi.value.httpStatus >= 500))

function layLoiTruong(tenTruong) {
  const cacLoi = loiLuu.value?.fieldErrors?.[tenTruong]
  return Array.isArray(cacLoi) ? cacLoi[0] ?? '' : ''
}

function ganBanNhap(duLieu) {
  banNhap.introduction = typeof duLieu?.introduction === 'string' ? duLieu.introduction : ''
  banNhap.specialties = typeof duLieu?.specialties === 'string' ? duLieu.specialties : ''
}

async function taiHoSo() {
  dangTai.value = true
  loi.value = null
  try {
    const duLieu = await taiHoSoCaNhanHuanLuyenVien()
    hoSo.value = duLieu
    ganBanNhap(duLieu)
    daTaiLanDau.value = true
  } catch (error) {
    loi.value = error
    daTaiLanDau.value = true
  } finally {
    dangTai.value = false
  }
}

async function luuHoSo() {
  if (dangLuu.value) return
  dangLuu.value = true
  loiLuu.value = null
  thongBao.value = ''
  try {
    const duLieu = await capNhatHoSoCaNhanHuanLuyenVien({ ...banNhap })
    hoSo.value = duLieu
    ganBanNhap(duLieu)
    thongBao.value = 'Đã cập nhật hồ sơ cá nhân.'
  } catch (error) {
    loiLuu.value = error
    // PATCH timeout không được gửi lại tự động; GET reconcile không làm mất draft.
    if (laLoiTamThoi(error)) {
      await doiChieuHoSo()
    }
  } finally {
    dangLuu.value = false
  }
}

function moTaLoiTai() {
  return layThongBaoLoiApi(loi.value, 'Không thể tải hồ sơ cá nhân. Vui lòng thử lại sau.')
}

function moTaLoiLuu() {
  return layThongBaoLoiApi(loiLuu.value, 'Không thể cập nhật hồ sơ cá nhân. Vui lòng kiểm tra lại.')
}

function laLoiTamThoi(error) {
  return error?.isNetworkError === true
    || (Number.isInteger(error?.httpStatus) && error.httpStatus >= 500)
}

async function doiChieuHoSo() {
  try {
    hoSo.value = await taiHoSoCaNhanHuanLuyenVien()
  } catch {
    // Giữ draft và lỗi đã chuẩn hóa; không gửi lại PATCH ngoài ý muốn.
  }
}

onMounted(() => {
  void taiHoSo()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-ho-so"
    aria-label="Hồ sơ cá nhân huấn luyện viên"
    :aria-busy="dangTai || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Hồ sơ cá nhân"
      mo-ta="Cập nhật giới thiệu và chuyên môn hiển thị trong phạm vi hồ sơ PT của bạn."
    />

    <TrangThaiTaiDuLieu
      v-if="dangTai && !daTaiLanDau"
      nhan="Đang tải hồ sơ cá nhân…"
    />

    <TrangThaiLoi
      v-if="loi"
      :thong-bao="moTaLoiTai()"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTai"
      @thu-lai="taiHoSo"
    />

    <form
      v-if="daTaiLanDau && (!loi || hoSo)"
      class="pt-form pt-card"
      novalidate
      @submit.prevent="luuHoSo"
    >
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">PROFILE</p>
          <h2>Thông tin chuyên môn</h2>
        </div>
        <p v-if="hoSo?.trainer_code" class="pt-card__phu-de">
          Mã PT: {{ hoSo.trainer_code }}
        </p>
      </div>

      <TruongBieuMau
        id="pt-ho-so-introduction"
        nhan="Giới thiệu"
        tro-giup="Tối đa 2.000 ký tự."
        :loi="layLoiTruong('introduction')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <textarea
            :id="id"
            v-model="banNhap.introduction"
            name="introduction"
            maxlength="2000"
            rows="6"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
          />
        </template>
      </TruongBieuMau>

      <TruongBieuMau
        id="pt-ho-so-specialties"
        nhan="Chuyên môn"
        tro-giup="Nhập các chuyên môn theo nội dung bạn muốn giới thiệu."
        :loi="layLoiTruong('specialties')"
      >
        <template #default="{ id, ariaDescribedby, ariaInvalid }">
          <input
            :id="id"
            v-model="banNhap.specialties"
            name="specialties"
            maxlength="500"
            type="text"
            :aria-describedby="ariaDescribedby"
            :aria-invalid="ariaInvalid"
          >
        </template>
      </TruongBieuMau>

      <VungThongBao
        v-if="loiLuu"
        :danh-sach="[{ kieu: 'nguy_hiem', noiDung: moTaLoiLuu() }]"
      />
      <VungThongBao
        v-if="thongBao"
        :danh-sach="[{ kieu: 'thanh_cong', noiDung: thongBao }]"
      />

      <div class="pt-form__hanh-dong">
        <button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
          :aria-busy="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Lưu hồ sơ' }}
        </button>
      </div>
    </form>
  </section>
</template>
