import { h, nextTick } from 'vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { readFileSync } from 'node:fs'
import TieuDeTrang from './tieu_de_trang.vue'
import TrangThaiTaiDuLieu from './trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from './trang_thai_trong.vue'
import TrangThaiLoi from './trang_thai_loi.vue'
import HuyHieuTrangThai from './huy_hieu_trang_thai.vue'
import TruongBieuMau from './truong_bieu_mau.vue'
import BoLocDanhSach from './bo_loc_danh_sach.vue'
import BangDuLieu from './bang_du_lieu.vue'
import ThanhPhanTrang from './thanh_phan_trang.vue'
import HopThoaiXacNhan from './hop_thoai_xac_nhan.vue'
import VungThongBao from './vung_thong_bao.vue'

const cotMau = [{ khoa: 'ten', nhan: 'Tên' }]
const hangMau = [{ id: 1, ten: 'Nguyễn Minh Anh' }]

afterEach(() => {
  vi.restoreAllMocks()
  document.body.innerHTML = ''
})

describe('shared components FE0-T07', () => {
  it('tieu_de_trang render tieu de va mo ta', () => {
    const wrapper = mount(TieuDeTrang, {
      props: { tieuDe: 'Tổng quan', moTa: 'Theo dõi hoạt động' },
    })

    expect(wrapper.get('h1').text()).toBe('Tổng quan')
    expect(wrapper.get('p').text()).toBe('Theo dõi hoạt động')
  })

  it('tieu_de_trang render slot hanh dong', () => {
    const wrapper = mount(TieuDeTrang, {
      props: { tieuDe: 'Tổng quan' },
      slots: { hanhDong: '<button>Thêm mới</button>' },
    })

    expect(wrapper.get('.tieu-de-trang__hanh-dong button').text()).toBe('Thêm mới')
  })

  it('trang_thai_tai_du_lieu co text accessible va aria busy', () => {
    const wrapper = mount(TrangThaiTaiDuLieu, { props: { nhan: 'Đang tải hội viên…' } })

    expect(wrapper.get('[role="status"]').text()).toContain('Đang tải hội viên')
    expect(wrapper.get('[role="status"]').attributes('aria-busy')).toBe('true')
  })

  it('trang_thai_trong render tieu de va mo ta', () => {
    const wrapper = mount(TrangThaiTrong, {
      props: { tieuDe: 'Chưa có buổi tập', moTa: 'Hãy tạo lịch đầu tiên.' },
    })

    expect(wrapper.get('h2').text()).toBe('Chưa có buổi tập')
    expect(wrapper.text()).toContain('Hãy tạo lịch đầu tiên.')
  })

  it('trang_thai_trong khong render hanh dong khi khong co slot', () => {
    const wrapper = mount(TrangThaiTrong)

    expect(wrapper.find('.trang-thai-trong__hanh-dong').exists()).toBe(false)
  })

  it('trang_thai_loi hien thong bao an toan qua interpolation', () => {
    const wrapper = mount(TrangThaiLoi, {
      props: { thongBao: '<script>alert(1)</script>', coTheThuLai: false },
    })

    expect(wrapper.text()).toContain('<script>alert(1)</script>')
    expect(wrapper.find('script').exists()).toBe(false)
  })

  it('trang_thai_loi phat event thu lai', async () => {
    const wrapper = mount(TrangThaiLoi, { props: { coTheThuLai: true } })

    await wrapper.get('button').trigger('click')

    expect(wrapper.emitted('thuLai')).toHaveLength(1)
  })

  it('huy_hieu_trang_thai hien status text', () => {
    const wrapper = mount(HuyHieuTrangThai, {
      props: { trangThai: 'thanh_cong', nhan: 'Đã hoàn thành' },
    })

    expect(wrapper.text()).toBe('Đã hoàn thành')
    expect(wrapper.classes()).toContain('huy-hieu-trang-thai--thanh-cong')
  })

  it('huy_hieu_trang_thai unknown fallback trung tinh', () => {
    const wrapper = mount(HuyHieuTrangThai, {
      props: { trangThai: 'trang_thai_chua_biet', nhan: 'Chưa xác định' },
    })

    expect(wrapper.classes()).toContain('huy-hieu-trang-thai--trung-tinh')
  })

  it('huy_hieu_trang_thai khong dung mau lam tin hieu duy nhat', () => {
    const wrapper = mount(HuyHieuTrangThai, {
      props: { trangThai: 'nguy_hiem', nhan: 'Cần xử lý' },
    })

    expect(wrapper.text()).toBe('Cần xử lý')
    expect(wrapper.classes()).toContain('huy-hieu-trang-thai--nguy-hiem')
  })

  it('truong_bieu_mau lien ket label voi control qua id', () => {
    const wrapper = mount(TruongBieuMau, {
      props: { id: 'email', nhan: 'Email' },
      slots: {
        default: ({ id }) => h('input', { id }),
      },
    })

    expect(wrapper.get('label').attributes('for')).toBe('email')
    expect(wrapper.get('input').attributes('id')).toBe('email')
  })

  it('truong_bieu_mau map aria describedby va invalid cho loi tro giup', () => {
    const wrapper = mount(TruongBieuMau, {
      props: { id: 'mat-khau', nhan: 'Mật khẩu', troGiup: 'Tối thiểu 12 ký tự.', loi: 'Mật khẩu chưa đúng.' },
      slots: {
        default: ({ id, ariaDescribedby, ariaInvalid }) => h('input', {
          id,
          'aria-describedby': ariaDescribedby,
          'aria-invalid': ariaInvalid,
        }),
      },
    })

    const input = wrapper.get('input')
    expect(input.attributes('aria-describedby')).toBe('mat-khau-tro-giup mat-khau-loi')
    expect(input.attributes('aria-invalid')).toBe('true')
    expect(wrapper.get('[role="alert"]').text()).toContain('Mật khẩu chưa đúng.')
  })

  it('truong_bieu_mau hien dau bat buoc nhung khong thay the required native', () => {
    const wrapper = mount(TruongBieuMau, {
      props: { id: 'email', nhan: 'Email', batBuoc: true },
      slots: { default: '<input id="email" required>' },
    })

    expect(wrapper.get('.truong-bieu-mau__bat-buoc').text()).toBe('*')
    expect(wrapper.get('input').attributes('required')).toBe('')
  })

  it('bang_du_lieu render semantic table va header scope', () => {
    const wrapper = mount(BangDuLieu, {
      props: { cot: cotMau, hang: hangMau, tieuDe: 'Danh sách hội viên' },
    })

    expect(wrapper.get('table').exists()).toBe(true)
    expect(wrapper.get('caption').text()).toBe('Danh sách hội viên')
    expect(wrapper.get('th').attributes('scope')).toBe('col')
  })

  it('bang_du_lieu hien loading state', () => {
    const wrapper = mount(BangDuLieu, { props: { cot: cotMau, dangTai: true } })

    expect(wrapper.get('[role="status"]').text()).toContain('Đang tải dữ liệu')
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('bang_du_lieu hien empty state', () => {
    const wrapper = mount(BangDuLieu, {
      props: { cot: cotMau, thongBaoTrong: 'Chưa có hội viên.' },
    })

    expect(wrapper.get('[role="status"]').text()).toBe('Chưa có hội viên.')
  })

  it('bang_du_lieu co custom row slot nhung mang hang rong van hien empty state', () => {
    const wrapper = mount(BangDuLieu, {
      props: { cot: cotMau, hang: [], thongBaoTrong: 'Chưa có hội viên.' },
      slots: { hang: '<tr><td>Không được render</td></tr>' },
    })

    expect(wrapper.get('[role="status"]').text()).toBe('Chưa có hội viên.')
    expect(wrapper.find('table').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Không được render')
  })

  it('bang_du_lieu render content tu abstraction cot va hang', () => {
    const wrapper = mount(BangDuLieu, {
      props: { cot: cotMau, hang: hangMau },
    })

    expect(wrapper.get('tbody').text()).toContain('Nguyễn Minh Anh')
  })

  it('thanh_phan_trang disable dung boundary truoc va sau', () => {
    const wrapper = mount(ThanhPhanTrang, {
      props: { trangHienTai: 2, tongSoTrang: 2 },
    })
    const buttons = wrapper.findAll('button')

    expect(buttons[0].attributes('disabled')).toBeUndefined()
    expect(buttons[1].attributes('disabled')).toBe('')
  })

  it('thanh_phan_trang disable ca hai nut khi pending', () => {
    const wrapper = mount(ThanhPhanTrang, {
      props: { trangHienTai: 2, tongSoTrang: 3, dangTai: true },
    })

    expect(wrapper.findAll('button').every((button) => button.attributes('disabled') === '')).toBe(true)
  })

  it('thanh_phan_trang emit dung trang khi chuyen trang', async () => {
    const wrapper = mount(ThanhPhanTrang, {
      props: { trangHienTai: 2, tongSoTrang: 3 },
    })

    await wrapper.findAll('button')[1].trigger('click')

    expect(wrapper.emitted('chuyenTrang')).toEqual([[3]])
  })

  it('hop_thoai_xac_nhan an khi hienThi false va hien khi true', async () => {
    const wrapper = mount(HopThoaiXacNhan, {
      props: { hienThi: false, tieuDe: 'Xóa buổi tập' },
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    await wrapper.setProps({ hienThi: true })
    expect(wrapper.get('[role="dialog"]').exists()).toBe(true)
  })

  it('hop_thoai_xac_nhan emit xac nhan', async () => {
    const wrapper = mount(HopThoaiXacNhan, {
      props: { hienThi: true, tieuDe: 'Xóa buổi tập' },
    })

    await wrapper.get('.nut--chinh').trigger('click')

    expect(wrapper.emitted('xacNhan')).toHaveLength(1)
  })

  it('hop_thoai_xac_nhan emit huy va dong khi cancel', async () => {
    const wrapper = mount(HopThoaiXacNhan, {
      props: { hienThi: true, tieuDe: 'Xóa buổi tập' },
    })

    await wrapper.get('.nut--phu').trigger('click')

    expect(wrapper.emitted('huy')).toHaveLength(1)
    expect(wrapper.emitted('dong')).toHaveLength(1)
  })

  it('hop_thoai_xac_nhan co semantics accessible', () => {
    const wrapper = mount(HopThoaiXacNhan, {
      props: {
        hienThi: true,
        tieuDe: 'Xóa buổi tập',
        moTa: 'Thao tác này không thể hoàn tác.',
        idHopThoai: 'xac-nhan-xoa',
      },
    })
    const dialog = wrapper.get('[role="dialog"]')

    expect(dialog.attributes('aria-modal')).toBe('true')
    expect(dialog.attributes('aria-labelledby')).toBe('xac-nhan-xoa-tieu-de')
    expect(dialog.attributes('aria-describedby')).toBe('xac-nhan-xoa-mo-ta')
    expect(wrapper.get('#xac-nhan-xoa-tieu-de').text()).toBe('Xóa buổi tập')
  })

  it('hop_thoai_xac_nhan trap Tab hai chieu va tra focus ve trigger khi dong', async () => {
    const trigger = document.createElement('button')
    trigger.textContent = 'Mở xác nhận'
    document.body.appendChild(trigger)
    trigger.focus()
    const wrapper = mount(HopThoaiXacNhan, {
      attachTo: document.body,
      props: { hienThi: false, tieuDe: 'Xóa buổi tập' },
    })

    try {
      await wrapper.setProps({ hienThi: true })
      await nextTick()
      expect(document.activeElement).toBe(wrapper.get('.nut--phu').element)

      wrapper.get('.nut--chinh').element.focus()
      await wrapper.get('.nut--chinh').trigger('keydown', { key: 'Tab' })
      expect(document.activeElement).toBe(wrapper.get('.nut--phu').element)

      await wrapper.get('.nut--phu').trigger('keydown', { key: 'Tab', shiftKey: true })
      expect(document.activeElement).toBe(wrapper.get('.nut--chinh').element)

      await wrapper.setProps({ hienThi: false })
      await nextTick()
      expect(document.activeElement).toBe(trigger)
    } finally {
      wrapper.unmount()
      trigger.remove()
    }
  })

  it('hop_thoai_xac_nhan giu control trong slot trong focus trap hai chieu', async () => {
    const trigger = document.createElement('button')
    document.body.appendChild(trigger)
    trigger.focus()
    const wrapper = mount(HopThoaiXacNhan, {
      attachTo: document.body,
      props: { hienThi: false, tieuDe: 'Xác nhận' },
      slots: { default: '<input id="control-trong-slot">' },
    })

    try {
      await wrapper.setProps({ hienThi: true })
      await nextTick()
      const dialog = wrapper.get('[role="dialog"]')
      const control = wrapper.get('#control-trong-slot')

      expect(dialog.find('#control-trong-slot').exists()).toBe(true)
      wrapper.get('.nut--chinh').element.focus()
      await wrapper.get('.nut--chinh').trigger('keydown', { key: 'Tab' })
      expect(document.activeElement).toBe(control.element)

      control.element.focus()
      await control.trigger('keydown', { key: 'Tab', shiftKey: true })
      expect(document.activeElement).toBe(wrapper.get('.nut--chinh').element)
    } finally {
      wrapper.unmount()
      trigger.remove()
    }
  })

  it('hop_thoai_xac_nhan ngan duplicate confirm khi pending', async () => {
    const wrapper = mount(HopThoaiXacNhan, {
      props: { hienThi: true, tieuDe: 'Xóa buổi tập', dangXuLy: true },
    })

    await wrapper.get('.nut--chinh').trigger('click')

    expect(wrapper.emitted('xacNhan')).toBeUndefined()
    expect(wrapper.get('.nut--chinh').attributes('disabled')).toBe('')
  })

  it('vung_thong_bao hien text success va error', () => {
    const wrapper = mount(VungThongBao, {
      props: {
        danhSach: [
          { id: '1', kieu: 'thanh_cong', noiDung: 'Đã lưu.' },
          { id: '2', kieu: 'nguy_hiem', noiDung: 'Có lỗi.' },
        ],
      },
    })

    expect(wrapper.text()).toContain('Đã lưu.')
    expect(wrapper.text()).toContain('Có lỗi.')
    expect(wrapper.find('[role="alert"]').text()).toBe('Có lỗi.')
  })

  it('vung_thong_bao co aria live polite', () => {
    const wrapper = mount(VungThongBao, {
      props: { danhSach: [{ id: '1', kieu: 'thanh_cong', noiDung: 'Đã lưu.' }] },
    })

    expect(wrapper.get('.vung-thong-bao').attributes('aria-live')).toBe('polite')
  })

  it('bo_loc_danh_sach render slot filter', () => {
    const wrapper = mount(BoLocDanhSach, {
      slots: { default: '<label for="tim-kiem">Tìm kiếm</label><input id="tim-kiem">' },
    })

    expect(wrapper.get('#tim-kiem').exists()).toBe(true)
    expect(wrapper.get('label').text()).toBe('Tìm kiếm')
  })

  it('bo_loc_danh_sach emit apply va reset', async () => {
    const wrapper = mount(BoLocDanhSach)

    await wrapper.get('form').trigger('submit')
    await wrapper.findAll('button')[1].trigger('click')

    expect(wrapper.emitted('apDung')).toHaveLength(1)
    expect(wrapper.emitted('datLai')).toHaveLength(1)
  })

  it('CSS tokens va visual baseline tap trung, responsive va accessible', () => {
    const css = readFileSync('src/assets/main.css', 'utf8')

    expect(css).toContain('--mau-nen-toi: #07151d')
    expect(css).toContain('--mau-chinh: #e66a2c')
    expect(css).toContain('--mau-accent: #b8f34a')
    expect(css).toContain('--font-tieu-de: "Barlow Condensed"')
    expect(css).toContain('--khoang-1: 4px')
    expect(css).toContain('--khoang-6: 32px')
    expect(css).toContain('--bo-tron-nho: 6px')
    expect(css).toContain('--bo-tron-lon: 12px')
    expect(css).toContain('--mau-bong-to: 0 24px 60px rgb(4 18 25 / 18%)')
    expect(css).toContain('@import url(')
    expect(css).not.toContain('prefers-color-scheme')
    expect(css).toContain(':focus-visible')
    expect(css).toContain('overflow-x: auto')
    expect(css).toContain('max-width: calc(100vw - var(--khoang-4) * 2)')
    expect(css).toContain('@media (max-width: 1023px)')
    expect(css).toContain('@media (max-width: 639px)')
    expect(css).toContain('prefers-reduced-motion: reduce')
  })

  it('shared components khong import API/store va khong render raw html', () => {
    const componentFiles = [
      'tieu_de_trang.vue',
      'trang_thai_tai_du_lieu.vue',
      'trang_thai_trong.vue',
      'trang_thai_loi.vue',
      'huy_hieu_trang_thai.vue',
      'truong_bieu_mau.vue',
      'bo_loc_danh_sach.vue',
      'bang_du_lieu.vue',
      'thanh_phan_trang.vue',
      'hop_thoai_xac_nhan.vue',
      'vung_thong_bao.vue',
    ]
    const source = componentFiles.map((fileName) => readFileSync(`src/components/dung_chung/${fileName}`, 'utf8')).join('\n')

    expect(source).not.toMatch(/from ['"].*(api|store)/i)
    expect(source).not.toContain('v-html')
  })
})
