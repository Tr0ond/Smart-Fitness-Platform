<script setup>
import { computed, onBeforeUnmount, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import { useHuanLuyenVienStore } from '../../../stores/huan_luyen_vien.store.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN } from '../../../services/huan_luyen_vien.api.js'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = useHuanLuyenVienStore()
const taiKhoanStore = useTaiKhoanStore()
const { dangOnboarding, loiOnboarding, ketQuaOnboarding } = storeToRefs(store)
const { danhSachTaiKhoan, dangTai, daTaiLanDau } = storeToRefs(taiKhoanStore)

const mode = reactive({ value: 'new' })
const form = reactive({
  name: '', email: '', phone: '', account_id: '', introduction: '', specialties: '', status: 'HOAT_DONG',
})
const hienThiXacNhan = reactive({ value: false })

const accountsCoTheChon = computed(() => danhSachTaiKhoan.value.filter((account) => (
  Number.isSafeInteger(Number(account?.id))
  && Number(account.id) > 0
  && !account.roles?.some((role) => role?.code === 'PT' && role?.active === true)
)))

function doiMode(nextMode) {
  if (mode.value === nextMode) return
  mode.value = nextMode
  store.huyOnboarding()
  form.name = ''
  form.email = ''
  form.phone = ''
  form.account_id = ''
  form.introduction = ''
  form.specialties = ''
  form.status = 'HOAT_DONG'
  void taiTaiKhoanChoModeExisting()
}

function layDuLieu() {
  if (mode.value === 'existing') {
    return {
      account_id: Number(form.account_id),
      introduction: form.introduction || null,
      specialties: form.specialties || null,
      status: form.status,
    }
  }

  return {
    name: form.name.trim(),
    email: form.email.trim(),
    phone: form.phone.trim() || null,
    introduction: form.introduction || null,
    specialties: form.specialties || null,
    status: form.status,
  }
}

const moTaiKhoan = computed(() => {
  const id = ketQuaOnboarding.value?.account?.id
  return Number.isSafeInteger(Number(id)) && Number(id) > 0
    ? { name: 'adminChiTietTaiKhoan', params: { id: String(id) } }
    : { name: 'adminHuanLuyenVien' }
})

async function taiTaiKhoanChoModeExisting() {
  if (mode.value === 'existing' && !daTaiLanDau.value && !dangTai.value) {
    await taiKhoanStore.taiDanhSachTaiKhoan({ trang: 1 })
  }
}

function moHopThoai() {
  hienThiXacNhan.value = true
}

function huyHopThoai() {
  hienThiXacNhan.value = false
}

async function xacNhanOnboarding() {
  hienThiXacNhan.value = false
  const ketQua = await store.taoMoiHuanLuyenVien({ mode: mode.value, duLieu: layDuLieu() })
  const loiSauOnboarding = loiOnboarding.value
  if (loiSauOnboarding?.httpStatus === 403) {
    store.xoaDuLieu()
    taiKhoanStore.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return
  }
  if (loiSauOnboarding?.httpStatus === 422) {
    await datFocusLoiOnboarding()
  }
  if (ketQua !== null) {
    await taiKhoanStore.taiDanhSachHuanLuyenVien({
      boLoc: taiKhoanStore.boLocHuanLuyenVien,
      trang: taiKhoanStore.phanTrangHuanLuyenVien.current_page,
    })
  }
}

function layLoiTruong(truong) {
  const errors = loiOnboarding.value?.fieldErrors?.[truong]
  return Array.isArray(errors) ? errors[0] ?? '' : ''
}

function layThuTuTruongTheoMode() {
  return mode.value === 'new'
    ? ['name', 'email', 'phone', 'introduction', 'specialties', 'status']
    : ['account_id', 'introduction', 'specialties', 'status']
}

function coLoiTruong(truong) {
  return layLoiTruong(truong) !== ''
}

async function datFocusLoiOnboarding() {
  await datFocusVaoTruongLoiDau(
    loiOnboarding.value?.fieldErrors,
    layThuTuTruongTheoMode(),
    {
      account_id: 'trainer-account',
      name: 'trainer-name',
      email: 'trainer-email',
      phone: 'trainer-phone',
      introduction: 'trainer-introduction',
      specialties: 'trainer-specialties',
      status: 'trainer-status',
    },
    'trainer-onboarding-loi',
  )
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiOnboarding.value,
  'Không thể onboarding Huấn luyện viên. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => loiOnboarding.value?.isNetworkError === true
  || (Number.isInteger(loiOnboarding.value?.httpStatus) && loiOnboarding.value.httpStatus >= 500))

onMounted(() => {
  void taiTaiKhoanChoModeExisting()
})

onBeforeUnmount(() => {
  store.huyOnboarding()
})
</script>

<template>
  <section
    class="trang-tao-moi-huan-luyen-vien"
    aria-label="Tạo mới Huấn luyện viên"
    :aria-busy="dangOnboarding"
  >
    <TieuDeTrang
      tieu-de="Tạo mới Huấn luyện viên"
      mo-ta="Onboarding account hoặc gắn hồ sơ PT cho account hiện hữu trong đúng chi nhánh."
    />

    <div
      class="bo-chuyen-che-do"
      role="group"
      aria-label="Chọn chế độ onboarding"
    >
      <button
        class="nut"
        :class="{ 'nut--chinh': mode.value === 'new' }"
        type="button"
        @click="doiMode('new')"
      >
        Account mới
      </button>
      <button
        class="nut"
        :class="{ 'nut--chinh': mode.value === 'existing' }"
        type="button"
        @click="doiMode('existing')"
      >
        Account hiện hữu
      </button>
    </div>

    <form
      class="bieu-mau-admin"
      @submit.prevent="moHopThoai"
    >
      <div
        v-if="mode.value === 'existing'"
        class="truong-bieu-mau"
      >
        <label for="trainer-account">Account hiện hữu</label>
        <select
          id="trainer-account"
          v-model="form.account_id"
          name="account_id"
          required
          :disabled="dangOnboarding"
          :aria-invalid="coLoiTruong('account_id')"
          :aria-describedby="coLoiTruong('account_id') ? 'trainer-account-error' : undefined"
          @change="store.danhDauThayDoiNoiDungOnboarding"
        >
          <option value="">
            Chọn account
          </option>
          <option
            v-for="account in accountsCoTheChon"
            :key="account.id"
            :value="account.id"
          >
            {{ account.name }} · {{ account.email }}
          </option>
        </select>
        <small
          v-if="coLoiTruong('account_id')"
          id="trainer-account-error"
          role="alert"
        >
          {{ layLoiTruong('account_id') }}
        </small>
        <small v-if="accountsCoTheChon.length === 0 && daTaiLanDau">Không có account đủ điều kiện.</small>
      </div>

      <div
        v-if="mode.value === 'new'"
        class="luoi-bieu-mau"
      >
        <div class="truong-bieu-mau">
          <label for="trainer-name">Họ tên</label>
          <input
            id="trainer-name"
            v-model="form.name"
            name="name"
            required
            maxlength="150"
            :disabled="dangOnboarding"
            :aria-invalid="coLoiTruong('name')"
            :aria-describedby="coLoiTruong('name') ? 'trainer-name-error' : undefined"
            @input="store.danhDauThayDoiNoiDungOnboarding"
          >
          <small
            v-if="coLoiTruong('name')"
            id="trainer-name-error"
            role="alert"
          >{{ layLoiTruong('name') }}</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="trainer-email">Email</label>
          <input
            id="trainer-email"
            v-model="form.email"
            name="email"
            type="email"
            required
            maxlength="254"
            :disabled="dangOnboarding"
            :aria-invalid="coLoiTruong('email')"
            :aria-describedby="coLoiTruong('email') ? 'trainer-email-error' : undefined"
            @input="store.danhDauThayDoiNoiDungOnboarding"
          >
          <small
            v-if="coLoiTruong('email')"
            id="trainer-email-error"
            role="alert"
          >{{ layLoiTruong('email') }}</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="trainer-phone">Điện thoại</label>
          <input
            id="trainer-phone"
            v-model="form.phone"
            name="phone"
            maxlength="20"
            :disabled="dangOnboarding"
            :aria-invalid="coLoiTruong('phone')"
            :aria-describedby="coLoiTruong('phone') ? 'trainer-phone-error' : undefined"
            @input="store.danhDauThayDoiNoiDungOnboarding"
          >
          <small
            v-if="coLoiTruong('phone')"
            id="trainer-phone-error"
            role="alert"
          >{{ layLoiTruong('phone') }}</small>
        </div>
      </div>

      <div class="truong-bieu-mau">
        <label for="trainer-introduction">Giới thiệu</label>
        <textarea
          id="trainer-introduction"
          v-model="form.introduction"
          name="introduction"
          maxlength="16383"
          rows="4"
          :disabled="dangOnboarding"
          :aria-invalid="coLoiTruong('introduction')"
          :aria-describedby="coLoiTruong('introduction') ? 'trainer-introduction-error' : undefined"
          @input="store.danhDauThayDoiNoiDungOnboarding"
        />
        <small
          v-if="coLoiTruong('introduction')"
          id="trainer-introduction-error"
          role="alert"
        >{{ layLoiTruong('introduction') }}</small>
      </div>
      <div class="truong-bieu-mau">
        <label for="trainer-specialties">Chuyên môn</label>
        <input
          id="trainer-specialties"
          v-model="form.specialties"
          name="specialties"
          maxlength="255"
          :disabled="dangOnboarding"
          :aria-invalid="coLoiTruong('specialties')"
          :aria-describedby="coLoiTruong('specialties') ? 'trainer-specialties-error' : undefined"
          @input="store.danhDauThayDoiNoiDungOnboarding"
        >
        <small
          v-if="coLoiTruong('specialties')"
          id="trainer-specialties-error"
          role="alert"
        >{{ layLoiTruong('specialties') }}</small>
      </div>
      <div class="truong-bieu-mau">
        <label for="trainer-status">Trạng thái hồ sơ</label>
        <select
          id="trainer-status"
          v-model="form.status"
          name="status"
          :disabled="dangOnboarding"
          :aria-invalid="coLoiTruong('status')"
          :aria-describedby="coLoiTruong('status') ? 'trainer-status-error' : undefined"
          @change="store.danhDauThayDoiNoiDungOnboarding"
        >
          <option
            v-for="status in CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN"
            :key="status"
            :value="status"
          >
            {{ status === 'HOAT_DONG' ? 'Đang nhận phân công' : 'Ngừng nhận phân công' }}
          </option>
        </select>
        <small
          v-if="coLoiTruong('status')"
          id="trainer-status-error"
          role="alert"
        >{{ layLoiTruong('status') }}</small>
      </div>
      <button
        class="nut nut--chinh"
        type="submit"
        :disabled="dangOnboarding"
      >
        {{ dangOnboarding ? 'Đang xử lý…' : 'Lưu onboarding' }}
      </button>
    </form>

    <TrangThaiTaiDuLieu
      v-if="mode.value === 'existing' && dangTai && !daTaiLanDau"
      nhan="Đang tải account đủ điều kiện…"
    />
    <TrangThaiLoi
      v-if="loiOnboarding"
      id="trainer-onboarding-loi"
      tabindex="-1"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangOnboarding"
      @thu-lai="xacNhanOnboarding"
    />

    <section
      v-if="ketQuaOnboarding"
      class="the-ket-qua-onboarding"
      aria-live="polite"
    >
      <h2>Onboarding đã được Backend xác nhận</h2>
      <p>Invitation: {{ ketQuaOnboarding.invitation === 'QUEUED' ? 'QUEUED' : 'NOT_REQUESTED' }}</p>
      <p>Không có tuyên bố rằng email đã được gửi; trạng thái chỉ phản ánh Backend.</p>
      <RouterLink
        :to="moTaiKhoan"
        class="nut nut--lien-ket"
      >
        Mở Account detail
      </RouterLink>
    </section>

    <HopThoaiXacNhan
      :hien-thi="hienThiXacNhan"
      tieu-de="Xác nhận onboarding PT"
      mo-ta="Backend sẽ tạo hoặc cập nhật role/profile. Invitation chỉ hiển thị trạng thái QUEUED hoặc NOT_REQUESTED do Backend trả về."
      nhan-xac-nhan="Xác nhận"
      :dang-xu-ly="dangOnboarding"
      @xac-nhan="xacNhanOnboarding"
      @huy="huyHopThoai"
    />
  </section>
</template>
