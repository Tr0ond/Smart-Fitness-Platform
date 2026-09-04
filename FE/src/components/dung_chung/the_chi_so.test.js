import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TheChiSo from './the_chi_so.vue'

describe('the_chi_so FE1-T02', () => {
  it('render label va numeric zero nhu gia tri hop le', () => {
    const wrapper = mount(TheChiSo, {
      props: { nhan: 'Lượt check-in hôm nay', giaTri: 0 },
    })

    expect(wrapper.get('.the-chi-so__nhan').text()).toBe('Lượt check-in hôm nay')
    expect(wrapper.get('.the-chi-so__gia-tri').text()).toBe('0')
  })

  it('render gia tri duong va mo ta qua interpolation', () => {
    const wrapper = mount(TheChiSo, {
      props: { nhan: 'Hội viên', giaTri: 12, moTa: 'Dữ liệu Backend.' },
    })

    expect(wrapper.get('.the-chi-so__gia-tri').text()).toBe('12')
    expect(wrapper.get('.the-chi-so__mo-ta').text()).toBe('Dữ liệu Backend.')
  })

  it('loading co semantic status va khong hien gia tri stale', () => {
    const wrapper = mount(TheChiSo, {
      props: { nhan: 'Hội viên', giaTri: 12, dangTai: true },
    })

    expect(wrapper.get('[role="status"]').text()).toContain('Đang cập nhật')
    expect(wrapper.text()).not.toContain('12')
    expect(wrapper.get('article').attributes('aria-busy')).toBe('true')
  })

  it('khong render raw HTML trong label/mo ta', () => {
    const wrapper = mount(TheChiSo, {
      props: { nhan: '<b>Chỉ số</b>', giaTri: 1, moTa: '<script>alert(1)</script>' },
    })

    expect(wrapper.find('b').exists()).toBe(false)
    expect(wrapper.find('script').exists()).toBe(false)
    expect(wrapper.text()).toContain('<b>Chỉ số</b>')
  })
})
