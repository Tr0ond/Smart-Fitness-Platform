<script setup>
import { computed, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import BoLocDanhSach from '../../../components/dung_chung/bo_loc_danh_sach.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import ThanhPhanTrang from '../../../components/dung_chung/thanh_phan_trang.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { CAC_TRANG_THAI_TAI_KHOAN } from '../../../services/tai_khoan.api.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = useTaiKhoanStore()
const {
  danhSachHuanLuyenVien,
  boLocHuanLuyenVien,
  phanTrangHuanLuyenVien,
  dangTaiHuanLuyenVien,
  loiTaiHuanLuyenVien,
  daTaiHuanLuyenVienLanDau,
} = storeToRefs(store)

const boLocNhap = reactive({
  search: '',
  status: '',
})

const CAC_COT_HUAN_LUYEN_VIEN = Object.freeze([
  { khoa: 'id', nhan: 'ID' },
  { khoa: 'name', nhan: 'Họ tên' },
  { khoa: 'email', nhan: 'Email' },
  { khoa: 'trainer_profile', nhan: 'Mã PT' },
  { khoa: 'phone', nhan: 'Điện thoại' },
  { khoa: 'branch', nhan: 'Chi nhánh' },
  { khoa: 'status', nhan: 'Trạng thái tài khoản' },
  { khoa: 'trainer_profile_status', nhan: 'Trạng thái hồ sơ PT' },
])

const NHAN_TRANG_THAI_TAI_KHOAN = Object.freeze({
  HOAT_DONG: 'Hoạt động',
  BI_KHOA: 'Bị khóa',
  NGUNG_HOAT_DONG: 'Ngừng hoạt động',
})

const KIEU_TRANG_THAI_TAI_KHOAN = Object.freeze({
  HOAT_DONG: 'thanh_cong',
  BI_KHOA: 'nguy_hiem',
  NGUNG_HOAT_DONG: 'canh_bao',
})

const NHAN_TRANG_THAI_HO_SO_PT = Object.freeze({
  HOAT_DONG: 'Đang nhận phân công',
  NGUNG_NHAN_PHAN_CONG: 'Ngừng nhận phân công',
})

const KIEU_TRANG_THAI_HO_SO_PT = Object.freeze({
  HOAT_DONG: 'thanh_cong',
  NGUNG_NHAN_PHAN_CONG: 'canh_bao',
})

const coRouteChiTietHuanLuyenVien = computed(() => typeof router.hasRoute === 'function'
  && router.hasRoute('adminChiTietHuanLuyenVien'))

const cacCotHienThi = computed(() => coRouteChiTietHuanLuyenVien.value
  ? [...CAC_COT_HUAN_LUYEN_VIEN, { khoa: 'thao_tac', nhan: 'Thao tác' }]
  : CAC_COT_HUAN_LUYEN_VIEN)

function layLoiTruong(tenTruong) {
  const danhSach = loiTaiHuanLuyenVien.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layNhanTrangThaiTaiKhoan(trangThai) {
  return NHAN_TRANG_THAI_TAI_KHOAN[trangThai] ?? 'Không xác định'
}

function layKieuTrangThaiTaiKhoan(trangThai) {
  return KIEU_TRANG_THAI_TAI_KHOAN[trangThai] ?? 'trung_tinh'
}

function layMaHuanLuyenVien(taiKhoan) {
  const ma = taiKhoan?.trainer_profile?.code
  return typeof ma === 'string' && ma.trim() !== '' ? ma : 'Chưa có hồ sơ PT'
}

function layDienThoai(taiKhoan) {
  return typeof taiKhoan?.phone === 'string' && taiKhoan.phone.trim() !== ''
    ? taiKhoan.phone
    : 'Chưa cập nhật'
}

function layTenChiNhanh(taiKhoan) {
  const ten = taiKhoan?.branch?.name
  return typeof ten === 'string' && ten.trim() !== '' ? ten : 'Chưa gán'
}

/**
 * Map status ho so PT sang nhan presentation an toan, khong lo ma enum la ra giao dien.
 *
 * Dau vao: Account DTO da duoc Store validate; trainer_profile co the null.
 * Cach hoat dong: tach truong hop khong co ho so, chi map hai enum da biet va dung
 * nhan neutral cho status moi/khong hop le.
 * Ket qua: mot nhan tieng Viet an toan cho cot trang thai ho so.
 * Side effect: khong goi API, khong thay doi DTO va khong suy ra phan cong Member.
 * Security Rule: status ho so chi la presentation; khong dung de cap quyen hay tao scope.
 */
function layNhanTrangThaiHoSoPt(taiKhoan) {
  const hoSo = taiKhoan?.trainer_profile

  if (hoSo === null || hoSo === undefined) {
    return 'Chưa có hồ sơ PT'
  }

  return NHAN_TRANG_THAI_HO_SO_PT[hoSo.status] ?? 'Không xác định'
}

function layKieuTrangThaiHoSoPt(taiKhoan) {
  const hoSo = taiKhoan?.trainer_profile

  return hoSo === null || hoSo === undefined
    ? 'trung_tinh'
    : KIEU_TRANG_THAI_HO_SO_PT[hoSo.status] ?? 'trung_tinh'
}

/**
 * Tao named route detail an toan cho Account PT trong list.
 *
 * Dau vao: Account DTO tu Backend va route registry hien tai.
 * Cach hoat dong: chi tao named location khi route detail da dang ky va id la so nguyen
 * duong an toan; khong ghep pathname tu du lieu user hoac status PT.
 * Ket qua: named location `adminChiTietHuanLuyenVien` hoac null neu khong hop le.
 * Side effect: khong goi API, khong mutation va khong cap quyen.
 * Security Rule: named link chi dieu phoi UX; Backend van xac minh ADMIN/branch.
 */
function moChiTietHuanLuyenVien(taiKhoan) {
  const id = taiKhoan?.id
  const idHopLe = (typeof id === 'number' && Number.isSafeInteger(id) && id > 0)
    || (typeof id === 'string' && /^[1-9]\d*$/.test(id.trim()))

  return coRouteChiTietHuanLuyenVien.value && idHopLe
    ? { name: 'adminChiTietHuanLuyenVien', params: { id: String(id).trim() } }
    : null
}

function coBoLocDaApDung() {
  return (boLocHuanLuyenVien.value?.search ?? '').trim() !== ''
    || (boLocHuanLuyenVien.value?.status ?? '').trim() !== ''
}

async function xuLyLoiTaiDanhSach() {
  if (loiTaiHuanLuyenVien.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiTaiHuanLuyenVien.value.fieldErrors,
      ['search', 'status', 'page', 'per_page'],
      {
        search: 'huan-luyen-vien-tim-kiem',
        status: 'huan-luyen-vien-trang-thai',
      },
      'huan-luyen-vien-loi-danh-sach',
    )
  }

  if (loiTaiHuanLuyenVien.value?.httpStatus === 403) {
    store.xoaDuLieuHuanLuyenVien()
    await router.replace({ name: 'khongCoQuyen' })
    return true
  }

  return false
}

/**
 * Tai list PT ban dau theo filter/page da ap dung trong Store.
 *
 * Dau vao: khong co; Store giu query scoped, service tu ep role PT.
 * Cach hoat dong: doc GET Account list, sau do xu ly 403 bang cleanup trainer scope
 * va route quyen; 401 van de Auth lifecycle chung xu ly.
 * Ket qua: list, loading, empty hoac error state an toan tren page.
 * Side effect: GET read-only; khong goi profile, assignment, Membership hay mutation.
 */
async function taiDuLieuHuanLuyenVien() {
  await store.taiDanhSachHuanLuyenVien()
  await xuLyLoiTaiDanhSach()
}

/**
 * Ap dung search/status cho danh sach PT va reset page ve 1.
 *
 * Dau vao: draft filter chi gom search/status tu form.
 * Cach hoat dong: Store trim va allow-list query, service gan role PT; caller khong
 * the chon role, branch hay truong authority khac.
 * Ket qua: list theo filter moi hoac loi field-level 422.
 * Side effect: GET read-only; khong tao/chinh sua Account hay ho so PT.
 */
async function apDungBoLocHuanLuyenVien() {
  await store.apDungBoLocHuanLuyenVien({ ...boLocNhap })
  await xuLyLoiTaiDanhSach()
}

/**
 * Dat lai filter PT ve default va tai lai trang dau.
 *
 * Dau vao: khong co.
 * Cach hoat dong: xoa draft search/status va de Store reset applied filter scoped,
 * sau do tai lai Account list voi role PT co dinh.
 * Ket qua: list/pagination theo query mac dinh.
 * Side effect: GET read-only; khong logout va khong cham scope Account khac.
 */
async function datLaiBoLocHuanLuyenVien() {
  boLocNhap.search = ''
  boLocNhap.status = ''
  await store.datLaiBoLocHuanLuyenVien()
  await xuLyLoiTaiDanhSach()
}

/**
 * Chuyen trang PT theo pagination authoritative do Backend tra ve.
 *
 * Dau vao: so trang emit tu component dung chung.
 * Cach hoat dong: Store chan trang ngoai bien, giu applied filter va sua overflow theo
 * last_page khi can; page khong tu tinh total hay du lieu client-side.
 * Ket qua: list/pagination PT moi hoac loi an toan.
 * Side effect: GET read-only; khong thay doi Account, profile hay assignment.
 */
async function chuyenTrangHuanLuyenVien(trangMoi) {
  await store.chuyenTrangHuanLuyenVien(trangMoi)
  await xuLyLoiTaiDanhSach()
}

/**
 * Thu lai list PT bang dung applied filter/page sau loi tam thoi.
 *
 * Dau vao: khong co; Store giu context dang xem.
 * Cach hoat dong: chi phat sinh GET thu cong; khong retry 422/403 va khong reset filter.
 * Ket qua: rows moi neu Backend san sang hoac loi normalized an toan.
 * Side effect: GET read-only; khong mutation va khong logout.
 */
async function thuLaiDanhSachHuanLuyenVien() {
  await store.thuLaiDanhSachHuanLuyenVien()
  await xuLyLoiTaiDanhSach()
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiTaiHuanLuyenVien.value,
  'Không thể tải danh sách Huấn luyện viên. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => loiTaiHuanLuyenVien.value?.isNetworkError === true
  || loiTaiHuanLuyenVien.value?.code === 'TRAINER_LIST_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiHuanLuyenVien.value?.httpStatus)
    && loiTaiHuanLuyenVien.value.httpStatus >= 500))

const thongBaoTrong = computed(() => coBoLocDaApDung()
  ? 'Không tìm thấy Huấn luyện viên phù hợp.'
  : 'Chưa có Huấn luyện viên.')

onMounted(() => {
  boLocNhap.search = boLocHuanLuyenVien.value.search
  boLocNhap.status = boLocHuanLuyenVien.value.status
  void taiDuLieuHuanLuyenVien()
})
</script>

<template>
  <section
    class="trang-huan-luyen-vien"
    aria-label="Danh sách Huấn luyện viên"
    :aria-busy="dangTaiHuanLuyenVien"
  >
    <TieuDeTrang
      tieu-de="Danh sách Huấn luyện viên"
      mo-ta="Tra cứu tài khoản có vai trò Huấn luyện viên bằng dữ liệu Account an toàn từ Backend."
    />

    <section
      class="trang-huan-luyen-vien__bo-loc"
      aria-labelledby="tieu-de-bo-loc-huan-luyen-vien"
    >
      <div class="trang-huan-luyen-vien__bo-loc-dau">
        <div>
          <p class="trang-huan-luyen-vien__nhan-khu-vuc">
            TRA CỨU HUẤN LUYỆN VIÊN
          </p>
          <h2 id="tieu-de-bo-loc-huan-luyen-vien">
            Tìm kiếm và lọc
          </h2>
        </div>
        <p class="trang-huan-luyen-vien__goi-y">
          Tìm theo tên, email, điện thoại hoặc mã PT.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiHuanLuyenVien"
        @ap-dung="apDungBoLocHuanLuyenVien"
        @dat-lai="datLaiBoLocHuanLuyenVien"
      >
        <TruongBieuMau
          id="huan-luyen-vien-tim-kiem"
          nhan="Từ khóa"
          tro-giup="Tối đa 150 ký tự."
          :loi="layLoiTruong('search')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="boLocNhap.search"
              name="search"
              type="search"
              maxlength="150"
              placeholder="Tên, email, mã PT…"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>

        <TruongBieuMau
          id="huan-luyen-vien-trang-thai"
          nhan="Trạng thái tài khoản"
          :loi="layLoiTruong('status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.status"
              name="status"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả trạng thái
              </option>
              <option
                v-for="trangThai in CAC_TRANG_THAI_TAI_KHOAN"
                :key="trangThai"
                :value="trangThai"
              >
                {{ layNhanTrangThaiTaiKhoan(trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTaiHuanLuyenVien && !daTaiHuanLuyenVienLanDau"
      nhan="Đang tải danh sách Huấn luyện viên…"
    />

    <template v-else>
      <p
        v-if="dangTaiHuanLuyenVien"
        class="trang-huan-luyen-vien__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật danh sách Huấn luyện viên…
      </p>

      <TrangThaiLoi
        v-if="loiTaiHuanLuyenVien"
        id="huan-luyen-vien-loi-danh-sach"
        tabindex="-1"
        :thong-bao="thongBaoLoi"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTaiHuanLuyenVien"
        @thu-lai="thuLaiDanhSachHuanLuyenVien"
      >
        <template #hanhDong>
          <p
            v-if="layLoiTruong('page')"
            class="trang-huan-luyen-vien__loi-phan-trang"
            role="alert"
          >
            {{ layLoiTruong('page') }}
          </p>
        </template>
      </TrangThaiLoi>

      <BangDuLieu
        v-if="daTaiHuanLuyenVienLanDau && (!loiTaiHuanLuyenVien || danhSachHuanLuyenVien.length > 0)"
        :cot="cacCotHienThi"
        :hang="danhSachHuanLuyenVien"
        tieu-de="Danh sách Huấn luyện viên"
        :thong-bao-trong="thongBaoTrong"
      >
        <template #tieuDeCot>
          <tr>
            <th
              v-for="cot in cacCotHienThi"
              :key="cot.khoa"
              scope="col"
            >
              {{ cot.nhan }}
            </th>
          </tr>
        </template>
        <template #hang="{ hang }">
          <tr
            v-for="taiKhoan in hang"
            :key="taiKhoan.id"
          >
            <td>{{ taiKhoan.id }}</td>
            <td><strong>{{ taiKhoan.name }}</strong></td>
            <td>{{ taiKhoan.email }}</td>
            <td>{{ layMaHuanLuyenVien(taiKhoan) }}</td>
            <td>{{ layDienThoai(taiKhoan) }}</td>
            <td>{{ layTenChiNhanh(taiKhoan) }}</td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThaiTaiKhoan(taiKhoan.status)"
                :nhan="layNhanTrangThaiTaiKhoan(taiKhoan.status)"
              />
            </td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThaiHoSoPt(taiKhoan)"
                :nhan="layNhanTrangThaiHoSoPt(taiKhoan)"
              />
            </td>
            <td v-if="coRouteChiTietHuanLuyenVien">
              <RouterLink
                v-if="moChiTietHuanLuyenVien(taiKhoan)"
                :to="moChiTietHuanLuyenVien(taiKhoan)"
                class="nut nut--lien-ket"
              >
                Xem chi tiết
              </RouterLink>
              <span v-else>—</span>
            </td>
          </tr>
        </template>
      </BangDuLieu>

      <p
        v-if="layLoiTruong('per_page')"
        class="trang-huan-luyen-vien__loi-phan-trang"
        role="alert"
      >
        {{ layLoiTruong('per_page') }}
      </p>

      <ThanhPhanTrang
        v-if="daTaiHuanLuyenVienLanDau && !loiTaiHuanLuyenVien && phanTrangHuanLuyenVien.last_page > 1"
        :trang-hien-tai="phanTrangHuanLuyenVien.current_page"
        :tong-so-trang="phanTrangHuanLuyenVien.last_page"
        :dang-tai="dangTaiHuanLuyenVien"
        @chuyen-trang="chuyenTrangHuanLuyenVien"
      />
    </template>
  </section>
</template>
