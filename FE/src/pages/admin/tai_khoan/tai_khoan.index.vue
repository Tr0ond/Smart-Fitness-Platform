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
import { CAC_TRANG_THAI_TAI_KHOAN, CAC_VAI_TRO_TAI_KHOAN } from '../../../services/tai_khoan.api.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = useTaiKhoanStore()
const {
  danhSachTaiKhoan,
  boLoc,
  phanTrang,
  dangTai,
  loiTaiDanhSach,
  daTaiLanDau,
} = storeToRefs(store)

const boLocNhap = reactive({
  search: '',
  status: '',
  role: '',
})

const CAC_COT_TAI_KHOAN = Object.freeze([
  { khoa: 'id', nhan: 'ID' },
  { khoa: 'name', nhan: 'Họ tên' },
  { khoa: 'email', nhan: 'Email' },
  { khoa: 'phone', nhan: 'Điện thoại' },
  { khoa: 'branch', nhan: 'Chi nhánh' },
  { khoa: 'roles', nhan: 'Vai trò' },
  { khoa: 'status', nhan: 'Trạng thái' },
])

const coRouteChiTietTaiKhoan = computed(() => typeof router.hasRoute === 'function'
  && router.hasRoute('adminChiTietTaiKhoan'))

const cacCotHienThi = computed(() => coRouteChiTietTaiKhoan.value
  ? [...CAC_COT_TAI_KHOAN, { khoa: 'thao_tac', nhan: 'Thao tác' }]
  : CAC_COT_TAI_KHOAN)

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

function layLoiTruong(tenTruong) {
  const danhSach = loiTaiDanhSach.value?.fieldErrors?.[tenTruong]
  return Array.isArray(danhSach) ? danhSach[0] ?? '' : ''
}

function layNhanTrangThai(trangThai) {
  return NHAN_TRANG_THAI[trangThai] ?? 'Không xác định'
}

function layNhanVaiTro(maVaiTro) {
  return NHAN_VAI_TRO[maVaiTro] ?? maVaiTro ?? 'Không xác định'
}

function layKieuTrangThai(trangThai) {
  return KIEU_TRANG_THAI[trangThai] ?? 'trung_tinh'
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

function layVaiTroTaiHien(taiKhoan) {
  return Array.isArray(taiKhoan?.roles)
    ? taiKhoan.roles.filter((vaiTro) => typeof vaiTro?.code === 'string')
    : []
}

/**
 * Tao named route detail an toan tu mot dong list da duoc Backend tra ve.
 *
 * Dau vao: taiKhoan trong danh sach, chi doc id va route registry hien tai.
 * Cach hoat dong: kiem tra route detail da dang ky va id la so nguyen duong truoc
 * khi tao location; neu khong hop le tra null de khong render RouterLink hong.
 * Ket qua: named location `{ name: 'adminChiTietTaiKhoan', params: { id } }` hoac null.
 * Side effect: khong goi API, khong thay doi filter/list state va khong cap quyen.
 * Security Rule: named route chi la UX navigation; Backend van xac minh ADMIN va account scope.
 */
function moChiTietTaiKhoan(taiKhoan) {
  const id = taiKhoan?.id
  const idHopLe = (typeof id === 'number' && Number.isSafeInteger(id) && id > 0)
    || (typeof id === 'string' && /^[1-9]\d*$/.test(id.trim()))

  return coRouteChiTietTaiKhoan.value && idHopLe
    ? { name: 'adminChiTietTaiKhoan', params: { id: String(id).trim() } }
    : null
}

function coBoLocDaApDung() {
  return boLoc.value.search.trim() !== '' || boLoc.value.status !== '' || boLoc.value.role !== ''
}

async function xuLyLoiTaiDanhSach() {
  if (loiTaiDanhSach.value?.httpStatus === 422) {
    await datFocusVaoTruongLoiDau(
      loiTaiDanhSach.value.fieldErrors,
      ['search', 'status', 'role', 'page', 'per_page'],
      {
        search: 'tai-khoan-tim-kiem',
        status: 'tai-khoan-trang-thai',
        role: 'tai-khoan-vai-tro',
      },
      'tai-khoan-loi-danh-sach',
    )
  }

  if (loiTaiDanhSach.value?.httpStatus === 403) {
    store.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return true
  }

  return false
}

/**
 * Tai danh sach Account ban dau hoac theo query dang ap dung trong Store.
 *
 * Dau vao: khong co; Store so huu applied filter va current page.
 * Cach hoat dong: goi read action, sau do chuyen 403 sang error route ma khong logout.
 * Ket qua: page co du lieu, loading, empty hoac loi an toan theo state Store.
 * Side effect: phat sinh GET /admin/accounts; khong mutation va khong tu chon actor.
 */
async function taiDuLieuTaiKhoan() {
  await store.taiDanhSachTaiKhoan()
  await xuLyLoiTaiDanhSach()
}

/**
 * Ap dung search/status/role tu form va reset pagination ve trang 1.
 *
 * Dau vao: boLocNhap cua form, chi gui cac field Backend cho phep.
 * Cach hoat dong: copy filter vao Store, bo qua query URL va de Store quan ly race guard.
 * Ket qua: list phan anh dung filter moi hoac loi 422 field-level.
 * Side effect: phat sinh GET read-only; khong tao link detail hay goi mutation.
 */
async function apDungBoLocTaiKhoan() {
  await store.apDungBoLocTaiKhoan({ ...boLocNhap })
  await xuLyLoiTaiDanhSach()
}

/**
 * Dat lai cac filter hien thi va applied filter, sau do tai page dau.
 *
 * Dau vao: khong co.
 * Cach hoat dong: reset draft form va goi action Store reset query state.
 * Ket qua: form rong, page 1 va list default Backend.
 * Side effect: phat sinh GET read-only; khong xoa auth session.
 */
async function datLaiBoLocTaiKhoan() {
  boLocNhap.search = ''
  boLocNhap.status = ''
  boLocNhap.role = ''
  await store.datLaiBoLocTaiKhoan()
  await xuLyLoiTaiDanhSach()
}

/**
 * Chuyen page bang applied search/status/role hien tai.
 *
 * Dau vao: so trang do component pagination emit.
 * Cach hoat dong: de Store kiem tra bien server va giu nguyen applied filter.
 * Ket qua: list va pagination theo page moi.
 * Side effect: phat sinh GET read-only; khong reset bo loc.
 */
async function chuyenTrangTaiKhoan(trangMoi) {
  await store.chuyenTrangTaiKhoan(trangMoi)
  await xuLyLoiTaiDanhSach()
}

/**
 * Thu lai list bang dung filter/page hien tai sau loi 5xx hoac network.
 *
 * Dau vao: khong co.
 * Cach hoat dong: goi lai Store retry thu cong, khong auto retry va khong doi query.
 * Ket qua: giu context thao tac cua Admin trong khi hien thi ket qua moi.
 * Side effect: phat sinh GET read-only; khong logout khi 403/5xx/network.
 */
async function thuLaiTaiDanhSachTaiKhoan() {
  await store.thuLaiTaiDanhSachTaiKhoan()
  await xuLyLoiTaiDanhSach()
}

const thongBaoLoi = computed(() => layThongBaoLoiApi(
  loiTaiDanhSach.value,
  'Không thể tải danh sách tài khoản. Vui lòng thử lại sau.',
))

const coTheThuLai = computed(() => loiTaiDanhSach.value?.isNetworkError === true
  || loiTaiDanhSach.value?.code === 'ACCOUNT_LIST_RESPONSE_INVALID'
  || (Number.isInteger(loiTaiDanhSach.value?.httpStatus)
    && loiTaiDanhSach.value.httpStatus >= 500))

const thongBaoTrong = computed(() => coBoLocDaApDung()
  ? 'Không tìm thấy tài khoản phù hợp với bộ lọc.'
  : 'Chưa có tài khoản nào để hiển thị.')

onMounted(() => {
  boLocNhap.search = boLoc.value.search
  boLocNhap.status = boLoc.value.status
  boLocNhap.role = boLoc.value.role
  void taiDuLieuTaiKhoan()
})
</script>

<template>
  <section
    class="trang-tai-khoan"
    aria-label="Danh sách tài khoản"
    :aria-busy="dangTai"
  >
    <TieuDeTrang
      tieu-de="Danh sách tài khoản"
      mo-ta="Tra cứu tài khoản và vai trò theo dữ liệu được Backend phân quyền."
    />

    <section
      class="trang-tai-khoan__bo-loc"
      aria-labelledby="tieu-de-bo-loc-tai-khoan"
    >
      <div class="trang-tai-khoan__bo-loc-dau">
        <div>
          <p class="trang-tai-khoan__nhan-khu-vuc">
            TRA CỨU TÀI KHOẢN
          </p>
          <h2 id="tieu-de-bo-loc-tai-khoan">
            Tìm kiếm và lọc
          </h2>
        </div>
        <p class="trang-tai-khoan__goi-y">
          Tìm theo tên, email, điện thoại hoặc mã hồ sơ.
        </p>
      </div>

      <BoLocDanhSach
        :dang-xu-ly="dangTai"
        @ap-dung="apDungBoLocTaiKhoan"
        @dat-lai="datLaiBoLocTaiKhoan"
      >
        <TruongBieuMau
          id="tai-khoan-tim-kiem"
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
              placeholder="Tên, email, mã hồ sơ…"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>

        <TruongBieuMau
          id="tai-khoan-trang-thai"
          nhan="Trạng thái"
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

        <TruongBieuMau
          id="tai-khoan-vai-tro"
          nhan="Vai trò"
          :loi="layLoiTruong('role')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="boLocNhap.role"
              name="role"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="">
                Tất cả vai trò
              </option>
              <option
                v-for="vaiTro in CAC_VAI_TRO_TAI_KHOAN"
                :key="vaiTro"
                :value="vaiTro"
              >
                {{ layNhanVaiTro(vaiTro) }}
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </BoLocDanhSach>
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTai && !daTaiLanDau"
      nhan="Đang tải danh sách tài khoản…"
    />

    <template v-else>
      <p
        v-if="dangTai"
        class="trang-tai-khoan__dang-tai-lai"
        role="status"
        aria-live="polite"
      >
        Đang cập nhật danh sách…
      </p>

      <TrangThaiLoi
        v-if="loiTaiDanhSach"
        id="tai-khoan-loi-danh-sach"
        tabindex="-1"
        :thong-bao="thongBaoLoi"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTai"
        @thu-lai="thuLaiTaiDanhSachTaiKhoan"
      >
        <template #hanhDong>
          <p
            v-if="layLoiTruong('page')"
            class="trang-tai-khoan__loi-phan-trang"
            role="alert"
          >
            {{ layLoiTruong('page') }}
          </p>
        </template>
      </TrangThaiLoi>

      <BangDuLieu
        v-if="daTaiLanDau && (!loiTaiDanhSach || danhSachTaiKhoan.length > 0)"
        :cot="cacCotHienThi"
        :hang="danhSachTaiKhoan"
        tieu-de="Danh sách tài khoản"
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
            <td>
              <strong>{{ taiKhoan.name }}</strong>
            </td>
            <td>{{ taiKhoan.email }}</td>
            <td>{{ layDienThoai(taiKhoan) }}</td>
            <td>{{ layTenChiNhanh(taiKhoan) }}</td>
            <td>
              <div class="trang-tai-khoan__vai-tro">
                <template v-if="layVaiTroTaiHien(taiKhoan).length > 0">
                  <HuyHieuTrangThai
                    v-for="vaiTro in layVaiTroTaiHien(taiKhoan)"
                    :key="vaiTro.assignment_id ?? vaiTro.code"
                    :trang-thai="vaiTro.active === true ? 'thong_tin' : 'canh_bao'"
                    :nhan="vaiTro.active === true
                      ? layNhanVaiTro(vaiTro.code)
                      : `${layNhanVaiTro(vaiTro.code)} · Đã thu hồi`"
                  />
                </template>
                <span v-else>Chưa gán</span>
              </div>
            </td>
            <td>
              <HuyHieuTrangThai
                :trang-thai="layKieuTrangThai(taiKhoan.status)"
                :nhan="layNhanTrangThai(taiKhoan.status)"
              />
            </td>
            <td v-if="coRouteChiTietTaiKhoan">
              <RouterLink
                v-if="moChiTietTaiKhoan(taiKhoan)"
                :to="moChiTietTaiKhoan(taiKhoan)"
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
        class="trang-tai-khoan__loi-phan-trang"
        role="alert"
      >
        {{ layLoiTruong('per_page') }}
      </p>

      <ThanhPhanTrang
        v-if="daTaiLanDau && !loiTaiDanhSach && phanTrang.last_page > 1"
        :trang-hien-tai="phanTrang.current_page"
        :tong-so-trang="phanTrang.last_page"
        :dang-tai="dangTai"
        @chuyen-trang="chuyenTrangTaiKhoan"
      />
    </template>
  </section>
</template>
