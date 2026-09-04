import { afterEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import DuongDanPhanCap from './duong_dan_phan_cap.vue'

const TrangKiemThu = { template: '<h1>Trang kiểm thử</h1>' }

async function mountDuongDan(meta, routes = []) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/tai-khoan', name: 'adminTaiKhoan', component: TrangKiemThu },
      { path: '/admin/tai-khoan/:id', name: 'adminChiTietTaiKhoan', component: TrangKiemThu, meta },
      ...routes,
    ],
  })

  await router.push('/admin/tai-khoan/123')
  await router.isReady()

  const wrapper = mount(DuongDanPhanCap, {
    global: { plugins: [router] },
  })

  return { router, wrapper }
}

afterEach(() => {
  document.body.innerHTML = ''
})

describe('duong_dan_phan_cap FE1-T01', () => {
  it('doc breadcrumb tu route metadata va render list semantic', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.get('nav').attributes('aria-label')).toBe('Đường dẫn')
    expect(wrapper.get('ol').exists()).toBe(true)
    expect(wrapper.get('ol').text()).toContain('Tài khoản')
    expect(wrapper.get('ol').text()).toContain('Chi tiết tài khoản')
  })

  it('detail breadcrumb hien dung nhan generic, khong hien raw id', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.text()).toContain('Tài khoản')
    expect(wrapper.text()).toContain('Chi tiết tài khoản')
    expect(wrapper.text()).not.toContain('123')
    expect(wrapper.text()).not.toContain(':id')
  })

  it('current item co aria-current page', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.get('[aria-current="page"]').text()).toBe('Chi tiết tài khoản')
  })

  it('ancestor route ton tai thi la RouterLink noi bo', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    const lienKet = wrapper.get('a')

    expect(lienKet.text()).toBe('Tài khoản')
    expect(lienKet.attributes('href')).toBe('/admin/tai-khoan')
  })

  it('ancestor route thieu thi chi render text, khong tao broken link', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Tài khoản', tenTuyenDuong: 'adminTaiKhoanChuaDangKy' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.findAll('a')).toHaveLength(0)
    expect(wrapper.text()).toContain('Tài khoản')
  })

  it('metadata raw param hoac raw numeric khong duoc render', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: ':id', tenTuyenDuong: 'adminTaiKhoan' },
        { nhan: '123' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.text()).not.toContain(':id')
    expect(wrapper.text()).not.toContain('123')
    expect(wrapper.text()).toContain('Chi tiết tài khoản')
  })

  it('khong tao external href tu metadata', async () => {
    const { wrapper } = await mountDuongDan({
      duongDanPhanCap: [
        { nhan: 'Trang ngoài', tenTuyenDuong: 'https://example.com' },
        { nhan: 'Chi tiết tài khoản' },
      ],
    })

    expect(wrapper.find('a[href^="http"]').exists()).toBe(false)
    expect(wrapper.findAll('a')).toHaveLength(0)
  })

  it('thieu metadata thi khong render nav va khong crash', async () => {
    const { wrapper } = await mountDuongDan({})

    expect(wrapper.find('nav').exists()).toBe(false)
    expect(wrapper.text()).toBe('')
  })

  it('metadata khong phai array duoc xu ly graceful', async () => {
    const { wrapper } = await mountDuongDan({ duongDanPhanCap: { nhan: 'Sai dinh dang' } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })
})
