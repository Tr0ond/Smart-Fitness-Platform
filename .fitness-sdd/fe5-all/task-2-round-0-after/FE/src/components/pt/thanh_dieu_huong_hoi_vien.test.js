import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from './thanh_dieu_huong_hoi_vien.vue'

describe('ThanhDieuHuongHoiVien', () => {
  it('renders scoped named links and exposes the active route accessibly', async () => {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { name: 'ptChiTietHoiVien', path: '/pt/hoi-vien/:id' },
        { name: 'ptTienDoHoiVien', path: '/pt/hoi-vien/:id/tien-do' },
        { name: 'ptKeHoachTapHoiVien', path: '/pt/hoi-vien/:id/ke-hoach-tap' },
        { name: 'ptLichSuTapHoiVien', path: '/pt/hoi-vien/:id/lich-su-tap' },
        { name: 'ptGhiChuHoiVien', path: '/pt/hoi-vien/:id/ghi-chu' },
      ],
    })
    await router.push({ name: 'ptTienDoHoiVien', params: { id: '42' } })

    const wrapper = mount(ThanhDieuHuongHoiVien, {
      props: { memberId: 42 },
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.text()).toContain('Tiến độ')
    expect(wrapper.findAll('a')).toHaveLength(5)
    expect(wrapper.find('a[aria-current="page"]').text()).toBe('Tiến độ')
    expect(wrapper.find('a[aria-current="page"]').attributes('href')).toBe('/pt/hoi-vien/42/tien-do')
  })
})
