<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BoLocDanhSach from '../../../components/dung_chung/bo_loc_danh_sach.vue'
import TheChiSo from '../../../components/dung_chung/the_chi_so.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { taiTongQuanAdmin } from '../../../services/bang_dieu_khien.api.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const SO_NGAY_TOI_DA = 366

const DINH_NGHIA_CHI_SO = Object.freeze([
  Object.freeze({
    maSo: 'hoiVienDangHoatDong',
    nhan: 'Hội viên đang hoạt động',
    moTa: 'Tài khoản Member đang hoạt động trong chi nhánh.',
  }),
  Object.freeze({
    maSo: 'huanLuyenVienDangHoatDong',
    nhan: 'Huấn luyện viên đang hoạt động',
    moTa: 'Tài khoản PT và hồ sơ PT đang hoạt động.',
  }),
  Object.freeze({
    maSo: 'kyHoiVienDangHoatDong',
    nhan: 'Kỳ hội viên đang hoạt động',
    moTa: 'Các kỳ Membership đang ở trạng thái hoạt động.',
  }),
  Object.freeze({
    maSo: 'kyHoiVienChoKichHoat',
    nhan: 'Kỳ hội viên chờ kích hoạt',
    moTa: 'Các kỳ đã sở hữu nhưng chưa bắt đầu thời hạn.',
  }),
  Object.freeze({
    maSo: 'checkInHomNay',
    nhan: 'Lượt check-in hôm nay',
    moTa: 'Theo ngày hiện tại của chi nhánh.',
  }),
  Object.freeze({
    maSo: 'buoiTapHoanTatHomNay',
    nhan: 'Buổi tập hoàn tất hôm nay',
    moTa: 'Workout hoàn tất trong ngày hiện tại của chi nhánh.',
  }),
  Object.freeze({
    maSo: 'buoiTapHoanTatTrongKy',
    nhan: 'Buổi tập hoàn tất trong kỳ',
    moTa: 'Workout hoàn tất trong khoảng đã chọn.',
  }),
  Object.freeze({
    maSo: 'thanhToanThanhCongTrongKy',
    nhan: 'Thanh toán thành công trong kỳ',
    moTa: 'Số lần thanh toán thành công trong khoảng đã chọn.',
  }),
  Object.freeze({
    maSo: 'thanhToanCanDoiSoat',
    nhan: 'Thanh toán cần đối soát',
    moTa: 'Các lần thanh toán Backend đánh dấu cần đối soát.',
  }),
  Object.freeze({
    maSo: 'phanCongPtDangHoatDong',
    nhan: 'Phân công PT đang hoạt động',
    moTa: 'Phân công PT đang hiệu lực theo Backend.',
  }),
])

const router = useRouter()
const tuNgay = ref('')
const denNgay = ref('')
const tongQuan = ref(null)
const dangTai = ref(false)
const loiTaiDuLieu = ref(null)
const loiTruong = ref({})
let soThuTuYeuCau = 0

function layGiaTriChiSo(duLieu, maSo) {
  const danhSachChoPhep = {
    hoiVienDangHoatDong: duLieu?.accounts?.active_members_count,
    huanLuyenVienDangHoatDong: duLieu?.accounts?.active_trainers_count,
    kyHoiVienDangHoatDong: duLieu?.memberships?.active_terms_count,
    kyHoiVienChoKichHoat: duLieu?.memberships?.awaiting_activation_terms_count,
    checkInHomNay: duLieu?.activity?.check_ins_today_count,
    buoiTapHoanTatHomNay: duLieu?.activity?.completed_workouts_today_count,
    buoiTapHoanTatTrongKy: duLieu?.activity?.completed_workouts_in_period_count,
    thanhToanThanhCongTrongKy: duLieu?.payments?.successful_in_period_count,
    thanhToanCanDoiSoat: duLieu?.payments?.reconciliation_required_count,
    phanCongPtDangHoatDong: duLieu?.pt?.active_assignments_count,
  }

  return danhSachChoPhep[maSo]
}

function laSoNguyenKhongAm(giaTri) {
  return Number.isInteger(giaTri) && giaTri >= 0
}

function laBaoCaoDashboardHopLe(duLieu) {
  if (duLieu === null || typeof duLieu !== 'object') {
    return false
  }

  const period = duLieu.period
  const branch = duLieu.branch
  const periodHopLe = period !== null
    && typeof period === 'object'
    && typeof period.from === 'string'
    && typeof period.to === 'string'
    && laSoNguyenKhongAm(period.inclusive_days)
  const branchHopLe = branch !== null
    && typeof branch === 'object'
    && typeof branch.timezone === 'string'
    && branch.timezone.trim() !== ''

  return periodHopLe
    && branchHopLe
    && DINH_NGHIA_CHI_SO.every((chiSo) => laSoNguyenKhongAm(layGiaTriChiSo(duLieu, chiSo.maSo)))
}

function layLoiTruong(tenTruong) {
  const danhSachLoi = loiTruong.value?.[tenTruong]

  return Array.isArray(danhSachLoi) ? danhSachLoi[0] ?? '' : ''
}

function laNgayISOHopLe(ngay) {
  if (typeof ngay !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(ngay)) {
    return false
  }

  const [nam, thang, ngayTrongThang] = ngay.split('-').map(Number)
  const mocUtc = Date.UTC(nam, thang - 1, ngayTrongThang)
  const ngayDaKiemTra = new Date(mocUtc)

  return Number.isFinite(mocUtc) && ngayDaKiemTra.toISOString().slice(0, 10) === ngay
}

/**
 * Dem so ngay lich theo UTC date-only de tranh DST/timezone cua trinh duyet.
 *
 * Dau vao: hai chuoi YYYY-MM-DD da qua validation format va calendar date.
 * Cach hoat dong: tach nam/thang/ngay, tinh chenh lech moc UTC va cong ca hai dau mut.
 * Ket qua: so ngay lich inclusive de doi chieu gioi han Backend 366 ngay.
 * Side effect: khong doc timezone trinh duyet, khong goi API va khong thay doi state.
 * Business Rule: day count chi dung cho UX validation; Backend van la authority cuoi.
 */
function demSoNgayLich(tu, den) {
  const [namTu, thangTu, ngayTu] = tu.split('-').map(Number)
  const [namDen, thangDen, ngayDen] = den.split('-').map(Number)
  const mocTu = Date.UTC(namTu, thangTu - 1, ngayTu)
  const mocDen = Date.UTC(namDen, thangDen - 1, ngayDen)

  return ((mocDen - mocTu) / (24 * 60 * 60 * 1000)) + 1
}

/**
 * Dinh dang khoang ngay dung period Backend tra ve, khong thay doi timezone semantics.
 *
 * Dau vao: period co from, to va inclusive_days tu response Dashboard.
 * Cach hoat dong: hien thi nguyen chuoi date-only va so ngay inclusive do Backend tinh.
 * Ket qua: chuoi context de Admin biet bao cao dang tong hop khoang nao.
 * Side effect: khong tinh lai period, khong doc browser timezone va khong goi API.
 */
function dinhDangKhoangNgay(period) {
  if (period === null || typeof period !== 'object') {
    return ''
  }

  if (typeof period.from !== 'string' || typeof period.to !== 'string'
    || !Number.isInteger(period.inclusive_days)) {
    return ''
  }

  return `${period.from} – ${period.to} · ${period.inclusive_days} ngày`
}

function dinhDangThoiDiem(generatedAt, muiGio) {
  if (typeof generatedAt !== 'string' || generatedAt.trim() === ''
    || typeof muiGio !== 'string' || muiGio.trim() === '') {
    return ''
  }

  try {
    const thoiDiem = new Date(generatedAt)

    if (Number.isNaN(thoiDiem.getTime())) {
      return ''
    }

    return new Intl.DateTimeFormat('vi-VN', {
      dateStyle: 'medium',
      timeStyle: 'short',
      timeZone: muiGio,
    }).format(thoiDiem)
  } catch {
    return ''
  }
}

/**
 * Tai Dashboard va giu lai du lieu tot truoc khi hien loi retry.
 *
 * Dau vao: boLoc rong hoac cap from/to da duoc page validate.
 * Cach hoat dong: goi service read-only, kiem tra response allow-list va bo qua response cu neu da co request moi.
 * Ket qua: cap nhat tongQuan hoac loiTaiDuLieu an toan; 403 duoc dua ve layout loi hien co.
 * Side effect: phat sinh GET Dashboard; khong logout, khong idempotency key, khong mutation va khong auto-retry.
 * Business Rule: 401 do Axios/Auth xu ly; 403 khong logout; 5xx/network cho phep Admin bam Thu lai.
 */
async function taiDuLieuDashboard(boLoc = {}) {
  const soThuTuHienTai = ++soThuTuYeuCau
  dangTai.value = true
  loiTaiDuLieu.value = null

  try {
    const phanHoi = await taiTongQuanAdmin(boLoc)

    if (soThuTuHienTai !== soThuTuYeuCau) {
      return
    }

    const duLieu = phanHoi?.data

    if (!laBaoCaoDashboardHopLe(duLieu)) {
      loiTaiDuLieu.value = {
        code: 'DASHBOARD_RESPONSE_INVALID',
        message: 'Dữ liệu bảng điều khiển không đầy đủ.',
        fieldErrors: {},
      }
      return
    }

    tongQuan.value = duLieu
  } catch (error) {
    if (soThuTuHienTai !== soThuTuYeuCau) {
      return
    }

    if (error?.httpStatus === 403) {
      await router.replace({ name: 'khongCoQuyen' })
      return
    }

    if (error?.httpStatus === 422) {
      loiTruong.value = error.fieldErrors ?? {}
      await datFocusVaoTruongLoiDau(
        loiTruong.value,
        ['from', 'to'],
        { from: 'dashboard-tu-ngay', to: 'dashboard-den-ngay' },
        'dashboard-loi-du-lieu',
      )
    }

    loiTaiDuLieu.value = error
  } finally {
    if (soThuTuHienTai === soThuTuYeuCau) {
      dangTai.value = false
    }
  }
}

/**
 * Ap dung cap ngay Dashboard sau khi kiem tra pair, format va gioi han 366 ngay.
 *
 * Dau vao: tuNgay va denNgay tu hai input date; ca hai rong la yeu cau default Backend.
 * Cach hoat dong: reject cap thieu, date khong hop le, from > to va range vuot 366 ngay;
 * date hop le duoc gui nguyen YYYY-MM-DD, khong convert UTC timestamp.
 * Ket qua: cap nhat query state va trigger mot GET Dashboard read-only.
 * Side effect: co the hien loi field local/422 va tai lai tongQuan; Backend van validation authority.
 * Business Rule: khong chon branch/timezone, khong tinh default 30 ngay o client, khong retry tu dong.
 */
async function apDungKhoangNgay() {
  loiTruong.value = {}

  const coTuNgay = tuNgay.value !== ''
  const coDenNgay = denNgay.value !== ''

  if (!coTuNgay && !coDenNgay) {
    await taiDuLieuDashboard()
    return true
  }

  if (!coTuNgay || !coDenNgay) {
    loiTruong.value = {
      from: coTuNgay ? [] : ['Vui lòng chọn Từ ngày.'],
      to: coDenNgay ? [] : ['Vui lòng chọn Đến ngày.'],
    }
    await datFocusVaoTruongLoiDau(
      loiTruong.value,
      ['from', 'to'],
      { from: 'dashboard-tu-ngay', to: 'dashboard-den-ngay' },
    )
    return false
  }

  if (!laNgayISOHopLe(tuNgay.value)) {
    loiTruong.value.from = ['Từ ngày không hợp lệ.']
    await datFocusVaoTruongLoiDau(loiTruong.value, ['from', 'to'], {
      from: 'dashboard-tu-ngay',
      to: 'dashboard-den-ngay',
    })
    return false
  }

  if (!laNgayISOHopLe(denNgay.value)) {
    loiTruong.value.to = ['Đến ngày không hợp lệ.']
    await datFocusVaoTruongLoiDau(loiTruong.value, ['from', 'to'], {
      from: 'dashboard-tu-ngay',
      to: 'dashboard-den-ngay',
    })
    return false
  }

  const soNgayLich = demSoNgayLich(tuNgay.value, denNgay.value)

  if (soNgayLich < 1) {
    loiTruong.value.to = ['Đến ngày phải từ Từ ngày trở đi.']
    await datFocusVaoTruongLoiDau(loiTruong.value, ['from', 'to'], {
      from: 'dashboard-tu-ngay',
      to: 'dashboard-den-ngay',
    })
    return false
  }

  if (soNgayLich > SO_NGAY_TOI_DA) {
    loiTruong.value.to = ['Khoảng thời gian không được dài hơn 366 ngày.']
    await datFocusVaoTruongLoiDau(loiTruong.value, ['from', 'to'], {
      from: 'dashboard-tu-ngay',
      to: 'dashboard-den-ngay',
    })
    return false
  }

  await taiDuLieuDashboard({ from: tuNgay.value, to: denNgay.value })
  return true
}

async function datLaiKhoangNgay() {
  tuNgay.value = ''
  denNgay.value = ''
  loiTruong.value = {}
  await taiDuLieuDashboard()
}

async function xuLyThuLai() {
  if (dangTai.value) {
    return
  }

  const boLoc = tuNgay.value !== '' && denNgay.value !== ''
    ? { from: tuNgay.value, to: denNgay.value }
    : {}

  await taiDuLieuDashboard(boLoc)
}

const cacChiSo = computed(() => DINH_NGHIA_CHI_SO.map((chiSo) => ({
  ...chiSo,
  giaTri: layGiaTriChiSo(tongQuan.value, chiSo.maSo),
})))

const khoangDuLieu = computed(() => dinhDangKhoangNgay(tongQuan.value?.period))
const muiGioChiNhanh = computed(() => tongQuan.value?.branch?.timezone ?? '')
const thoiDiemCapNhat = computed(() => dinhDangThoiDiem(
  tongQuan.value?.generated_at,
  muiGioChiNhanh.value,
))
const thongBaoLoi = computed(() => loiTaiDuLieu.value?.code === 'DASHBOARD_RESPONSE_INVALID'
  ? loiTaiDuLieu.value.message
  : layThongBaoLoiApi(
    loiTaiDuLieu.value,
   'Không thể tải dữ liệu bảng điều khiển. Vui lòng thử lại sau.',
  ))

const coTheThuLai = computed(() => loiTaiDuLieu.value?.isNetworkError === true
  || loiTaiDuLieu.value?.code === 'DASHBOARD_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiDuLieu.value?.httpStatus)
    && loiTaiDuLieu.value.httpStatus >= 500))

onMounted(() => {
  void taiDuLieuDashboard()
})
</script>

<template>
  <section
    class="trang-bang-dieu-khien"
    aria-label="Bảng điều khiển"
  >
    <TieuDeTrang
      tieu-de="Bảng điều khiển"
      mo-ta="Tổng quan hoạt động của chi nhánh theo dữ liệu Backend."
    />

    <section
      class="trang-bang-dieu-khien__bo-loc"
      aria-labelledby="tieu-de-bo-loc-bang-dieu-khien"
    >
      <div class="trang-bang-dieu-khien__bo-loc-dau">
        <div>
          <p class="trang-bang-dieu-khien__nhan-khu-vuc">
            Khoảng dữ liệu
          </p>
          <h2 id="tieu-de-bo-loc-bang-dieu-khien">
            Lọc theo ngày
          </h2>
        </div>
        <p class="trang-bang-dieu-khien__goi-y">
          Để trống cả hai trường để dùng khoảng mặc định từ Backend.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTai"
        @ap-dung="apDungKhoangNgay"
        @dat-lai="datLaiKhoangNgay"
      >
        <TruongBieuMau
          id="dashboard-tu-ngay"
          nhan="Từ ngày"
          :loi="layLoiTruong('from')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="tuNgay"
              name="from"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="dashboard-den-ngay"
          nhan="Đến ngày"
          :loi="layLoiTruong('to')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="denNgay"
              name="to"
              type="date"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>
    </section>

    <section
      v-if="khoangDuLieu || muiGioChiNhanh || thoiDiemCapNhat"
      class="trang-bang-dieu-khien__thong-tin"
      aria-label="Thông tin dữ liệu"
    >
      <p v-if="khoangDuLieu">
        <strong>Khoảng tổng hợp:</strong> {{ khoangDuLieu }}
      </p>
      <p v-if="muiGioChiNhanh">
        <strong>Múi giờ chi nhánh:</strong> {{ muiGioChiNhanh }}
      </p>
      <p v-if="thoiDiemCapNhat">
        <strong>Cập nhật lúc:</strong> {{ thoiDiemCapNhat }}
      </p>
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTai && !tongQuan"
      nhan="Đang tải số liệu bảng điều khiển…"
    />

    <TrangThaiLoi
      v-else-if="loiTaiDuLieu && !tongQuan"
      id="dashboard-loi-du-lieu"
      tabindex="-1"
      :thong-bao="thongBaoLoi"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTai"
      @thu-lai="xuLyThuLai"
    />

    <template v-else-if="tongQuan">
      <TrangThaiLoi
        v-if="loiTaiDuLieu"
        id="dashboard-loi-du-lieu"
        tabindex="-1"
        :thong-bao="thongBaoLoi"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTai"
        @thu-lai="xuLyThuLai"
      />
      <div
        class="trang-bang-dieu-khien__chi-so"
        :aria-busy="dangTai"
      >
        <TheChiSo
          v-for="chiSo in cacChiSo"
          :key="chiSo.maSo"
          :nhan="chiSo.nhan"
          :gia-tri="chiSo.giaTri"
          :mo-ta="chiSo.moTa"
          :dang-tai="dangTai"
        />
      </div>
    </template>
  </section>
</template>
