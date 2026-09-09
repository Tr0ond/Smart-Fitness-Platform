<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useHuanLuyenVienStore } from '../../../stores/huan_luyen_vien.store.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN } from '../../../services/huan_luyen_vien.api.js'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const taiKhoanStore = useTaiKhoanStore()
const hoSoStore = useHuanLuyenVienStore()
const { huanLuyenVienDaChon, dangTaiChiTietHuanLuyenVien, loiTaiChiTietHuanLuyenVien } = storeToRefs(taiKhoanStore)
const { hoSoDaChon, dangTaiHoSo, loiTaiHoSo, dangCapNhatHoSo, loiCapNhatHoSo, thongBaoCapNhatHoSo } = storeToRefs(hoSoStore)

const form = reactive({ introduction: '', specialties: '', status: '' })
let soThuTuTrang = 0

const NHAN_TRANG_THAI_ACCOUNT = Object.freeze({
  HOAT_DONG: 'Hoạt động',
  BI_KHOA: 'Bị khóa',
  NGUNG_HOAT_DONG: 'Ngừng hoạt động',
})
const NHAN_TRANG_THAI_PROFILE = Object.freeze({
  HOAT_DONG: 'Đang nhận phân công',
  NGUNG_NHAN_PHAN_CONG: 'Ngừng nhận phân công',
})

function layIdTuRoute() {
  return typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id
}

function layIdTaiKhoanTuRoute() {
  const id = Number(layIdTuRoute())
  return Number.isSafeInteger(id) && id > 0 ? id : null
}

function taoSoThuTuTrang() {
  soThuTuTrang += 1
  return soThuTuTrang
}

function conSoHuuTrang(soThuTu, accountId) {
  return soThuTu === soThuTuTrang && Number(layIdTuRoute()) === accountId
}

function laPtDangHoatDong(account) {
  return Array.isArray(account?.roles)
    && account.roles.some((role) => role?.code === 'PT' && role?.active === true)
}

function dongBoForm(profile) {
  form.introduction = profile?.introduction ?? ''
  form.specialties = profile?.specialties ?? ''
  form.status = CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN.includes(profile?.status) ? profile.status : ''
}

const coTheEdit = computed(() => hoSoDaChon.value !== null
  && CAC_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN.includes(hoSoDaChon.value.status))

const profileStatusLabel = computed(() => NHAN_TRANG_THAI_PROFILE[hoSoDaChon.value?.status] ?? 'Không xác định')
const accountStatusLabel = computed(() => NHAN_TRANG_THAI_ACCOUNT[huanLuyenVienDaChon.value?.status] ?? 'Không xác định')

function layLoiTruong(truong) {
  const errors = loiCapNhatHoSo.value?.fieldErrors?.[truong]
  return Array.isArray(errors) ? errors[0] ?? '' : ''
}

function coLoiTruong(truong) {
  return layLoiTruong(truong) !== ''
}

async function datFocusLoiCapNhat() {
  await datFocusVaoTruongLoiDau(
    loiCapNhatHoSo.value?.fieldErrors,
    ['introduction', 'specialties', 'status'],
    {
      introduction: 'trainer-profile-introduction',
      specialties: 'trainer-profile-specialties',
      status: 'trainer-profile-status',
    },
    'trainer-profile-update-error',
  )
}

const loiTai = computed(() => loiTaiChiTietHuanLuyenVien.value || loiTaiHoSo.value)
const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiTai.value,
  'Không thể tải hồ sơ Huấn luyện viên. Vui lòng thử lại sau.',
))
const coTheThuLai = computed(() => loiTai.value?.isNetworkError === true
  || (Number.isInteger(loiTai.value?.httpStatus) && loiTai.value.httpStatus >= 500))

async function taiDuLieu(soThuTu = taoSoThuTuTrang()) {
  const id = layIdTaiKhoanTuRoute()
  if (id === null) return

  const account = await taiKhoanStore.taiChiTietHuanLuyenVien(id)
  if (!conSoHuuTrang(soThuTu, id)) return
  if (loiTaiChiTietHuanLuyenVien.value?.httpStatus === 403) {
    taiKhoanStore.xoaDuLieuHuanLuyenVien()
    hoSoStore.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return
  }
  if (!account || Number(account.id) !== id || !laPtDangHoatDong(account)) return

  const profile = await hoSoStore.taiHoSoHuanLuyenVien(id)
  if (!conSoHuuTrang(soThuTu, id)) return
  if (loiTaiHoSo.value?.httpStatus === 403) {
    hoSoStore.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return
  }
  if (!profile || Number(profile.account_id) !== id) return
  dongBoForm(profile)
}

async function thuLai() {
  await taiDuLieu()
}

async function luuHoSo() {
  const accountId = layIdTaiKhoanTuRoute()
  const soThuTu = soThuTuTrang
  if (accountId === null
    || !conSoHuuTrang(soThuTu, accountId)
    || !coTheEdit.value
    || Number(hoSoDaChon.value?.account_id) !== accountId) return

  const profile = await hoSoStore.capNhatHoSoHuanLuyenVien(accountId, {
    introduction: form.introduction.trim() || null,
    specialties: form.specialties.trim() || null,
    status: form.status,
  })
  if (!conSoHuuTrang(soThuTu, accountId)) return

  const loiSauCapNhat = loiCapNhatHoSo.value
  if (loiSauCapNhat?.httpStatus === 403) {
    taiKhoanStore.xoaDuLieuHuanLuyenVien()
    hoSoStore.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return
  }
  if (loiSauCapNhat?.httpStatus === 422) {
    await datFocusLoiCapNhat()
    if (!conSoHuuTrang(soThuTu, accountId)) return
  }
  if (!profile || Number(profile.account_id) !== accountId) return
  if (!conSoHuuTrang(soThuTu, accountId)) return

  dongBoForm(profile)
  if (!conSoHuuTrang(soThuTu, accountId)) return
  const accountSauCapNhat = await taiKhoanStore.taiChiTietHuanLuyenVien(accountId)
  if (!conSoHuuTrang(soThuTu, accountId)
    || !accountSauCapNhat
    || Number(accountSauCapNhat.id) !== accountId) return

  await taiKhoanStore.taiDanhSachHuanLuyenVien({
    boLoc: taiKhoanStore.boLocHuanLuyenVien,
    trang: taiKhoanStore.phanTrangHuanLuyenVien.current_page,
  })
  if (!conSoHuuTrang(soThuTu, accountId)) return
}

watch(() => route.params.id, () => {
  const soThuTu = taoSoThuTuTrang()
  taiKhoanStore.xoaChiTietHuanLuyenVien()
  hoSoStore.xoaHoSoDangChon()
  void taiDuLieu(soThuTu)
})

onMounted(() => {
  void taiDuLieu()
})

onBeforeUnmount(() => {
  taoSoThuTuTrang()
  taiKhoanStore.xoaChiTietHuanLuyenVien()
  hoSoStore.xoaHoSoDangChon()
})
</script>

<template>
  <section
    class="trang-chi-tiet-huan-luyen-vien"
    aria-label="Chi tiết Huấn luyện viên"
    :aria-busy="dangTaiChiTietHuanLuyenVien || dangTaiHoSo"
  >
    <TieuDeTrang
      tieu-de="Chi tiết Huấn luyện viên"
      mo-ta="Account, role PT và hồ sơ PT được tải từ các nguồn authoritative độc lập."
    >
      <template #hanhDong>
        <RouterLink
          :to="{ name: 'adminHuanLuyenVien' }"
          class="nut nut--lien-ket"
        >
          Quay lại danh sách Huấn luyện viên
        </RouterLink>
      </template>
    </TieuDeTrang>

    <TrangThaiTaiDuLieu
      v-if="(dangTaiChiTietHuanLuyenVien || dangTaiHoSo) && !huanLuyenVienDaChon"
      nhan="Đang tải Account và hồ sơ PT…"
    />
    <TrangThaiLoi
      v-if="loiTai"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiChiTietHuanLuyenVien || dangTaiHoSo"
      @thu-lai="thuLai"
    />

    <template v-if="huanLuyenVienDaChon && laPtDangHoatDong(huanLuyenVienDaChon)">
      <section
        class="the-chi-tiet-huan-luyen-vien"
        aria-labelledby="tieu-de-account-pt"
      >
        <p class="the-chi-tiet-huan-luyen-vien__nhan-khu-vuc">
          ACCOUNT #{{ huanLuyenVienDaChon.id }} · PT
        </p>
        <h2 id="tieu-de-account-pt">
          {{ huanLuyenVienDaChon.name }}
        </h2>
        <p>{{ huanLuyenVienDaChon.email }}</p>
        <dl class="luoi-thuoc-tinh-huan-luyen-vien">
          <div><dt>ID tài khoản</dt><dd>{{ huanLuyenVienDaChon.id }}</dd></div>
          <div><dt>Chi nhánh</dt><dd>{{ huanLuyenVienDaChon.branch?.name ?? 'Chưa gán' }}</dd></div>
          <div><dt>Trạng thái Account</dt><dd>{{ accountStatusLabel }}</dd></div>
          <div><dt>Vai trò</dt><dd>PT đang hoạt động</dd></div>
        </dl>
      </section>

      <section
        v-if="hoSoDaChon"
        class="the-chi-tiet-huan-luyen-vien"
        aria-labelledby="tieu-de-ho-so-pt"
      >
        <div class="the-chi-tiet-huan-luyen-vien__dau">
          <div>
            <p class="the-chi-tiet-huan-luyen-vien__nhan-khu-vuc">
              HỒ SƠ PT AUTHORITATIVE
            </p>
            <h2 id="tieu-de-ho-so-pt">
              Hồ sơ PT
            </h2>
          </div>
          <HuyHieuTrangThai
            :trang-thai="coTheEdit ? 'thanh_cong' : 'trung_tinh'"
            :nhan="profileStatusLabel"
          />
        </div>
        <dl class="luoi-thuoc-tinh-huan-luyen-vien">
          <div><dt>ID hồ sơ</dt><dd>{{ hoSoDaChon.trainer_profile_id }}</dd></div>
          <div><dt>Mã PT</dt><dd>{{ hoSoDaChon.trainer_code }}</dd></div>
          <div><dt>Cập nhật</dt><dd>{{ hoSoDaChon.updated_at }}</dd></div>
        </dl>

        <form
          v-if="coTheEdit"
          class="bieu-mau-admin"
          @submit.prevent="luuHoSo"
        >
          <div class="truong-bieu-mau">
            <label for="trainer-profile-introduction">Giới thiệu</label>
            <textarea
              id="trainer-profile-introduction"
              v-model="form.introduction"
              name="introduction"
              maxlength="16383"
              rows="4"
              :disabled="dangCapNhatHoSo"
              :aria-invalid="coLoiTruong('introduction')"
              :aria-describedby="coLoiTruong('introduction') ? 'trainer-profile-introduction-error' : undefined"
              @input="hoSoStore.danhDauThayDoiNoiDungCapNhat"
            />
            <small
              v-if="coLoiTruong('introduction')"
              id="trainer-profile-introduction-error"
              role="alert"
            >{{ layLoiTruong('introduction') }}</small>
          </div>
          <div class="truong-bieu-mau">
            <label for="trainer-profile-specialties">Chuyên môn</label>
            <input
              id="trainer-profile-specialties"
              v-model="form.specialties"
              name="specialties"
              maxlength="255"
              :disabled="dangCapNhatHoSo"
              :aria-invalid="coLoiTruong('specialties')"
              :aria-describedby="coLoiTruong('specialties') ? 'trainer-profile-specialties-error' : undefined"
              @input="hoSoStore.danhDauThayDoiNoiDungCapNhat"
            >
            <small
              v-if="coLoiTruong('specialties')"
              id="trainer-profile-specialties-error"
              role="alert"
            >{{ layLoiTruong('specialties') }}</small>
          </div>
          <div class="truong-bieu-mau">
            <label for="trainer-profile-status">Trạng thái hồ sơ</label>
            <select
              id="trainer-profile-status"
              v-model="form.status"
              name="status"
              :disabled="dangCapNhatHoSo"
              :aria-invalid="coLoiTruong('status')"
              :aria-describedby="coLoiTruong('status') ? 'trainer-profile-status-error' : undefined"
              @change="hoSoStore.danhDauThayDoiNoiDungCapNhat"
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
              id="trainer-profile-status-error"
              role="alert"
            >{{ layLoiTruong('status') }}</small>
          </div>
          <button
            class="nut nut--chinh"
            type="submit"
            :disabled="dangCapNhatHoSo"
          >
            {{ dangCapNhatHoSo ? 'Đang lưu…' : 'Lưu hồ sơ PT' }}
          </button>
          <p
            v-if="thongBaoCapNhatHoSo"
            role="status"
          >
            {{ thongBaoCapNhatHoSo }}
          </p>
          <p
            v-if="loiCapNhatHoSo"
            id="trainer-profile-update-error"
            role="alert"
            tabindex="-1"
          >
            {{ layThongBaoLoiApi(loiCapNhatHoSo, 'Không thể cập nhật hồ sơ PT.') }}
          </p>
        </form>
      </section>
      <p
        v-else-if="!dangTaiHoSo"
        class="trang-thai-loi"
      >
        Chưa có hồ sơ PT authoritative để chỉnh sửa.
      </p>
    </template>
  </section>
</template>
