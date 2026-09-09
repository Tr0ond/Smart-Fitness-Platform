import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ChanTinhNangBiChan from './chan_tinh_nang_bi_chan.vue'

describe('chan_tinh_nang_bi_chan FE2-T04', () => {
  it('render status accessible voi props va next-action text bang interpolation an toan', () => {
    const wrapper = mount(ChanTinhNangBiChan, {
      props: {
        tieuDe: '<Hồ sơ PT>',
        moTa: '<Nội dung chưa sẵn sàng>',
        maChan: 'BLOCKER-01',
        hanhDongTiepTheo: 'Chờ API được cung cấp.',
      },
    })

    const status = wrapper.get('[role="status"]')

    expect(status.attributes('aria-live')).toBe('polite')
    expect(status.attributes('aria-labelledby')).toBeTruthy()
    expect(status.attributes('aria-describedby')).toBeTruthy()
    expect(status.get('h3').text()).toBe('<Hồ sơ PT>')
    expect(status.get('p.chan-tinh-nang-bi-chan__mo-ta').text()).toBe('<Nội dung chưa sẵn sàng>')
    expect(status.get('code').text()).toBe('BLOCKER-01')
    expect(status.text()).toContain('Chờ API được cung cấp.')
    expect(wrapper.html()).not.toContain('v-html')
    expect(wrapper.findAll('button')).toHaveLength(0)
    expect(wrapper.emitted()).toEqual({})
  })

  it('nhan slot next-action va khong tu tao action side effect', () => {
    const wrapper = mount(ChanTinhNangBiChan, {
      props: {
        tieuDe: 'Hồ sơ đầy đủ',
        moTa: 'Chưa có dữ liệu nguồn.',
        maChan: 'BLOCKER-01',
      },
      slots: {
        hanhDongTiepTheo: '<a href="/ke-hoach">Xem kế hoạch Backend</a>',
      },
    })

    expect(wrapper.get('a').attributes('href')).toBe('/ke-hoach')
    expect(wrapper.text()).toContain('Xem kế hoạch Backend')
    expect(wrapper.findAll('button')).toHaveLength(0)
    expect(wrapper.emitted()).toEqual({})
  })
})
