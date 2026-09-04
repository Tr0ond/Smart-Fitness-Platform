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
  danhSachHoiVien,
  boLocHoiVien,
  phanTrangHoiVien,
  dangTaiHoiVien,
  loiTaiHoiVien,
  daTaiHoiVienLanDau,
} = storeToRefs(store)

const boLocNhap = reactive({
  search: '',
  status: '',
})

const CAC_COT_HOI_VIEN = Object.freeze([
  { khoa: 'id', nhan: 'ID' },
  { khoa: 'name', nhan: 'Họ tên' },
  { khoa: 'email', nhan: 'Email' },
  { khoa: 'member_profile', nhan: 'Mã hội viên' },
  { khoa: 'phone', nhan: 'Điện thoại' },
  { khoa: 'branch', nhan: 'Chi nhánh' },
  { khoa: 'status', nhan: 'Trạng thái tài khoản' },
])

const NHAN_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'Hoạt động',
  BI_KHOA: 'Bị khóa',
  NGUNG_HOAT_DONG: 'Ngừng hoạt động',
})

const KIEU_TRANG_THAI = Object.freeze({
  HOAT_DONG: 'thanh_cong',
  BI_KHOA: 'nguy_hiem',
  NGUNG_HOAT_DONG: 'canh_bao',
})

const coRouteChiTietHoiVien = computed(() => typeof router.hasRoute === 'function'
  && router.hasRoute('adminChiTietHoiVien'))

const cacCotHienThi = computed(() => coRouteChiTietHoiVien.value
  ? [...CAC_COT_HOI_VIEN, { khoa: 'thao_tac', nhan: 'Thao tác' }]
  : CAC_COT_HOI_VIEN)

function layLoiTruong(tenTruong) {
  const danhSach = loiTaiHoiVien.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layNhanTrangThai(trangThai) {
  return NHAN_TRANG_THAI[trangThai] ?? 'Không xác định'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
}

function layMaHoiVien(taiKhoan) {
  return typeof taiKhoan?.member_profile?.code === 'string'
    && taiKhoan.member_profile.code.trim() !== ''
    ? taiKhoan.member_profile.code
    : 'Chưa có mã hội viên'
}

function layTenChiNhanh(taiKhoan) {
  return typeof taiKhoan?.branch?.name === 'string' && taiKhoan.branch.name.trim() !== ''
    ? taiKhoan.branch.name
    : 'Chưa gán'
}

function layDienThoai(taiKhoan) {
  return typeof taiKhoan?.phone === 'string' && taiKhoan.phone.trim() !== ''
    ? taiKhoan.phone
    : 'Chưa cập nhật'
}

/**
 * Tao named route detail an toan cho mot Account Member trong list.
 *
 * Dau vao: taiKhoan do Backend tra ve va route registry hien tai.
 * Cach hoat dong: kiem tra route detail va positive id truoc khi tao named location;
 * khong ghep pathname tu chuoi do user cung cap.
 * Ket qua: named location `adminChiTietHoiVien` hoac null neu du lieu/route khong hop le.
 * Side effect: khong goi API, khong thay doi store va khong cap quyen.
 * Security Rule: named link chi dieu phoi UX; Backend van xac minh ADMIN va role scope.
 */
function moChiTietHoiVien(taiKhoan) {
  const id = taiKhoan?.id
  const idHopLe = (typeof id === 'number' && Number.isSafeInteger(id) && id > 0)
    || (typeof id === 'string' && /^[1-9]\d*$/.test(id.trim()))

  return coRouteChiTietHoiVien.value && idHopLe
    ? { name: 'adminChiTietHoiVien', params: { id: String(id).trim() } }
    : null
}

function coBoLocDaApDung() {
  return boLocHoiVien.value.search.trim() !== '' || boLocHoiVien.value.status !== ''
}

async function xuLyLoiTaiDanhSach() {
  if (loiTaiHoiVien.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiTaiHoiVien.value.fieldErrors,
      ['search', 'status', 'page', 'per_page'],
      {
        search: 'hoi-vien-tim-kiem',
        status: 'hoi-vien-trang-thai',
      },
      'hoi-vien-loi-danh-sach',
    )
  }

  if (loiTaiHoiVien.value?.httpStatus === 403) {
    store.xoaDuLieuHoiVien()
    await router.replace({ name: 'khongCoQuyen' })
    return true
  }

  return false
}

/**
 * Tai list Member-oriented ban dau theo query state rieng cua store.
 *
 * Dau vao: khong co; store giu applied search/status va current page.
 * Cach hoat dong: goi GET Account list voi role MEMBER do service co dinh, sau do
 * chuyen 403 sang man quyen ma khong logout tai page.
 * Ket qua: list co loading/empty/error state an toan.
 * Side effect: GET read-only; khong goi PT/Member-self API hay mutation.
 */
async function taiDuLieuHoiVien() {
  await store.taiDanhSachHoiVien()
  await xuLyLoiTaiDanhSach()
}

/**
 * Ap dung search/status cho list Hội viên va reset server page ve 1.
 *
 * Dau vao: bo loc hien thi chi gom search/status.
 * Cach hoat dong: copy draft vao scoped store; service tu dong them role MEMBER,
 * khong cho user chon role/branch hay gui field ngoai contract.
 * Ket qua: list theo query moi hoac loi 422 field-level.
 * Side effect: GET read-only; khong sua account/profile.
 */
async function apDungBoLocHoiVien() {
  await store.apDungBoLocHoiVien({ ...boLocNhap })
  await xuLyLoiTaiDanhSach()
}

/**
 * Dat lai bo loc Member va tai lai trang dau.
 *
 * Dau vao: khong co.
 * Cach hoat dong: xoa draft search/status va de store reset applied state rieng.
 * Ket qua: query default fixed-role va list authoritative.
 * Side effect: GET read-only; khong xoa Account list tong quat hay Auth session.
 */
async function datLaiBoLocHoiVien() {
  boLocNhap.search = ''
  boLocNhap.status = ''
  await store.datLaiBoLocHoiVien()
  await xuLyLoiTaiDanhSach()
}

/**
 * Chuyen trang Member theo pagination do Backend tra ve.
 *
 * Dau vao: so trang component phan trang emit.
 * Cach hoat dong: store kiem tra bien va giu applied search/status hien tai.
 * Ket qua: list/pagination moi, khong reset bo loc.
 * Side effect: GET read-only; khong tu tinh tong so trang.
 */
async function chuyenTrangHoiVien(trangMoi) {
  await store.chuyenTrangHoiVien(trangMoi)
  await xuLyLoiTaiDanhSach()
}

/**
 * Thu lai list Member sau loi 5xx/network bang dung query dang xem.
 *
 * Dau vao: khong co.
 * Cach hoat dong: chi retry thu cong GET fixed-role; 422, 403 va 404 khong bi auto-retry.
 * Ket qua: du lieu moi hoac loi normalized an toan.
 * Side effect: GET read-only; khong thay doi filter/page va khong logout.
 */
async function thuLaiDanhSachHoiVien() {
  await store.thuLaiDanhSachHoiVien()
  await xuLyLoiTaiDanhSach()
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiTaiHoiVien.value,
  'Không thể tải danh sách Hội viên. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => loiTaiHoiVien.value?.isNetworkError === true
  || loiTaiHoiVien.value?.code === 'MEMBER_LIST_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiHoiVien.value?.httpStatus)
    && loiTaiHoiVien.value.httpStatus >= 500))

const thongBaoTrong = computed(() => coBoLocDaApDung()
  ? 'Không tìm thấy Hội viên phù hợp.'
  : 'Chưa có Hội viên.')

onMounted(() => {
  boLocNhap.search = boLocHoiVien.value.search
  boLocNhap.status = boLocHoiVien.value.status
  void taiDuLieuHoiVien()
})
</script>

<template>
  <section
    class="trang-hoi-vien"
    aria-label="Danh sách Hội viên"
    :aria-busy="dangTaiHoiVien"
  >
    <TieuDeTrang
      tieu-de="Danh sách Hội viên"
      mo-ta="Tra cứu tài khoản có vai trò Hội viên bằng dữ liệu Account an toàn từ Backend."
    />

    <section
      class="trang-hoi-vien__bo-loc"
      aria-labelledby="tieu-de-bo-loc-hoi-vien"
    >
      <div class="trang-hoi-vien__bo-loc-dau">
        <div>
          <p class="trang-hoi-vien__nhan-khu-vuc">
            TRA CỨU HỘI VIÊN
          </p>
          <h2 id="tieu-de-bo-loc-hoi-vien">
            Tìm kiếm và lọc
          </h2>
        </div>
        <p class="trang-hoi-vien__goi-y">
          Tìm theo tên, email, điện thoại hoặc mã hội viên.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiHoiVien"
        @ap-dung="apDungBoLocHoiVien"
        @dat-lai="datLaiBoLocHoiVien"
      >
        <TruongBieuMau
          id="hoi-vien-tim-kiem"
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
              placeholder="Tên, email, mã hội viên…"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>

        <TruongBieuMau
          id="hoi-vien-trang-thai"
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
                {{ layNhanTrangThai(trangThai) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTaiHoiVien && !daTaiHoiVienLanDau"
      nhan="Đang tải danh sách Hội viên…"
    />

    <template v-else>
      <p
        v-if="dangTaiHoiVien"
        class="trang-hoi-vien__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật danh sách Hội viên…
      </p>

      <TrangThaiLoi
        v-if="loiTaiHoiVien"
        id="hoi-vien-loi-danh-sach"
        tabindex="-1"
        :thong-bao="thongBaoLoi"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTaiHoiVien"
        @thu-lai="thuLaiDanhSachHoiVien"
      >
        <template #hanhDong>
          <p
            v-if="layLoiTruong('page')"
            class="trang-hoi-vien__loi-phan-trang"
            role="alert"
          >
            {{ layLoiTruong('page') }}
          </p>
        </template>
      </TrangThaiLoi>

      <BangDuLieu
        v-if="daTaiHoiVienLanDau && (!loiTaiHoiVien || danhSachHoiVien.length > 0)"
        :cot="cacCotHienThi"
        :hang="danhSachHoiVien"
        tieu-de="Danh sách Hội viên"
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
            <td>{{ layMaHoiVien(taiKhoan) }}</td>
            <td>{{ layDienThoai(taiKhoan) }}</td>
            <td>{{ layTenChiNhanh(taiKhoan) }}</td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(taiKhoan.status)"
                :nhan="layNhanTrangThai(taiKhoan.status)"
              />
            </td>
            <td v-if="coRouteChiTietHoiVien">
              <RouterLink
                v-if="moChiTietHoiVien(taiKhoan)"
                :to="moChiTietHoiVien(taiKhoan)"
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
        class="trang-hoi-vien__loi-phan-trang"
        role="alert"
      >
        {{ layLoiTruong('per_page') }}
      </p>

      <ThanhPhanTrang
        v-if="daTaiHoiVienLanDau && !loiTaiHoiVien && phanTrangHoiVien.last_page > 1"
        :trang-hien-tai="phanTrangHoiVien.current_page"
        :tong-so-trang="phanTrangHoiVien.last_page"
        :dang-tai="dangTaiHoiVien"
        @chuyen-trang="chuyenTrangHoiVien"
      />
    </template>
  </section>
</template>
