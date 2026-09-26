import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import BieuMauDangNhapAdmin from '../components/Admin/DangNhap/index.vue'
import BieuMauDangNhapLeTan from '../components/LeTan/DangNhap/index.vue'
import BieuMauDangNhapPt from '../components/PT/DangNhap/index.vue'
import ChonVaiTro from '../components/Chung/ChonVaiTro/index.vue'
import DatLaiMatKhau from '../components/Chung/DatLaiMatKhau/index.vue'
import KhongCoQuyen from '../components/Chung/Loi/KhongCoQuyen.vue'
import KhongTimThay from '../components/Chung/Loi/KhongTimThay.vue'
import QuenMatKhau from '../components/Chung/QuenMatKhau/index.vue'
import BangDieuKhien from '../pages/admin/bang_dieu_khien/bang_dieu_khien.index.vue'
import BaiTapChiTiet from '../pages/admin/bai_tap/bai_tap.chi_tiet.vue'
import BaiTap from '../pages/admin/bai_tap/bai_tap.index.vue'
import BaiTapTaoMoi from '../pages/admin/bai_tap/bai_tap.tao_moi.vue'
import DungCu from '../pages/admin/dung_cu/dung_cu.index.vue'
import GiaoAnMauChiTiet from '../pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue'
import GiaoAnMau from '../pages/admin/giao_an_mau/giao_an_mau.index.vue'
import GiaoAnMauTaoMoi from '../pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue'
import GiaoAnMauTaoPhienBan from '../pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue'
import GoiTapChiTiet from '../pages/admin/goi_tap/goi_tap.chi_tiet.vue'
import GoiTap from '../pages/admin/goi_tap/goi_tap.index.vue'
import GoiTapTaoMoi from '../pages/admin/goi_tap/goi_tap.tao_moi.vue'
import HoiVienChiTiet from '../pages/admin/hoi_vien/hoi_vien.chi_tiet.vue'
import HoiVien from '../pages/admin/hoi_vien/hoi_vien.index.vue'
import HuanLuyenVien from '../pages/admin/huan_luyen_vien/huan_luyen_vien.index.vue'
import HuanLuyenVienChiTiet from '../pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.vue'
import HuanLuyenVienTaoMoi from '../pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue'
import NhanVienLeTanChiTiet from '../pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.chi_tiet.vue'
import NhanVienLeTan from '../pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.index.vue'
import PhanCongPt from '../pages/admin/phan_cong_pt/phan_cong_pt.index.vue'
import NhomCo from '../pages/admin/nhom_co/nhom_co.index.vue'
import TaiKhoanChiTiet from '../pages/admin/tai_khoan/tai_khoan.chi_tiet.vue'
import TaiKhoan from '../pages/admin/tai_khoan/tai_khoan.index.vue'
import DoiSoatThanhToan from '../pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue'
import ThanhToanChiTiet from '../pages/admin/thanh_toan/thanh_toan.chi_tiet.vue'
import ThanhToan from '../pages/admin/thanh_toan/thanh_toan.index.vue'
import { useXacThucStore } from '../stores/xac_thuc.store.js'
import { taoBaoVeTuyenDuong } from './bao_ve_tuyen_duong.js'
import { taoMetaTuyenDuongAdmin } from './dieu_huong_admin.js'

const TrangDieuPhoiRong = { template: '<span aria-hidden="true"></span>' }
const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

/**
 * Dat focus vao heading chinh sau khi Vue Router render route moi.
 *
 * Dau vao: khong co; document cua SPA sau navigation.
 * Cach hoat dong: doi mot tick render, tim h1 dau tien, them tabindex -1 khi can
 * va focus khong cuon trang de screen reader/keyboard nhan biet context moi.
 * Ket qua: tra true neu focus duoc heading, false khi page khong co h1/document.
 * Side effect: co the them tabindex presentation vao heading va thay activeElement.
 * UI Rule: sau navigation focus khong duoc roi ve body.
 */
export async function datFocusVaoTieuDeSauDieuHuong() {
  await nextTick()

  if (typeof document === 'undefined') {
    return false
  }

  const tieuDe = document.querySelector('h1')

  if (!(tieuDe instanceof HTMLElement)) {
    return false
  }

  if (!tieuDe.hasAttribute('tabindex')) {
    tieuDe.setAttribute('tabindex', '-1')
  }

  tieuDe.focus({ preventScroll: true })
  return document.activeElement === tieuDe
}

const boDinhTuyen = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dieuPhoiTrangGoc',
      component: TrangDieuPhoiRong,
      meta: { tinhNang: 'dieuPhoiTrangGoc' },
    },
    {
      path: '/chon-vai-tro',
      name: 'chonVaiTro',
      component: ChonVaiTro,
      meta: { boCuc: 'cong_khai', yeuCauXacThuc: true, tinhNang: 'chonVaiTro' },
    },
    {
      path: '/quen-mat-khau',
      name: 'quenMatKhau',
      component: QuenMatKhau,
      meta: { boCuc: 'cong_khai', congKhai: true, tinhNang: 'quenMatKhau' },
    },
    {
      path: '/dat-lai-mat-khau',
      name: 'datLaiMatKhau',
      component: DatLaiMatKhau,
      meta: { boCuc: 'cong_khai', congKhai: true, tinhNang: 'datLaiMatKhau' },
    },
    {
      // Giữ tương thích với các email reset đã phát hành dùng URL tiếng Anh.
      path: '/reset-password',
      name: 'datLaiMatKhauCu',
      component: DatLaiMatKhau,
      meta: { boCuc: 'cong_khai', congKhai: true, tinhNang: 'datLaiMatKhau' },
    },
    {
      path: '/khong-co-quyen',
      name: 'khongCoQuyen',
      component: KhongCoQuyen,
      meta: { boCuc: 'loi', congKhai: true, tinhNang: 'khongCoQuyen' },
    },
    {
      path: '/khong-tim-thay',
      name: 'khongTimThay',
      component: KhongTimThay,
      meta: { boCuc: 'loi', congKhai: true, tinhNang: 'khongTimThay' },
    },
    {
      path: '/admin/dang-nhap',
      name: 'adminDangNhap',
      component: BieuMauDangNhapAdmin,
      meta: { boCuc: 'cong_khai', congKhai: true, vaiTro: 'ADMIN', tinhNang: 'adminDangNhap' },
    },
    {
      path: '/admin/bang-dieu-khien',
      name: 'adminBangDieuKhien',
      component: BangDieuKhien,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminBangDieuKhien',
        tieuDe: 'Bảng điều khiển',
        duongDanPhanCap: [{ nhan: 'Bảng điều khiển' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/tai-khoan',
      name: 'adminTaiKhoan',
      component: TaiKhoan,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaiKhoan',
        tieuDe: 'Danh sách tài khoản',
        duongDanPhanCap: [{ nhan: 'Tài khoản' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/tai-khoan/:id',
      name: 'adminChiTietTaiKhoan',
      component: TaiKhoanChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietTaiKhoan',
        tieuDe: 'Chi tiết tài khoản',
        duongDanPhanCap: [
          { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
          { nhan: 'Chi tiết tài khoản' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/hoi-vien',
      name: 'adminHoiVien',
      component: HoiVien,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminHoiVien',
        tieuDe: 'Danh sách Hội viên',
        duongDanPhanCap: [{ nhan: 'Hội viên' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/hoi-vien/:id',
      name: 'adminChiTietHoiVien',
      component: HoiVienChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietHoiVien',
        tieuDe: 'Chi tiết Hội viên',
        duongDanPhanCap: [
          { nhan: 'Hội viên', tenTuyenDuong: 'adminHoiVien' },
          { nhan: 'Chi tiết Hội viên' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/huan-luyen-vien',
      name: 'adminHuanLuyenVien',
      component: HuanLuyenVien,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminHuanLuyenVien',
        tieuDe: 'Danh sách Huấn luyện viên',
        duongDanPhanCap: [{ nhan: 'Huấn luyện viên' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/huan-luyen-vien/tao-moi',
      name: 'adminTaoHuanLuyenVien',
      component: HuanLuyenVienTaoMoi,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaoHuanLuyenVien',
        tieuDe: 'Tạo mới Huấn luyện viên',
        duongDanPhanCap: [
          { nhan: 'Huấn luyện viên', tenTuyenDuong: 'adminHuanLuyenVien' },
          { nhan: 'Tạo mới Huấn luyện viên' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/huan-luyen-vien/:id',
      name: 'adminChiTietHuanLuyenVien',
      component: HuanLuyenVienChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietHuanLuyenVien',
        tieuDe: 'Chi tiết Huấn luyện viên',
        duongDanPhanCap: [
          { nhan: 'Huấn luyện viên', tenTuyenDuong: 'adminHuanLuyenVien' },
          { nhan: 'Chi tiết Huấn luyện viên' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/phan-cong-pt',
      name: 'adminPhanCongPt',
      component: PhanCongPt,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminPhanCongPt',
        tieuDe: 'Phân công PT',
        duongDanPhanCap: [{ nhan: 'Phân công PT' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/goi-tap',
      name: 'adminGoiTap',
      component: GoiTap,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminGoiTap',
        tieuDe: 'Gói tập',
        duongDanPhanCap: [{ nhan: 'Gói tập' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/goi-tap/tao-moi',
      name: 'adminTaoGoiTap',
      component: GoiTapTaoMoi,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaoGoiTap',
        tieuDe: 'Tạo mới gói tập',
        duongDanPhanCap: [
          { nhan: 'Gói tập', tenTuyenDuong: 'adminGoiTap' },
          { nhan: 'Tạo mới gói tập' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/goi-tap/:id',
      name: 'adminChiTietGoiTap',
      component: GoiTapChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietGoiTap',
        tieuDe: 'Chi tiết gói tập',
        duongDanPhanCap: [
          { nhan: 'Gói tập', tenTuyenDuong: 'adminGoiTap' },
          { nhan: 'Chi tiết gói tập' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/dung-cu',
      name: 'adminDungCu',
      component: DungCu,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminDungCu',
        tieuDe: 'Dụng cụ',
        duongDanPhanCap: [{ nhan: 'Dụng cụ' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/nhom-co',
      name: 'adminNhomCo',
      component: NhomCo,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminNhomCo',
        tieuDe: 'Nhóm cơ',
        duongDanPhanCap: [{ nhan: 'Nhóm cơ' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/bai-tap',
      name: 'adminBaiTap',
      component: BaiTap,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminBaiTap',
        tieuDe: 'Bài tập',
        duongDanPhanCap: [{ nhan: 'Bài tập' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/bai-tap/tao-moi',
      name: 'adminTaoBaiTap',
      component: BaiTapTaoMoi,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaoBaiTap',
        tieuDe: 'Tạo mới bài tập',
        duongDanPhanCap: [
          { nhan: 'Bài tập', tenTuyenDuong: 'adminBaiTap' },
          { nhan: 'Tạo mới bài tập' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/bai-tap/:id',
      name: 'adminChiTietBaiTap',
      component: BaiTapChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietBaiTap',
        tieuDe: 'Chi tiết bài tập',
        duongDanPhanCap: [
          { nhan: 'Bài tập', tenTuyenDuong: 'adminBaiTap' },
          { nhan: 'Chi tiết bài tập' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/giao-an-mau',
      name: 'adminGiaoAnMau',
      component: GiaoAnMau,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminGiaoAnMau',
        tieuDe: 'Giáo án mẫu',
        duongDanPhanCap: [{ nhan: 'Giáo án mẫu' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/giao-an-mau/tao-moi',
      name: 'adminTaoGiaoAnMau',
      component: GiaoAnMauTaoMoi,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaoGiaoAnMau',
        tieuDe: 'Tạo mới giáo án mẫu',
        duongDanPhanCap: [
          { nhan: 'Giáo án mẫu', tenTuyenDuong: 'adminGiaoAnMau' },
          { nhan: 'Tạo mới giáo án mẫu' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/giao-an-mau/:id/tao-phien-ban',
      name: 'adminTaoPhienBanGiaoAnMau',
      component: GiaoAnMauTaoPhienBan,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminTaoPhienBanGiaoAnMau',
        tieuDe: 'Tạo phiên bản giáo án mẫu',
        duongDanPhanCap: [
          { nhan: 'Giáo án mẫu', tenTuyenDuong: 'adminGiaoAnMau' },
          { nhan: 'Tạo phiên bản giáo án mẫu' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/giao-an-mau/:id',
      name: 'adminChiTietGiaoAnMau',
      component: GiaoAnMauChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietGiaoAnMau',
        tieuDe: 'Chi tiết giáo án mẫu',
        duongDanPhanCap: [
          { nhan: 'Giáo án mẫu', tenTuyenDuong: 'adminGiaoAnMau' },
          { nhan: 'Chi tiết giáo án mẫu' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/nhan-vien-le-tan',
      name: 'adminNhanVienLeTan',
      component: NhanVienLeTan,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminNhanVienLeTan',
        tieuDe: 'Danh sách Nhân viên lễ tân',
        duongDanPhanCap: [{ nhan: 'Nhân viên lễ tân' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/nhan-vien-le-tan/:id',
      name: 'adminChiTietNhanVienLeTan',
      component: NhanVienLeTanChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietNhanVienLeTan',
        tieuDe: 'Chi tiết Nhân viên lễ tân',
        duongDanPhanCap: [
          { nhan: 'Nhân viên lễ tân', tenTuyenDuong: 'adminNhanVienLeTan' },
          { nhan: 'Chi tiết Nhân viên lễ tân' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/thanh-toan',
      name: 'adminThanhToan',
      component: ThanhToan,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminThanhToan',
        tieuDe: 'Thanh toán',
        duongDanPhanCap: [{ nhan: 'Thanh toán' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/admin/thanh-toan/:id',
      name: 'adminChiTietThanhToan',
      component: ThanhToanChiTiet,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminChiTietThanhToan',
        tieuDe: 'Chi tiết thanh toán',
        duongDanPhanCap: [
          { nhan: 'Thanh toán', tenTuyenDuong: 'adminThanhToan' },
          { nhan: 'Chi tiết thanh toán' },
        ],
        hienTrongDieuHuong: false,
      }),
    },
    {
      path: '/admin/doi-soat-thanh-toan',
      name: 'adminDoiSoatThanhToan',
      component: DoiSoatThanhToan,
      meta: taoMetaTuyenDuongAdmin({
        tinhNang: 'adminDoiSoatThanhToan',
        tieuDe: 'Đối soát thanh toán',
        duongDanPhanCap: [{ nhan: 'Đối soát thanh toán' }],
        hienTrongDieuHuong: true,
      }),
    },
    {
      path: '/pt/dang-nhap',
      name: 'ptDangNhap',
      component: BieuMauDangNhapPt,
      meta: { boCuc: 'cong_khai', congKhai: true, vaiTro: 'PT', tinhNang: 'ptDangNhap' },
    },
    {
      path: '/le-tan/dang-nhap',
      name: 'leTanDangNhap',
      component: BieuMauDangNhapLeTan,
      meta: {
        boCuc: 'cong_khai',
        congKhai: true,
        vaiTro: 'RECEPTIONIST',
        tinhNang: 'leTanDangNhap',
      },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'khongTimThay' },
    },
  ],
})

boDinhTuyen.beforeEach(async (routeDich) => {
  const store = useXacThucStore()

  return baoVeTuyenDuong(routeDich, store)
})

boDinhTuyen.afterEach(() => {
  void datFocusVaoTieuDeSauDieuHuong()
})

export default boDinhTuyen
