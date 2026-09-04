<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import {
  CAC_TRANG_THAI_TAI_KHOAN,
  CAC_VAI_TRO_TAI_KHOAN,
} from '../../../services/tai_khoan.api.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useTaiKhoanStore()
const {
  taiKhoanDaChon,
  dangTaiChiTiet,
  loiTaiChiTiet,
  dangCapNhatTrangThai,
  loiCapNhatTrangThai,
  thongBaoCapNhatTrangThai,
  dangThayDoiVaiTro,
  vaiTroDangXuLy,
  loiThayDoiVaiTro,
  thongBaoThayDoiVaiTro,
} = storeToRefs(store)

const trangThaiMoi = reactive({ giaTri: '' })
const vaiTroMoi = reactive({ giaTri: '' })
const hienThiXacNhan = reactive({ giaTri: false, loai: 'TRANG_THAI', maVaiTro: '' })

const NHAN_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'Hoạt động',
  BI_KHOA: 'Bị khóa',
  NGUNG_HOAT_DONG: 'Ngừng hoạt động',
})

const NHAN_VAI_TRO = Object.freeze({
  MEMBER: 'Hội viên',
  PT: 'Huấn luyện viên',
  RECEPTIONIST: 'Nhân viên lễ tân',
  ADMIN: 'Quản trị viên',
})

const KIEU_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'thanh_cong',
  BI_KHOA: 'nguy_hiem',
  NGUNG_HOAT_DONG: 'canh_bao',
})

const NHAN_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN = Object.freeze({
  HOAT_DONG: 'Đang nhận phân công',
  NGUNG_NHAN_PHAN_CONG: 'Ngừng nhận phân công',
})

function layIdTuRoute() {
  return typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id
}

function layNhanTrangThai(trangThai) {
  return NHAN_TRANG_THAI[trangThai] ?? 'Không xác định'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
}

function layNhanVaiTro(maVaiTro) {
  return NHAN_VAI_TRO[maVaiTro] ?? 'Vai trò không xác định'
}

function layNhanTrangThaiHoSoHuanLuyenVien(trangThai) {
  return NHAN_TRANG_THAI_HO_SO_HUAN_LUYEN_VIEN[trangThai] ?? 'Không xác định'
}

function layVaiTroHienThi(taiKhoan) {
  return Array.isArray(taiKhoan?.roles)
    ? taiKhoan.roles.filter((vaiTro) => typeof vaiTro?.code === 'string')
    : []
}

function layVaiTroTheoMa(taiKhoan, maVaiTro) {
  return layVaiTroHienThi(taiKhoan).find((vaiTro) => vaiTro.code === maVaiTro) ?? null
}

const vaiTroChuaGan = computed(() => CAC_VAI_TRO_TAI_KHOAN.filter((maVaiTro) => (
  layVaiTroTheoMa(taiKhoanDaChon.value, maVaiTro) === null
)))

function dinhDangThoiGian(thoiGian) {
  if (typeof thoiGian !== 'string' || Number.isNaN(Date.parse(thoiGian))) {
    return 'Chưa có dữ liệu'
  }

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(thoiGian))
}

function layLoiTruong(tenTruong) {
  const danhSach = loiCapNhatTrangThai.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layLoiTruongVaiTro(tenTruong) {
  const danhSach = loiThayDoiVaiTro.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

const thongBaoLoiChiTiet = computed(() => {
  if (loiTaiChiTiet.value?.code === 'ACCOUNT_ID_INVALID') {
    return 'Không thể truy cập dữ liệu tài khoản này.'
  }

  if (loiTaiChiTiet.value?.httpStatus === 404) {
    return 'Không thể truy cập dữ liệu này.'
  }

  return layThongBaoLoiApi(
    loiTaiChiTiet.value,
    'Không thể tải chi tiết tài khoản. Vui lòng thử lại sau.',
  )
})

const coTheThuLaiChiTiet = computed(() => loiTaiChiTiet.value?.isNetworkError === true
  || loiTaiChiTiet.value?.code === 'ACCOUNT_DETAIL_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiChiTiet.value?.httpStatus)
    && loiTaiChiTiet.value.httpStatus >= 500))

const thongBaoLoiCapNhat = computed(() => {
  if (loiCapNhatTrangThai.value?.outcomeUnknown === true) {
    return loiCapNhatTrangThai.value.message
  }

  if (loiCapNhatTrangThai.value?.code === 'LAST_ACTIVE_ADMIN_PROTECTED') {
    return 'Không thể vô hiệu hóa quản trị viên hoạt động cuối cùng. Vui lòng kiểm tra lại trạng thái tài khoản.'
  }

  return layThongBaoLoiApi(
    loiCapNhatTrangThai.value,
    'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại sau.',
  )
})

const thongBaoLoiVaiTro = computed(() => {
  if (loiThayDoiVaiTro.value?.outcomeUnknown === true) {
    return loiThayDoiVaiTro.value.message
  }

  if (loiThayDoiVaiTro.value?.code === 'ROLE_CONFLICT') {
    return 'Vai trò vừa được thay đổi bởi thao tác khác. Dữ liệu đã được tải lại; hãy kiểm tra lại trước khi thử tiếp.'
  }

  if (loiThayDoiVaiTro.value?.code === 'TRAINER_PROFILE_REQUIRED') {
    return 'Không thể cấp vai trò Huấn luyện viên vì tài khoản chưa có hồ sơ huấn luyện viên. Quy trình onboarding hồ sơ không thuộc màn hình này.'
  }

  if (loiThayDoiVaiTro.value?.code === 'LAST_ACTIVE_ADMIN_PROTECTED') {
    return 'Không thể thu hồi quản trị viên hoạt động cuối cùng. Backend vẫn giữ quyền quản trị để hệ thống không mất Admin.'
  }

  if (loiThayDoiVaiTro.value?.code === 'ROLE_ASSIGNMENT_NOT_FOUND') {
    return 'Vai trò này chưa từng được cấp nên không thể thu hồi.'
  }

  return layThongBaoLoiApi(
    loiThayDoiVaiTro.value,
    'Không thể thay đổi vai trò tài khoản. Vui lòng thử lại sau.',
  )
})

const dangMutation = computed(() => dangCapNhatTrangThai.value || dangThayDoiVaiTro.value)

const coTheGuiCapNhat = computed(() => Boolean(taiKhoanDaChon.value)
  && !dangMutation.value
  && CAC_TRANG_THAI_TAI_KHOAN.includes(trangThaiMoi.giaTri)
  && trangThaiMoi.giaTri !== taiKhoanDaChon.value?.status)

const coTheCapVaiTro = computed(() => Boolean(taiKhoanDaChon.value)
  && !dangMutation.value
  && vaiTroChuaGan.value.includes(vaiTroMoi.giaTri))

const thongBaoXacNhanVaiTro = computed(() => {
  const nhanVaiTro = layNhanVaiTro(hienThiXacNhan.maVaiTro)

  if (hienThiXacNhan.loai === 'THU_HOI' && hienThiXacNhan.maVaiTro === 'ADMIN') {
    return 'Bạn đang thu hồi quyền Quản trị viên. Backend sẽ chặn thao tác nếu đây là quản trị viên hoạt động cuối cùng.'
  }

  if (hienThiXacNhan.loai === 'THU_HOI') {
    return `Thu hồi vai trò ${nhanVaiTro}? Backend sẽ kiểm tra lại trạng thái hiện tại trước khi ghi nhận.`
  }

  return `${hienThiXacNhan.loai === 'CAP_LAI' ? 'Cấp lại' : 'Cấp'} vai trò ${nhanVaiTro}? Backend sẽ kiểm tra hồ sơ và quyền thao tác trước khi cập nhật.`
})

const tieuDeXacNhan = computed(() => {
  if (hienThiXacNhan.loai === 'TRANG_THAI') {
    return 'Xác nhận cập nhật trạng thái'
  }

  return hienThiXacNhan.loai === 'THU_HOI'
    ? 'Xác nhận thu hồi vai trò'
    : hienThiXacNhan.loai === 'CAP_LAI'
      ? 'Xác nhận cấp lại vai trò'
      : 'Xác nhận cấp vai trò'
})

const nhanXacNhan = computed(() => {
  if (hienThiXacNhan.loai === 'TRANG_THAI') {
    return `Đổi sang ${layNhanTrangThai(trangThaiMoi.giaTri)}`
  }

  return hienThiXacNhan.loai === 'THU_HOI'
    ? 'Thu hồi vai trò'
    : hienThiXacNhan.loai === 'CAP_LAI' ? 'Cấp lại vai trò' : 'Cấp vai trò'
})

const laHanhDongNguyHiem = computed(() => (
  hienThiXacNhan.loai === 'THU_HOI'
  || (hienThiXacNhan.loai === 'TRANG_THAI' && trangThaiMoi.giaTri !== 'HOAT_DONG')
))

const thongBaoXacNhan = computed(() => {
  if (hienThiXacNhan.loai !== 'TRANG_THAI') {
    return thongBaoXacNhanVaiTro.value
  }

  const nhanTrangThai = layNhanTrangThai(trangThaiMoi.giaTri)
  return `Chuyển trạng thái tài khoản này sang “${nhanTrangThai}”? Backend sẽ kiểm tra lại trạng thái và quyền thao tác trước khi cập nhật.`
})

/**
 * Tai detail Account theo id tren URL va xu ly mat quyen ma khong logout nham.
 *
 * Dau vao: id route hien tai; Store tu validate DTO va race sequence.
 * Cach hoat dong: goi GET detail, sau do neu Backend tra 403 thi den man khong co quyen;
 * 404 giu copy unavailable an toan, khong phan biet resource khong ton tai hay bi conceal.
 * Ket qua: page hien loading/detail/error theo state authoritative cua Store.
 * Side effect: co the thay doi selected Account trong memory va dieu huong 403; khong PATCH.
 * Security Rule: khong ghep du lieu Member/PT profile va khong dung route id lam authorization.
 */
async function taiChiTietTaiKhoan() {
  await store.taiChiTietTaiKhoan(layIdTuRoute())

  if (loiTaiChiTiet.value?.httpStatus === 403) {
    store.xoaTaiKhoanDaChon()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

/**
 * Thu lai GET detail voi cung account id sau loi 5xx/network.
 *
 * Dau vao: id route hien tai, khong nhan id moi tu form.
 * Cach hoat dong: goi Store refetch read-only; 403 van roi ve man quyen, 404 van hien
 * unavailable generic. Retry nay khong lap PATCH va khong thay doi status local optimistically.
 * Ket qua: detail authoritative moi hoac query error an toan.
 * Side effect: cap nhat selected Account va co the dong bo status select theo response.
 */
async function thuLaiTaiChiTietTaiKhoan() {
  await taiChiTietTaiKhoan()
}

/**
 * Mo hop xac nhan truoc khi doi status Account.
 *
 * Dau vao: status moi da chon tu enum Backend.
 * Cach hoat dong: chan status rong, status hien tai va request pending; chi mo dialog,
 * chua goi API de nguoi dung xem lai hanh dong.
 * Ket qua: dialog confirmation hien thi hoac khong co thay doi.
 * Side effect: chi thay doi state presentation; khong optimistic update va khong mutation.
 * Security Rule: dialog khong hien id/email nhay cam; Backend moi kiem tra authority.
 */
function xuLyCapNhatTrangThai() {
  if (coTheGuiCapNhat.value) {
    hienThiXacNhan.loai = 'TRANG_THAI'
    hienThiXacNhan.maVaiTro = ''
    hienThiXacNhan.giaTri = true
  }
}

/**
 * Mo confirmation cho grant/regrant/revoke role tren detail Account.
 *
 * Dau vao: assignment DTO da refetch va hanh dong role do UI xac dinh.
 * Cach hoat dong: chi mo dialog voi role label an toan; khong suy quyen va chua goi API.
 * Ket qua: dialog hien dung cap/cap lai/thu hoi copy, Admin revoke co canh bao manh hon.
 * Side effect: thay doi presentation state trong memory; khong optimistic role update.
 * Security Rule: Backend moi kiem tra authority, profile prerequisite va last-admin guard.
 */
function xuLyHanhDongVaiTro(vaiTro, hanhDong) {
  if (dangMutation.value || !CAC_VAI_TRO_TAI_KHOAN.includes(vaiTro?.code)) {
    return
  }

  hienThiXacNhan.maVaiTro = vaiTro.code
  hienThiXacNhan.loai = hanhDong === 'thuHoi'
    ? 'THU_HOI'
    : vaiTro.active === false ? 'CAP_LAI' : 'CAP'
  hienThiXacNhan.giaTri = true
}

/**
 * Mo confirmation cho grant role dang absent, khong tao profile hay account phu.
 *
 * Dau vao: role enum duoc chon tu danh sach chua gan.
 * Cach hoat dong: chi cho qua enum va mo dialog; Store se validate lai truoc PUT.
 * Ket qua: confirmation cap role hoac khong co action khi selection rong/active.
 * Side effect: thay doi presentation state, khong tao request.
 * Security Rule: UI khong thay the Backend authorization va TRAINER_PROFILE_REQUIRED.
 */
function xuLyCapVaiTro() {
  if (coTheCapVaiTro.value) {
    hienThiXacNhan.maVaiTro = vaiTroMoi.giaTri
    hienThiXacNhan.loai = 'CAP'
    hienThiXacNhan.giaTri = true
  }
}

function dongHopXacNhan() {
  if (!dangMutation.value) {
    hienThiXacNhan.giaTri = false
  }
}

/**
 * Xac nhan PATCH status va de Store refetch detail/list sau khi Backend xu ly.
 *
 * Dau vao: account hien tai va status moi trong select; dialog da duoc mo.
 * Cach hoat dong: goi mutation mot lan, khong gui Idempotency-Key, khong tu retry khi
 * timeout/network; Store refetch de phan loai unknown outcome va page xu ly 403.
 * Ket qua: status hien thi theo GET authoritative, success/error announcement an toan.
 * Side effect: Backend co the revoke token/audit/bao ve last-admin; dialog dong khi request ket thuc.
 * Security Rule: disabled button chi la UX, khong thay the Backend authorization/concurrency.
 */
async function xacNhanCapNhatTrangThai() {
  if (!coTheGuiCapNhat.value || !taiKhoanDaChon.value) {
    return
  }

  await store.capNhatTrangThaiTaiKhoan(
    taiKhoanDaChon.value.id,
    trangThaiMoi.giaTri,
  )
  hienThiXacNhan.giaTri = false

  if (loiCapNhatTrangThai.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiCapNhatTrangThai.value.fieldErrors,
      ['status'],
      { status: 'tai-khoan-chi-tiet-trang-thai' },
      'tai-khoan-loi-cap-nhat-trang-thai',
    )
  }

  if (loiCapNhatTrangThai.value?.httpStatus === 403) {
    store.xoaTaiKhoanDaChon()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

/**
 * Xac nhan role mutation mot lan va de Store refetch detail/list authoritative.
 *
 * Dau vao: role/hanh dong da duoc dialog dong bang state presentation.
 * Cach hoat dong: goi capVaiTro hoac thuHoiVaiTro; khong header idempotency, khong retry
 * mutation khi timeout; 403 clear selected va chuyen man quyen, conflict hien copy co kiem soat.
 * Ket qua: role active/revoked hien theo GET detail, hoac unknown outcome an toan.
 * Side effect: Backend ghi assignment/audit; Store dong bo list va thong bao success/error.
 * Security Rule: Frontend khong tinh last-admin, khong tao trainer profile va khong audit.
 */
async function xacNhanThayDoiVaiTro() {
  if (dangThayDoiVaiTro.value || !taiKhoanDaChon.value) {
    return
  }

  const maVaiTro = hienThiXacNhan.maVaiTro
  const ketQua = hienThiXacNhan.loai === 'THU_HOI'
    ? await store.thuHoiVaiTro(taiKhoanDaChon.value.id, maVaiTro)
    : await store.capVaiTro(taiKhoanDaChon.value.id, maVaiTro)

  hienThiXacNhan.giaTri = false

  if (loiThayDoiVaiTro.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiThayDoiVaiTro.value.fieldErrors,
      ['role'],
      { role: 'tai-khoan-cap-vai-tro' },
      'tai-khoan-loi-thay-doi-vai-tro',
    )
  }

  if (ketQua) {
    vaiTroMoi.giaTri = ''
  }

  if (loiThayDoiVaiTro.value?.httpStatus === 403) {
    store.xoaTaiKhoanDaChon()
    await router.replace({ name: 'khongCoQuyen' })
  }
}

async function xacNhanHanhDong() {
  if (hienThiXacNhan.loai === 'TRANG_THAI') {
    await xacNhanCapNhatTrangThai()
    return
  }

  await xacNhanThayDoiVaiTro()
}

watch(taiKhoanDaChon, (taiKhoan) => {
  if (taiKhoan !== null) {
    trangThaiMoi.giaTri = taiKhoan.status
  } else {
    trangThaiMoi.giaTri = ''
  }
})

watch(() => route.params.id, () => {
  store.xoaTaiKhoanDaChon()
  hienThiXacNhan.giaTri = false
  hienThiXacNhan.loai = 'TRANG_THAI'
  hienThiXacNhan.maVaiTro = ''
  vaiTroMoi.giaTri = ''
  void taiChiTietTaiKhoan()
})

onMounted(() => {
  void taiChiTietTaiKhoan()
})

onBeforeUnmount(() => {
  store.xoaTaiKhoanDaChon()
})
</script>

<template>
  <section
    class="trang-chi-tiet-tai-khoan"
    aria-label="Chi tiết tài khoản"
    :aria-busy="dangTaiChiTiet || dangMutation"
  >
    <TieuDeTrang
      tieu-de="Chi tiết tài khoản"
      mo-ta="Xem thông tin tài khoản và cập nhật trạng thái theo quyền Admin."
    />

    <TrangThaiTaiDuLieu
      v-if="dangTaiChiTiet && !taiKhoanDaChon"
      nhan="Đang tải chi tiết tài khoản…"
    />

    <TrangThaiLoi
      v-if="loiTaiChiTiet"
      :thong-bao="thongBaoLoiChiTiet"
      :co-the-thu-lai="coTheThuLaiChiTiet"
      :dang-thu-lai="dangTaiChiTiet"
      @thu-lai="thuLaiTaiChiTietTaiKhoan"
    />

    <template v-if="taiKhoanDaChon">
      <section
        class="the-chi-tiet-tai-khoan the-chi-tiet-tai-khoan--nhan-dien"
        aria-labelledby="tieu-de-thong-tin-tai-khoan"
      >
        <div class="the-chi-tiet-tai-khoan__dau">
          <div>
            <p class="the-chi-tiet-tai-khoan__nhan-khu-vuc">
              ACCOUNT #{{ taiKhoanDaChon.id }}
            </p>
            <h2 id="tieu-de-thong-tin-tai-khoan">
              {{ taiKhoanDaChon.name }}
            </h2>
            <p class="the-chi-tiet-tai-khoan__phu-de">
              {{ taiKhoanDaChon.email }}
            </p>
          </div>
          <HuyHieuTrangThai
            :trang-thai="layKieuTrangThai(taiKhoanDaChon.status)"
            :nhan="layNhanTrangThai(taiKhoanDaChon.status)"
          />
        </div>

        <dl class="luoi-thuoc-tinh-tai-khoan">
          <div>
            <dt>ID tài khoản</dt>
            <dd>{{ taiKhoanDaChon.id }}</dd>
          </div>
          <div>
            <dt>Họ tên</dt>
            <dd>{{ taiKhoanDaChon.name }}</dd>
          </div>
          <div>
            <dt>Email</dt>
            <dd class="luoi-thuoc-tinh-tai-khoan__gia-tri-dai">
              {{ taiKhoanDaChon.email }}
            </dd>
          </div>
          <div>
            <dt>Điện thoại</dt>
            <dd>{{ taiKhoanDaChon.phone || 'Chưa cập nhật' }}</dd>
          </div>
          <div>
            <dt>Chi nhánh</dt>
            <dd>
              {{ taiKhoanDaChon.branch?.name || 'Chưa gán' }}
              <span v-if="taiKhoanDaChon.branch?.code"> ({{ taiKhoanDaChon.branch.code }})</span>
            </dd>
          </div>
          <div>
            <dt>Email xác minh</dt>
            <dd>{{ dinhDangThoiGian(taiKhoanDaChon.email_verified_at) }}</dd>
          </div>
          <div>
            <dt>Đăng nhập gần nhất</dt>
            <dd>{{ dinhDangThoiGian(taiKhoanDaChon.last_login_at) }}</dd>
          </div>
          <div>
            <dt>Tạo lúc</dt>
            <dd>{{ dinhDangThoiGian(taiKhoanDaChon.created_at) }}</dd>
          </div>
          <div>
            <dt>Cập nhật lúc</dt>
            <dd>{{ dinhDangThoiGian(taiKhoanDaChon.updated_at) }}</dd>
          </div>
        </dl>
      </section>

      <section
        class="the-chi-tiet-tai-khoan"
        aria-labelledby="tieu-de-trang-thai-tai-khoan"
        :aria-busy="dangMutation"
      >
        <div class="the-chi-tiet-tai-khoan__dau">
          <div>
            <p class="the-chi-tiet-tai-khoan__nhan-khu-vuc">
              VÒNG ĐỜI TÀI KHOẢN
            </p>
            <h2 id="tieu-de-trang-thai-tai-khoan">
              Cập nhật trạng thái
            </h2>
          </div>
          <p class="the-chi-tiet-tai-khoan__ghi-chu">
            Backend quyết định trạng thái cuối cùng và bảo vệ quản trị viên cuối cùng.
          </p>
        </div>

        <div class="the-chi-tiet-tai-khoan__trang-thai-hien-tai">
          <span>Trạng thái hiện tại</span>
          <HuyHieuTrangThai
            :trang-thai="layKieuTrangThai(taiKhoanDaChon.status)"
            :nhan="layNhanTrangThai(taiKhoanDaChon.status)"
          />
        </div>

        <form
          class="bieu-mau-trang-thai-tai-khoan"
          aria-label="Cập nhật trạng thái tài khoản"
          :aria-busy="dangMutation"
          @submit.prevent="xuLyCapNhatTrangThai"
        >
          <TruongBieuMau
            id="tai-khoan-chi-tiet-trang-thai"
            nhan="Trạng thái mới"
            bat-buoc
            tro-giup="Chỉ chọn một trạng thái trong danh sách Backend cho phép."
            :loi="layLoiTruong('status')"
          >
            <template #default="{ id, ariaDescribedby, ariaInvalid }">
              <select
                :id="id"
                v-model="trangThaiMoi.giaTri"
                name="status"
                :disabled="dangMutation"
                :aria-describedby="ariaDescribedby"
                :aria-invalid="ariaInvalid"
              >
                <option
                  v-for="trangThai in CAC_TRANG_THAI_TAI_KHOAN"
                  :key="trangThai"
                  :value="trangThai"
                >
                  {{ layNhanTrangThai(trangThai) }}
                </option>
              </select>
            </template>
          </TruongBieuMau>
          <button
            class="nut nut--chinh"
            type="submit"
            :disabled="!coTheGuiCapNhat"
            :aria-busy="dangMutation"
          >
            {{ dangMutation ? 'Đang cập nhật…' : 'Cập nhật trạng thái' }}
          </button>
        </form>

        <p
          v-if="loiCapNhatTrangThai"
          id="tai-khoan-loi-cap-nhat-trang-thai"
          class="the-chi-tiet-tai-khoan__loi-mutation"
          tabindex="-1"
          role="alert"
          aria-live="assertive"
        >
          {{ thongBaoLoiCapNhat }}
        </p>
        <VungThongBao
          v-if="thongBaoCapNhatTrangThai"
          :danh-sach="[{ id: 'cap-nhat-trang-thai', kieu: 'thanh_cong', noiDung: thongBaoCapNhatTrangThai }]"
        />
      </section>

      <section
        class="the-chi-tiet-tai-khoan"
        aria-labelledby="tieu-de-vai-tro-tai-khoan"
      >
        <div class="the-chi-tiet-tai-khoan__dau">
          <div>
            <p class="the-chi-tiet-tai-khoan__nhan-khu-vuc">
              QUYỀN ĐÃ GHI NHẬN
            </p>
            <h2 id="tieu-de-vai-tro-tai-khoan">
              Vai trò
            </h2>
          </div>
          <p class="the-chi-tiet-tai-khoan__ghi-chu">
            Trạng thái role do Backend quyết định; thao tác sẽ được kiểm tra lại trước khi ghi nhận.
          </p>
        </div>
        <div class="the-chi-tiet-tai-khoan__vai-tro">
          <template v-if="layVaiTroHienThi(taiKhoanDaChon).length > 0">
            <div
              v-for="vaiTro in layVaiTroHienThi(taiKhoanDaChon)"
              :key="vaiTro.assignment_id ?? vaiTro.code"
              class="the-chi-tiet-tai-khoan__vai-tro-muc"
            >
              <HuyHieuTrangThai
                :trang-thai="vaiTro.active === true ? 'thong_tin' : 'canh_bao'"
                :nhan="vaiTro.active === true
                  ? layNhanVaiTro(vaiTro.code)
                  : `${layNhanVaiTro(vaiTro.code)} · Đã thu hồi`"
              />
              <span v-if="vaiTro.granted_at || vaiTro.revoked_at">
                {{ vaiTro.active === true
                  ? `Cấp lúc ${dinhDangThoiGian(vaiTro.granted_at)}`
                  : `Thu hồi lúc ${dinhDangThoiGian(vaiTro.revoked_at)}` }}
              </span>
              <button
                class="nut nut--phu the-chi-tiet-tai-khoan__vai-tro-hanh-dong"
                type="button"
                :disabled="dangMutation"
                :aria-busy="dangThayDoiVaiTro && vaiTroDangXuLy === vaiTro.code"
                @click="xuLyHanhDongVaiTro(vaiTro, vaiTro.active === true ? 'thuHoi' : 'capLai')"
              >
                {{ vaiTro.active === true ? 'Thu hồi vai trò' : 'Cấp lại vai trò' }}
              </button>
            </div>
          </template>
          <p v-else>
            Chưa gán vai trò.
          </p>
        </div>
        <form
          v-if="vaiTroChuaGan.length > 0"
          class="bieu-mau-vai-tro-tai-khoan"
          aria-label="Cấp vai trò"
          :aria-busy="dangMutation"
          @submit.prevent="xuLyCapVaiTro"
        >
          <TruongBieuMau
            id="tai-khoan-cap-vai-tro"
            nhan="Cấp vai trò chưa gán"
            bat-buoc
            tro-giup="Chỉ có thể cấp một role trong danh sách Backend cho phép."
            :loi="layLoiTruongVaiTro('role')"
          >
            <template #default="{ id, ariaDescribedby, ariaInvalid }">
              <select
                :id="id"
                v-model="vaiTroMoi.giaTri"
                name="role"
                :disabled="dangMutation"
                :aria-describedby="ariaDescribedby"
                :aria-invalid="ariaInvalid"
              >
                <option value="">
                  Chọn vai trò
                </option>
                <option
                  v-for="maVaiTro in vaiTroChuaGan"
                  :key="maVaiTro"
                  :value="maVaiTro"
                >
                  {{ layNhanVaiTro(maVaiTro) }}
                </option>
              </select>
            </template>
          </TruongBieuMau>
          <button
            class="nut nut--chinh"
            type="submit"
            :disabled="!coTheCapVaiTro"
          >
            Cấp vai trò
          </button>
        </form>
        <p
          v-if="loiThayDoiVaiTro"
          id="tai-khoan-loi-thay-doi-vai-tro"
          class="the-chi-tiet-tai-khoan__loi-mutation"
          tabindex="-1"
          role="alert"
          aria-live="assertive"
        >
          {{ thongBaoLoiVaiTro }}
        </p>
        <VungThongBao
          v-if="thongBaoThayDoiVaiTro"
          :danh-sach="[{ id: 'thay-doi-vai-tro', kieu: 'thanh_cong', noiDung: thongBaoThayDoiVaiTro }]"
        />
      </section>

      <section
        v-if="taiKhoanDaChon.member_profile || taiKhoanDaChon.trainer_profile"
        class="the-chi-tiet-tai-khoan"
        aria-labelledby="tieu-de-ho-so-lien-quan"
      >
        <div class="the-chi-tiet-tai-khoan__dau">
          <div>
            <p class="the-chi-tiet-tai-khoan__nhan-khu-vuc">
              THAM CHIẾU AN TOÀN
            </p>
            <h2 id="tieu-de-ho-so-lien-quan">
              Hồ sơ liên quan
            </h2>
          </div>
        </div>
        <dl class="luoi-thuoc-tinh-tai-khoan luoi-thuoc-tinh-tai-khoan--ho-so">
          <div v-if="taiKhoanDaChon.member_profile">
            <dt>Hồ sơ hội viên</dt>
            <dd>{{ taiKhoanDaChon.member_profile.code || 'Đã tạo hồ sơ' }}</dd>
          </div>
          <div v-if="taiKhoanDaChon.trainer_profile">
            <dt>Hồ sơ huấn luyện viên</dt>
            <dd>
              {{ taiKhoanDaChon.trainer_profile.code || 'Đã tạo hồ sơ' }}
              <span v-if="taiKhoanDaChon.trainer_profile.status">
                · {{ layNhanTrangThaiHoSoHuanLuyenVien(taiKhoanDaChon.trainer_profile.status) }}
              </span>
            </dd>
          </div>
        </dl>
      </section>
    </template>

    <HopThoaiXacNhan
      :hien-thi="hienThiXacNhan.giaTri"
      :tieu-de="tieuDeXacNhan"
      :mo-ta="thongBaoXacNhan"
      :nhan-xac-nhan="nhanXacNhan"
      nhan-huy="Quay lại"
      :mang-nguy-hiem="laHanhDongNguyHiem"
      :dang-xu-ly="dangMutation"
      @xac-nhan="xacNhanHanhDong"
      @huy="dongHopXacNhan"
      @dong="dongHopXacNhan"
    />
  </section>
</template>
