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
  danhSachNhanVienLeTan,
  boLocNhanVienLeTan,
  phanTrangNhanVienLeTan,
  dangTaiNhanVienLeTan,
  loiTaiNhanVienLeTan,
  daTaiNhanVienLeTanLanDau,
} = storeToRefs(store)

const boLocNhap = reactive({ search: '', status: '' })

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

const CAC_COT_NHAN_VIEN_LE_TAN = Object.freeze([
  { khoa: 'id', nhan: 'ID' },
  { khoa: 'name', nhan: 'Họ tên' },
  { khoa: 'email', nhan: 'Email' },
  { khoa: 'phone', nhan: 'Điện thoại' },
  { khoa: 'branch', nhan: 'Chi nhánh' },
  { khoa: 'status', nhan: 'Trạng thái tài khoản' },
  { khoa: 'role', nhan: 'Vai trò' },
])

const coRouteChiTietNhanVienLeTan = computed(() => typeof router.hasRoute === 'function'
  && router.hasRoute('adminChiTietNhanVienLeTan'))

const cacCotHienThi = computed(() => coRouteChiTietNhanVienLeTan.value
  ? [...CAC_COT_NHAN_VIEN_LE_TAN, { khoa: 'thao_tac', nhan: 'Thao tác' }]
  : CAC_COT_NHAN_VIEN_LE_TAN)

function layLoiTruong(tenTruong) {
  const danhSach = loiTaiNhanVienLeTan.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layNhanTrangThai(trangThai) {
  return NHAN_TRANG_THAI[trangThai] ?? 'Không xác định'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
}

function layDienThoai(taiKhoan) {
  return typeof taiKhoan?.phone === 'string' && taiKhoan.phone.trim() !== ''
    ? taiKhoan.phone
    : 'Chưa cập nhật'
}

function layTenChiNhanh(taiKhoan) {
  const ten = taiKhoan?.branch?.name
  const ma = taiKhoan?.branch?.code

  if (typeof ten !== 'string' || ten.trim() === '') {
    return 'Chưa gán'
  }

  return typeof ma === 'string' && ma.trim() !== '' ? `${ten} (${ma})` : ten
}

/**
 * Mo detail bang named route cho account Receptionist co id hop le.
 *
 * Dau vao: DTO Account tu list va route registry hien tai.
 * Cach hoat dong: chi tao named location sau khi kiem tra positive id; khong ghep URL.
 * Ket qua: location detail hoac null neu DTO/route khong hop le.
 * Side effect: khong goi API, khong mutation va khong thay doi authorization.
 * Security Rule: active RECEPTIONIST da duoc Store/Backend xac nhan; route khong cap quyen.
 */
function xuLyMoChiTietNhanVienLeTan(taiKhoan) {
  const id = taiKhoan?.id
  const idHopLe = (typeof id === 'number' && Number.isSafeInteger(id) && id > 0)
    || (typeof id === 'string' && /^[1-9]\d*$/.test(id.trim()))

  return coRouteChiTietNhanVienLeTan.value && idHopLe
    ? { name: 'adminChiTietNhanVienLeTan', params: { id: String(id).trim() } }
    : null
}

function coBoLocDaApDung() {
  return boLocNhanVienLeTan.value.search.trim() !== '' || boLocNhanVienLeTan.value.status !== ''
}

async function xuLyLoiTaiDanhSach() {
  if (loiTaiNhanVienLeTan.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiTaiNhanVienLeTan.value.fieldErrors,
      ['search', 'status', 'page', 'per_page'],
      {
        search: 'nhan-vien-le-tan-tim-kiem',
        status: 'nhan-vien-le-tan-trang-thai',
      },
      'nhan-vien-le-tan-loi-danh-sach',
    )
  }

  if (loiTaiNhanVienLeTan.value?.httpStatus === 403) {
    store.xoaDuLieuNhanVienLeTan()
    await router.replace({ name: 'khongCoQuyen' })
    return true
  }

  return false
}

/**
 * Tai list Receptionist-oriented ban dau theo query state scoped.
 *
 * Dau vao: khong co; Store giu filter/page hien tai va service co dinh role.
 * Cach hoat dong: GET Account list, sau do xu ly 403 fail-closed tai page.
 * Ket qua: list/loading/empty/error an toan.
 * Side effect: GET read-only; khong goi profile/shift hay mutation.
 */
async function taiDanhSachNhanVienLeTan() {
  await store.taiDanhSachNhanVienLeTan()
  await xuLyLoiTaiDanhSach()
}

/**
 * Ap dung search/status Receptionist va reset server page ve 1.
 *
 * Dau vao: draft search/status tu form; khong co role/branch selector.
 * Cach hoat dong: Store allow-list field va service gan role RECEPTIONIST.
 * Ket qua: list theo filter moi hoac loi validation field-level.
 * Side effect: GET read-only; khong sua Account/Member state.
 */
async function apDungBoLocNhanVienLeTan() {
  await store.apDungBoLocNhanVienLeTan({ ...boLocNhap })
  await xuLyLoiTaiDanhSach()
}

/**
 * Dat lai filter Receptionist ve default va tai lai trang dau.
 *
 * Dau vao: khong co.
 * Cach hoat dong: reset draft va scoped applied state.
 * Ket qua: fixed-role list voi query mac dinh.
 * Side effect: GET read-only; khong logout va khong cham state view khac.
 */
async function datLaiBoLocNhanVienLeTan() {
  boLocNhap.search = ''
  boLocNhap.status = ''
  await store.datLaiBoLocNhanVienLeTan()
  await xuLyLoiTaiDanhSach()
}

/**
 * Chuyen trang theo server pagination cua Receptionist list.
 *
 * Dau vao: so trang emit tu component dung chung.
 * Cach hoat dong: Store chan trang ngoai bien va giu applied filter.
 * Ket qua: list/pagination authoritative moi.
 * Side effect: GET read-only; khong tu tinh total/last_page.
 */
async function chuyenTrangNhanVienLeTan(trangMoi) {
  await store.chuyenTrangNhanVienLeTan(trangMoi)
  await xuLyLoiTaiDanhSach()
}

/**
 * Retry thu cong list Receptionist sau network/5xx.
 *
 * Dau vao: khong co; dung filter/page dang xem.
 * Cach hoat dong: chi goi GET lai, khong auto-retry 422/403/404.
 * Ket qua: list moi hoac normalized error.
 * Side effect: khong mutation va khong logout.
 */
async function thuLaiDanhSachNhanVienLeTan() {
  await store.thuLaiDanhSachNhanVienLeTan()
  await xuLyLoiTaiDanhSach()
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiTaiNhanVienLeTan.value,
  'Không thể tải danh sách Nhân viên lễ tân. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => loiTaiNhanVienLeTan.value?.isNetworkError === true
  || loiTaiNhanVienLeTan.value?.code === 'RECEPTIONIST_LIST_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiNhanVienLeTan.value?.httpStatus)
    && loiTaiNhanVienLeTan.value.httpStatus >= 500))

const thongBaoTrong = computed(() => coBoLocDaApDung()
  ? 'Không tìm thấy Nhân viên lễ tân phù hợp'
  : 'Chưa có Nhân viên lễ tân')

onMounted(() => {
  boLocNhap.search = boLocNhanVienLeTan.value.search
  boLocNhap.status = boLocNhanVienLeTan.value.status
  void taiDanhSachNhanVienLeTan()
})
</script>

<template>
  <section
    class="trang-nhan-vien-le-tan"
    aria-label="Danh sách Nhân viên lễ tân"
    :aria-busy="dangTaiNhanVienLeTan"
  >
    <TieuDeTrang
      tieu-de="Danh sách Nhân viên lễ tân"
      mo-ta="Tra cứu tài khoản đang có vai trò Nhân viên lễ tân bằng dữ liệu Account an toàn từ Backend."
    />

    <section
      class="trang-nhan-vien-le-tan__bo-loc"
      aria-labelledby="tieu-de-bo-loc-nhan-vien-le-tan"
    >
      <div class="trang-nhan-vien-le-tan__bo-loc-dau">
        <div>
          <p class="trang-nhan-vien-le-tan__nhan-khu-vuc">
            TRA CỨU NHÂN VIÊN LỄ TÂN
          </p>
          <h2 id="tieu-de-bo-loc-nhan-vien-le-tan">
            Tìm kiếm và lọc
          </h2>
        </div>
        <p class="trang-nhan-vien-le-tan__goi-y">
          Tìm theo tên, email hoặc điện thoại.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTaiNhanVienLeTan"
        @ap-dung="apDungBoLocNhanVienLeTan"
        @dat-lai="datLaiBoLocNhanVienLeTan"
      >
        <TruongBieuMau
          id="nhan-vien-le-tan-tim-kiem"
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
              placeholder="Tên, email, điện thoại…"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>

        <TruongBieuMau
          id="nhan-vien-le-tan-trang-thai"
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
      v-if="dangTaiNhanVienLeTan && !daTaiNhanVienLeTanLanDau"
      nhan="Đang tải danh sách Nhân viên lễ tân…"
    />

    <template v-else>
      <p
        v-if="dangTaiNhanVienLeTan"
        class="trang-nhan-vien-le-tan__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật danh sách Nhân viên lễ tân…
      </p>

      <TrangThaiLoi
        v-if="loiTaiNhanVienLeTan"
        id="nhan-vien-le-tan-loi-danh-sach"
        tabindex="-1"
        :thong-bao="thongBaoLoi"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTaiNhanVienLeTan"
        @thu-lai="thuLaiDanhSachNhanVienLeTan"
      >
        <template #hanhDong>
          <p
            v-if="layLoiTruong('page')"
            class="trang-nhan-vien-le-tan__loi-phan-trang"
            role="alert"
          >
            {{ layLoiTruong('page') }}
          </p>
        </template>
      </TrangThaiLoi>

      <BangDuLieu
        v-if="daTaiNhanVienLeTanLanDau && (!loiTaiNhanVienLeTan || danhSachNhanVienLeTan.length > 0)"
        :cot="cacCotHienThi"
        :hang="danhSachNhanVienLeTan"
        tieu-de="Danh sách Nhân viên lễ tân"
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
            <td>{{ layDienThoai(taiKhoan) }}</td>
            <td>{{ layTenChiNhanh(taiKhoan) }}</td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(taiKhoan.status)"
                :nhan="layNhanTrangThai(taiKhoan.status)"
              />
            </td>
            <td>
              <HuyHieuTrangThai
                trang-thai="thong_tin"
                nhan="Nhân viên lễ tân"
              />
            </td>
            <td v-if="coRouteChiTietNhanVienLeTan">
              <RouterLink
                v-if="xuLyMoChiTietNhanVienLeTan(taiKhoan)"
                :to="xuLyMoChiTietNhanVienLeTan(taiKhoan)"
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
        class="trang-nhan-vien-le-tan__loi-phan-trang"
        role="alert"
      >
        {{ layLoiTruong('per_page') }}
      </p>

      <ThanhPhanTrang
        v-if="daTaiNhanVienLeTanLanDau && !loiTaiNhanVienLeTan && phanTrangNhanVienLeTan.last_page > 1"
        :trang-hien-tai="phanTrangNhanVienLeTan.current_page"
        :tong-so-trang="phanTrangNhanVienLeTan.last_page"
        :dang-tai="dangTaiNhanVienLeTan"
        @chuyen-trang="chuyenTrangNhanVienLeTan"
      />
    </template>
  </section>
</template>
