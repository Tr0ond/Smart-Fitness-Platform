import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { createMemoryHistory, createRouter } from 'vue-router'
import BieuMauDangNhap from '../components/xac_thuc/bieu_mau_dang_nhap.vue'
import AdminDangNhap from './admin/dang_nhap/dang_nhap.index.vue'
import LeTanDangNhap from './le_tan/dang_nhap/dang_nhap.index.vue'
import PtDangNhap from './pt/dang_nhap/dang_nhap.index.vue'
import QuenMatKhau from './chung/quen_mat_khau/quen_mat_khau.index.vue'
import DatLaiMatKhau from './chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue'
import ChonVaiTro from './chung/chon_vai_tro/chon_vai_tro.index.vue'
import * as xacThucApi from '../services/xac_thuc.api.js'
import { useXacThucStore } from '../stores/xac_thuc.store.js'
import {
  dieuPhoiSau401,
  dieuPhoiSauDangNhap,
  layTuyenDangNhapTheoVaiTro,
} from '../utils/dieu_phoi_xac_thuc.js'

function taoRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'chonVaiTro', component: { template: '<div />' } },
      { path: '/quen-mat-khau', name: 'quenMatKhau', component: { template: '<div />' } },
      { path: '/dat-lai-mat-khau', name: 'datLaiMatKhau', component: { template: '<div />' } },
    ],
  })
}

function mountCoRouter(component, router, options = {}) {
  return mount(component, {
    global: { plugins: [router] },
    ...options,
  })
}

async function choFlowHoanTat() {
  await new Promise((resolve) => setTimeout(resolve, 0))
}

describe('public auth pages FE0-T05', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    sessionStorage.clear()
    vi.restoreAllMocks()
  })

  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it.each([
    ['Admin', AdminDangNhap, 'ADMIN', 'Đăng nhập quản trị viên'],
    ['PT', PtDangNhap, 'PT', 'Đăng nhập huấn luyện viên'],
    ['Le tan', LeTanDangNhap, 'RECEPTIONIST', 'Đăng nhập lễ tân'],
  ])('login %s dung role tu /me va khong persist password', async (_ten, component, vaiTro, tieuDe) => {
    const router = taoRouter()
    const store = useXacThucStore()
    store.dangNhap = vi.fn(async () => {
      store.token = 'token'
      store.nguoiDung = { id: 1 }
      store.vaiTro = [vaiTro]
    })
    const wrapper = mountCoRouter(component, router)

    await wrapper.get('input[type="email"]').setValue('actor@example.com')
    await wrapper.get('input[type="password"]').setValue('MatKhau!123456')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(wrapper.text()).toContain(tieuDe)
    expect(store.dangNhap).toHaveBeenCalledWith({
      email: 'actor@example.com',
      password: 'MatKhau!123456',
    })
    expect(store.vaiTroDangDung).toBe(vaiTro)
    expect(wrapper.get('input[type="password"]').element.value).toBe('')
    expect(sessionStorage.getItem('smart_fitness.auth.token')).not.toBe('MatKhau!123456')
  })

  it('login normalized 429 hien thong bao va khong retry', async () => {
    const xuLy = vi.fn().mockRejectedValue({
      httpStatus: 429,
      message: 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
      fieldErrors: {},
      isNetworkError: false,
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(BieuMauDangNhap, router, {
      props: { tieuDe: 'Đăng nhập', dangXuLyDangNhap: xuLy },
    })

    await wrapper.get('input[type="email"]').setValue('actor@example.com')
    await wrapper.get('input[type="password"]').setValue('MatKhau!123456')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(wrapper.get('[role="alert"]').text()).toContain('quá nhiều')
    expect(xuLy).toHaveBeenCalledTimes(1)
  })

  it('login 422 map tung field va focus field loi dau theo thu tu form', async () => {
    const xuLy = vi.fn().mockRejectedValue({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: {
        password: ['Mật khẩu chưa đúng.'],
        email: ['Email không hợp lệ.'],
      },
      retryAfter: null,
      isNetworkError: false,
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(BieuMauDangNhap, router, {
      attachTo: document.body,
      props: { tieuDe: 'Đăng nhập', dangXuLyDangNhap: xuLy },
    })

    try {
      await wrapper.get('input[name="email"]').setValue('actor@example.com')
      await wrapper.get('input[name="password"]').setValue('MatKhau!123456')
      await wrapper.get('form').trigger('submit')
      await choFlowHoanTat()
      await nextTick()

      expect(wrapper.get('#email-dang-nhap-loi').text()).toBe('Email không hợp lệ.')
      expect(wrapper.get('#mat-khau-dang-nhap-loi').text()).toBe('Mật khẩu chưa đúng.')
      expect(wrapper.get('input[name="email"]').attributes('aria-invalid')).toBe('true')
      expect(document.activeElement).toBe(wrapper.get('input[name="email"]').element)
    } finally {
      wrapper.unmount()
    }
  })

  it('login 429 khoa submit va dem nguoc dung Retry-After', async () => {
    vi.useFakeTimers()
    const xuLy = vi.fn().mockRejectedValue({
      httpStatus: 429,
      message: 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.',
      fieldErrors: {},
      retryAfter: 2,
      isNetworkError: false,
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(BieuMauDangNhap, router, {
      props: { tieuDe: 'Đăng nhập', dangXuLyDangNhap: xuLy },
    })

    try {
      await wrapper.get('input[name="email"]').setValue('actor@example.com')
      await wrapper.get('input[name="password"]').setValue('MatKhau!123456')
      await wrapper.get('form').trigger('submit')
      await flushPromises()

      expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBe('')
      expect(wrapper.get('button[type="submit"]').text()).toContain('2 giây')
      expect(wrapper.text()).toContain('thử lại sau 2 giây')

      await vi.advanceTimersByTimeAsync(1000)
      await nextTick()
      expect(wrapper.get('button[type="submit"]').text()).toContain('1 giây')

      await vi.advanceTimersByTimeAsync(1000)
      await nextTick()
      expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeUndefined()
      expect(xuLy).toHaveBeenCalledTimes(1)
    } finally {
      wrapper.unmount()
    }
  })

  it.each([
    [401, 'Thông tin xác thực không hợp lệ.'],
    [422, 'Dữ liệu gửi lên chưa hợp lệ.'],
    [429, 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.'],
    [503, 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.'],
    [null, 'Không thể kết nối đến máy chủ.'],
  ])('login hien normalized error %s va khong retry', async (httpStatus, message) => {
    const xuLy = vi.fn().mockRejectedValue({
      httpStatus,
      message,
      fieldErrors: {},
      isNetworkError: httpStatus === null,
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(BieuMauDangNhap, router, {
      props: { tieuDe: 'Đăng nhập', dangXuLyDangNhap: xuLy },
    })

    await wrapper.get('input[type="email"]').setValue('actor@example.com')
    await wrapper.get('input[type="password"]').setValue('MatKhau!123456')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(wrapper.get('[role="alert"]').text()).toBe(message)
    expect(xuLy).toHaveBeenCalledTimes(1)
  })

  it.each([
    [['ADMIN', 'PT'], 'ADMIN'],
    [['PT'], 'ADMIN'],
    [['MEMBER'], 'PT'],
  ])('login role decision khong tu priority va mismatch/member ve selector', async (vaiTro, vaiTroMongDoi) => {
    const store = useXacThucStore()
    const router = { push: vi.fn() }
    store.vaiTro = vaiTro
    store.chonVaiTroDangDung = vi.fn()

    await dieuPhoiSauDangNhap(store, router, vaiTroMongDoi)

    expect(store.chonVaiTroDangDung).not.toHaveBeenCalled()
    expect(router.push).toHaveBeenCalledWith({ name: 'chonVaiTro' })
  })

  it.each([
    ['ADMIN', 'adminDangNhap'],
    ['PT', 'ptDangNhap'],
    ['RECEPTIONIST', 'leTanDangNhap'],
    [null, 'chonVaiTro'],
  ])('401 current session dieu huong ve login actor %s bang route name an toan', async (vaiTro, tenTuyen) => {
    const router = {
      currentRoute: { value: { name: 'khuVucBaoVe' } },
      replace: vi.fn().mockResolvedValue(undefined),
    }

    expect(layTuyenDangNhapTheoVaiTro(vaiTro)).toBe(tenTuyen)
    await expect(dieuPhoiSau401(router, vaiTro)).resolves.toBe(true)
    expect(router.replace).toHaveBeenCalledWith({ name: tenTuyen })
  })

  it('selector chan noi dung phien loi tam thoi va cho nguoi dung retry /me', async () => {
    const router = taoRouter()
    const store = useXacThucStore()
    store.token = 'token-duoc-giu-de-retry'
    store.daKhoiPhucPhien = true
    store.loiKhoiPhucPhien = {
      httpStatus: 503,
      message: 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.',
      isNetworkError: false,
    }
    store.khoiPhucPhien = vi.fn(async () => {
      store.nguoiDung = { id: 1, roles: ['PT'] }
      store.vaiTro = ['PT']
      store.loiKhoiPhucPhien = null
      return true
    })
    const wrapper = mountCoRouter(ChonVaiTro, router)

    expect(wrapper.get('.trang-thai-loi').text()).toContain('Máy chủ đang gặp sự cố')
    expect(wrapper.find('button.nut--phu').text()).toBe('Thử lại')
    expect(wrapper.text()).not.toContain('Vai trò đang dùng')

    await wrapper.get('.trang-thai-loi button').trigger('click')
    await flushPromises()

    expect(store.khoiPhucPhien).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Huấn luyện viên')
  })

  it('forgot-password hien cung thong bao generic va xoa email sau success', async () => {
    const guiYeuCau = vi.spyOn(xacThucApi, 'guiYeuCauDatLaiMatKhau').mockResolvedValue({
      data: null,
      message: 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(QuenMatKhau, router)

    await wrapper.get('input[type="email"]').setValue('known@example.com')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(guiYeuCau).toHaveBeenCalledWith('known@example.com')
    expect(wrapper.get('[role="status"]').text()).toContain('Nếu email tồn tại')
    expect(wrapper.get('input[type="email"]').element.value).toBe('')
  })

  it('forgot-password khong phan biet known/unknown va khong retry', async () => {
    const guiYeuCau = vi.spyOn(xacThucApi, 'guiYeuCauDatLaiMatKhau').mockResolvedValue({
      data: null,
      message: 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(QuenMatKhau, router)

    await wrapper.get('input[type="email"]').setValue('known@example.com')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()
    const thongBaoKnown = wrapper.get('[role="status"]').text()
    await wrapper.get('input[type="email"]').setValue('unknown@example.com')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(wrapper.get('[role="status"]').text()).toBe(thongBaoKnown)
    expect(guiYeuCau).toHaveBeenCalledTimes(2)
    expect(guiYeuCau).toHaveBeenNthCalledWith(1, 'known@example.com')
    expect(guiYeuCau).toHaveBeenNthCalledWith(2, 'unknown@example.com')
  })

  it('forgot-password 422 gan loi vao email thay vi chi hien loi tong', async () => {
    vi.spyOn(xacThucApi, 'guiYeuCauDatLaiMatKhau').mockRejectedValue({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { email: ['Email không hợp lệ.'] },
      retryAfter: null,
      isNetworkError: false,
    })
    const router = taoRouter()
    const wrapper = mountCoRouter(QuenMatKhau, router, { attachTo: document.body })

    try {
      await wrapper.get('input[name="email"]').setValue('invalid@example.com')
      await wrapper.get('form').trigger('submit')
      await choFlowHoanTat()
      await nextTick()

      expect(wrapper.get('#email-quen-mat-khau-loi').text()).toBe('Email không hợp lệ.')
      expect(document.activeElement).toBe(wrapper.get('input[name="email"]').element)
    } finally {
      wrapper.unmount()
    }
  })

  it('reset-password valid gui exact fields, clear password va loai query khoi route', async () => {
    const token = 'a'.repeat(64)
    const datLai = vi.spyOn(xacThucApi, 'datLaiMatKhau').mockResolvedValue({
      data: null,
      message: 'Đặt lại mật khẩu thành công.',
    })
    const router = taoRouter()
    await router.push({ name: 'datLaiMatKhau', query: { token } })
    const wrapper = mountCoRouter(DatLaiMatKhau, router)

    await wrapper.get('#mat-khau-moi').setValue('NewPassword!456')
    await wrapper.get('#xac-nhan-mat-khau-moi').setValue('NewPassword!456')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(datLai).toHaveBeenCalledWith({
      token,
      password: 'NewPassword!456',
      password_confirmation: 'NewPassword!456',
    })
    expect(router.currentRoute.value.name).toBe('chonVaiTro')
    expect(router.currentRoute.value.query.token).toBeUndefined()
  })

  it('reset-password thieu token khong goi Backend', async () => {
    const datLai = vi.spyOn(xacThucApi, 'datLaiMatKhau')
    const router = taoRouter()
    await router.push({ name: 'datLaiMatKhau' })
    const wrapper = mountCoRouter(DatLaiMatKhau, router)

    expect(wrapper.get('[role="alert"]').text()).toContain('không hợp lệ')
    expect(datLai).not.toHaveBeenCalled()
  })

  it('reset-password token sai format khong duoc gui va khong persist', async () => {
    const datLai = vi.spyOn(xacThucApi, 'datLaiMatKhau')
    const router = taoRouter()
    await router.push({ name: 'datLaiMatKhau', query: { token: 'A'.repeat(64) } })
    const wrapper = mountCoRouter(DatLaiMatKhau, router)

    expect(wrapper.get('[role="alert"]').text()).toContain('không hợp lệ')
    expect(datLai).not.toHaveBeenCalled()
    expect(sessionStorage.length).toBe(0)
  })

  it('reset-password 422 map va focus password_confirmation khi day la field loi dau', async () => {
    const token = 'a'.repeat(64)
    vi.spyOn(xacThucApi, 'datLaiMatKhau').mockRejectedValue({
      httpStatus: 422,
      message: 'Dữ liệu gửi lên chưa hợp lệ.',
      fieldErrors: { password_confirmation: ['Xác nhận mật khẩu không khớp.'] },
      retryAfter: null,
      isNetworkError: false,
    })
    const router = taoRouter()
    await router.push({ name: 'datLaiMatKhau', query: { token } })
    const wrapper = mountCoRouter(DatLaiMatKhau, router, { attachTo: document.body })

    try {
      await wrapper.get('input[name="password"]').setValue('NewPassword!456')
      await wrapper.get('input[name="password_confirmation"]').setValue('DifferentPassword!456')
      await wrapper.get('form').trigger('submit')
      await choFlowHoanTat()
      await nextTick()

      expect(wrapper.get('#xac-nhan-mat-khau-moi-loi').text()).toBe('Xác nhận mật khẩu không khớp.')
      expect(document.activeElement).toBe(wrapper.get('input[name="password_confirmation"]').element)
    } finally {
      wrapper.unmount()
    }
  })

  it.each([
    [422, 'Dữ liệu gửi lên chưa hợp lệ.'],
    [429, 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau.'],
    [503, 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.'],
    [null, 'Không thể kết nối đến máy chủ.'],
  ])('reset-password hien normalized error %s va khong retry', async (httpStatus, message) => {
    const token = 'a'.repeat(64)
    const datLai = vi.spyOn(xacThucApi, 'datLaiMatKhau').mockRejectedValue({
      httpStatus,
      message,
      fieldErrors: {},
      isNetworkError: httpStatus === null,
    })
    const router = taoRouter()
    await router.push({ name: 'datLaiMatKhau', query: { token } })
    const wrapper = mountCoRouter(DatLaiMatKhau, router)

    await wrapper.get('#mat-khau-moi').setValue('NewPassword!456')
    await wrapper.get('#xac-nhan-mat-khau-moi').setValue('NewPassword!456')
    await wrapper.get('form').trigger('submit')
    await choFlowHoanTat()

    expect(wrapper.get('[role="alert"]').text()).toBe(message)
    expect(datLai).toHaveBeenCalledTimes(1)
    expect(sessionStorage.length).toBe(0)
  })
})
