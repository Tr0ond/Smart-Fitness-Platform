import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { nextTick } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { readFileSync } from 'node:fs'
import { createMemoryHistory, createRouter } from 'vue-router'
import App from '../App.vue'
import BoCucAdmin from './bo_cuc_admin.vue'
import BoCucLeTan from './bo_cuc_le_tan.vue'
import BoCucPt from './bo_cuc_pt.vue'
import { useXacThucStore } from '../stores/xac_thuc.store.js'
import boDinhTuyen from '../router/index.js'

let wrappers = []

function datChieuRongKhungNhin(chieuRong) {
  Object.defineProperty(window, 'innerWidth', {
    configurable: true,
    writable: true,
    value: chieuRong,
  })
  window.dispatchEvent(new Event('resize'))
}

function taoRouterShell() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/khu-vuc', name: 'khuVuc', component: { template: '<div />' } },
      { path: '/chon-vai-tro', name: 'chonVaiTro', component: { template: '<div />' } },
    ],
  })
}

async function mountShell(component, vaiTro) {
  const pinia = createPinia()
  setActivePinia(pinia)
  const router = taoRouterShell()
  await router.push({ name: 'khuVuc' })
  const store = useXacThucStore()

  store.token = 'token-khong-duoc-hien-thi'
  store.nguoiDung = {
    name: 'Nguyễn Minh Anh',
    email: 'minh.anh@example.com',
    token: 'du-lieu-nhay-cam-khong-duoc-hien-thi',
    roles: [vaiTro],
  }
  store.vaiTro = [vaiTro]
  store.vaiTroDangDung = vaiTro
  store.daKhoiPhucPhien = true

  const wrapper = mount(component, {
    global: { plugins: [pinia, router] },
    slots: { default: '<p>Nội dung khu vực kiểm thử</p>' },
  })
  wrappers.push(wrapper)
  await nextTick()

  return { router, store, wrapper }
}

async function mountRoute(path) {
  const pinia = createPinia()
  setActivePinia(pinia)
  await boDinhTuyen.push(path)
  const wrapper = mount(App, {
    global: { plugins: [pinia, boDinhTuyen] },
  })
  wrappers.push(wrapper)
  await nextTick()
  return wrapper
}

async function mountAdminCoBusinessRoutes(path = '/khu-vuc') {
  const pinia = createPinia()
  setActivePinia(pinia)
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/khu-vuc', name: 'khuVuc', component: { template: '<h1>Khu vực</h1>' } },
      {
        path: '/admin/tai-khoan',
        name: 'adminTaiKhoan',
        component: { template: '<h1>Tài khoản</h1>' },
        meta: {
          duongDanPhanCap: [{ nhan: 'Tài khoản' }],
        },
      },
      {
        path: '/admin/tai-khoan/:id',
        name: 'adminChiTietTaiKhoan',
        component: { template: '<h1>Chi tiết tài khoản</h1>' },
        meta: {
          duongDanPhanCap: [
            { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
            { nhan: 'Chi tiết tài khoản' },
          ],
        },
      },
      { path: '/chon-vai-tro', name: 'chonVaiTro', component: { template: '<div />' } },
    ],
  })
  await router.push(path)
  const store = useXacThucStore()
  store.token = 'token-khong-duoc-hien-thi'
  store.nguoiDung = { id: 1, name: 'Admin Test', email: 'admin@example.com' }
  store.vaiTro = ['ADMIN']
  store.vaiTroDangDung = 'ADMIN'
  store.daKhoiPhucPhien = true

  const wrapper = mount(BoCucAdmin, {
    global: { plugins: [pinia, router] },
    slots: { default: '<p>Nội dung Admin</p>' },
  })
  wrappers.push(wrapper)
  await nextTick()

  return { router, wrapper }
}

describe('layout integration FE0-T06', () => {
  beforeEach(() => {
    datChieuRongKhungNhin(1024)
    sessionStorage.clear()
    localStorage.clear()
    vi.restoreAllMocks()
  })

  afterEach(() => {
    wrappers.forEach((wrapper) => wrapper.unmount())
    wrappers = []
    datChieuRongKhungNhin(1024)
  })

  it.each([
    ['/chon-vai-tro', 'bo-cuc-cong-khai'],
    ['/quen-mat-khau', 'bo-cuc-cong-khai'],
    ['/dat-lai-mat-khau', 'bo-cuc-cong-khai'],
    ['/admin/dang-nhap', 'bo-cuc-cong-khai'],
    ['/pt/dang-nhap', 'bo-cuc-cong-khai'],
    ['/le-tan/dang-nhap', 'bo-cuc-cong-khai'],
    ['/khong-co-quyen', 'bo-cuc-loi'],
    ['/khong-tim-thay', 'bo-cuc-loi'],
    ['/duong-dan-khong-ton-tai', 'bo-cuc-loi'],
  ])('route %s render layout %s', async (path, classLayout) => {
    const wrapper = await mountRoute(path)

    expect(wrapper.find(`.${classLayout}`).exists()).toBe(true)
  })

  it('public layout dùng main, card responsive và không có actor shell', async () => {
    const wrapper = await mountRoute('/chon-vai-tro')

    expect(wrapper.find('main').exists()).toBe(true)
    expect(wrapper.find('.bo-cuc-cong-khai__the').exists()).toBe(true)
    expect(wrapper.find('.thanh-ben-dieu-huong').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Authorization')
    expect(wrapper.text()).not.toContain('token')
  })

  it('error layout chỉ hiển thị lỗi trung tính, không auto logout hoặc lộ đường dẫn gốc', async () => {
    const store = useXacThucStore()
    const dangXuat = vi.spyOn(store, 'dangXuat')
    const wrapper = await mountRoute('/khong-co-quyen')

    expect(wrapper.find('main').exists()).toBe(true)
    expect(wrapper.find('.thanh-ben-dieu-huong').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('/khong-co-quyen')
    expect(dangXuat).not.toHaveBeenCalled()
  })

  it.each([
    [BoCucAdmin, 'ADMIN', 'Quản trị viên'],
    [BoCucPt, 'PT', 'Huấn luyện viên'],
    [BoCucLeTan, 'RECEPTIONIST', 'Lễ tân'],
  ])('authenticated shell hiển thị actor %s và chỉ identity an toàn', async (component, vaiTro, nhanVaiTro) => {
    const { wrapper } = await mountShell(component, vaiTro)

    expect(wrapper.get('[data-testid="nhan-vai-tro"]').text()).toBe(nhanVaiTro)
    expect(wrapper.get('[data-testid="ten-nguoi-dung"]').text()).toContain('Nguyễn Minh Anh')
    expect(wrapper.get('[data-testid="email-nguoi-dung"]').text()).toContain('minh.anh@example.com')
    expect(wrapper.text()).not.toContain('token-khong-duoc-hien-thi')
    expect(wrapper.text()).not.toContain('du-lieu-nhay-cam-khong-duoc-hien-thi')
    expect(wrapper.findAll('a[href="#"]').length).toBe(0)
    expect(wrapper.findAll('.thanh-ben-dieu-huong__lien-ket').length).toBe(0)
    expect(wrapper.text()).toContain('Chưa có mục điều hướng.')
  })

  it('shell không render identity, menu hoặc slot nhạy cảm trước khi restore hoàn tất', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const router = taoRouterShell()
    await router.push({ name: 'khuVuc' })
    const wrapper = mount(BoCucAdmin, {
      props: { dangKhoiPhuc: true },
      global: { plugins: [pinia, router] },
      slots: { default: '<p data-testid="noi-dung-nhay-cam">Dữ liệu actor</p>' },
    })
    wrappers.push(wrapper)

    expect(wrapper.get('[role="status"]').text()).toContain('khôi phục phiên')
    expect(wrapper.find('.thanh-tren').exists()).toBe(false)
    expect(wrapper.find('.thanh-ben-dieu-huong').exists()).toBe(false)
    expect(wrapper.find('[data-testid="noi-dung-nhay-cam"]').exists()).toBe(false)
  })

  it('mobile drawer có nút mở/đóng, aria-expanded và đóng khi đổi route', async () => {
    const { router, wrapper } = await mountShell(BoCucAdmin, 'ADMIN')
    const nutMenu = wrapper.get('[data-testid="nut-mo-menu"]')

    expect(nutMenu.attributes('aria-expanded')).toBe('false')
    expect(nutMenu.attributes('aria-controls')).toBe('thanh-ben-admin')

    await nutMenu.trigger('click')
    await nextTick()
    expect(nutMenu.attributes('aria-expanded')).toBe('true')
    expect(wrapper.get('.khung-ung-dung').classes()).toContain('khung-ung-dung--mo')
    expect(wrapper.get('.thanh-ben-dieu-huong').classes()).toContain('thanh-ben-dieu-huong--dang-mo')
    expect(wrapper.find('.khung-ung-dung__lop-phu').exists()).toBe(true)

    await router.push({ name: 'chonVaiTro' })
    await nextTick()
    expect(nutMenu.attributes('aria-expanded')).toBe('false')
    expect(wrapper.get('.khung-ung-dung').classes()).not.toContain('khung-ung-dung--mo')
    expect(wrapper.find('.khung-ung-dung__lop-phu').exists()).toBe(false)
  })

  it('mobile drawer inert khi dong, trap Tab va tra focus khi Escape', async () => {
    datChieuRongKhungNhin(390)
    const { wrapper } = await mountAdminCoBusinessRoutes()
    document.body.appendChild(wrapper.element)
    const nutMenu = wrapper.get('[data-testid="nut-mo-menu"]')
    const thanhBen = wrapper.get('.thanh-ben-dieu-huong')

    expect(thanhBen.attributes('inert')).toBe('')
    expect(thanhBen.attributes('aria-hidden')).toBe('true')

    await nutMenu.trigger('click')
    await nextTick()
    const nutDong = wrapper.get('.thanh-ben-dieu-huong__nut-dong')
    const lienKetCuoi = wrapper.get('.thanh-ben-dieu-huong__lien-ket')
    expect(thanhBen.attributes('inert')).toBeUndefined()
    expect(document.activeElement).toBe(nutDong.element)
    expect(wrapper.get('.khung-ung-dung__noi-dung').attributes('inert')).toBe('')

    lienKetCuoi.element.focus()
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }))
    expect(document.activeElement).toBe(nutDong.element)

    nutDong.element.focus()
    window.dispatchEvent(new KeyboardEvent('keydown', {
      key: 'Tab',
      shiftKey: true,
      bubbles: true,
    }))
    expect(document.activeElement).toBe(lienKetCuoi.element)

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
    await nextTick()
    expect(nutMenu.attributes('aria-expanded')).toBe('false')
    expect(thanhBen.attributes('inert')).toBe('')
    expect(document.activeElement).toBe(nutMenu.element)
  })

  it('logout đi qua Auth Store, đóng drawer và chuyển về chooser neutral', async () => {
    const { router, store, wrapper } = await mountShell(BoCucAdmin, 'ADMIN')
    const dangXuat = vi.spyOn(store, 'dangXuat').mockImplementation(async () => {
      store.xoaPhienDangNhap()
      return true
    })

    await wrapper.get('[data-testid="nut-mo-menu"]').trigger('click')
    await wrapper.get('[data-testid="nut-dang-xuat"]').trigger('click')
    await flushPromises()

    expect(dangXuat).toHaveBeenCalledTimes(1)
    expect(router.currentRoute.value.name).toBe('chonVaiTro')
    expect(wrapper.find('.thanh-ben-dieu-huong').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Nguyễn Minh Anh')
  })

  it('401 cleanup làm shell mất actor và identity stale theo Store reactive state', async () => {
    const { store, wrapper } = await mountShell(BoCucPt, 'PT')

    store.xoaPhienDangNhap()
    await nextTick()

    expect(wrapper.find('[data-testid="nhan-vai-tro"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Nguyễn Minh Anh')
    expect(wrapper.text()).not.toContain('minh.anh@example.com')
  })

  it('Admin shell nhận menu đã lọc theo route registry và breadcrumb route meta', async () => {
    const { wrapper } = await mountAdminCoBusinessRoutes('/admin/tai-khoan/123')

    expect(wrapper.findAll('.thanh-ben-dieu-huong__lien-ket')).toHaveLength(1)
    expect(wrapper.get('.thanh-ben-dieu-huong__lien-ket').text()).toBe('Tài khoản')
    expect(wrapper.get('.duong-dan-phan-cap').text()).toContain('Tài khoản')
    expect(wrapper.get('.duong-dan-phan-cap').text()).toContain('Chi tiết tài khoản')
    expect(wrapper.get('.duong-dan-phan-cap [aria-current="page"]').text()).toBe('Chi tiết tài khoản')
  })

  it('menu Admin khong tao link cho business route chua register', async () => {
    const { wrapper } = await mountAdminCoBusinessRoutes()

    expect(wrapper.findAll('.thanh-ben-dieu-huong__lien-ket')).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Bảng điều khiển')
    expect(wrapper.text()).not.toContain('Hội viên')
    expect(wrapper.text()).not.toContain('Nhân viên lễ tân')
  })

  it('chon menu Admin dong drawer sau navigation', async () => {
    const { router, wrapper } = await mountAdminCoBusinessRoutes()
    const nutMenu = wrapper.get('[data-testid="nut-mo-menu"]')

    await nutMenu.trigger('click')
    expect(wrapper.get('.khung-ung-dung').classes()).toContain('khung-ung-dung--mo')

    await wrapper.get('.thanh-ben-dieu-huong__lien-ket').trigger('click')
    await router.isReady()
    await flushPromises()

    expect(router.currentRoute.value.name).toBe('adminTaiKhoan')
    expect(wrapper.get('.khung-ung-dung').classes()).not.toContain('khung-ung-dung--mo')
  })

  it('responsive static audit có breakpoint drawer và ràng buộc chống overflow', () => {
    const css = readFileSync('src/assets/main.css', 'utf8')

    expect(css).toContain('--do-rong-thanh-ben: 256px')
    expect(css).toContain('--chieu-cao-thanh-tren: 60px')
    expect(css).toContain('--le-noi-dung: 24px')
    expect(css).toContain('@media (max-width: 1023px)')
    expect(css).toContain('@media (max-width: 639px)')
    expect(css).toContain('grid-template-columns: minmax(0, 1fr)')
    expect(css).toContain('overflow-x: hidden')
    expect(css).toContain('text-overflow: ellipsis')
    expect(css).toContain('.khung-ung-dung--mo .thanh-ben-dieu-huong')
    expect(css).toContain('--mau-lop-phu-drawer:')
  })
})
