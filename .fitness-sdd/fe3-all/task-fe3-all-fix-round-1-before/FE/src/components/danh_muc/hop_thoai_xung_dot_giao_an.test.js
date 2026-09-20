import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import HopThoaiXungDotGiaoAn from './hop_thoai_xung_dot_giao_an.vue'

describe('hop_thoai_xung_dot_giao_an FE3', () => {
  it('hien stale warning va phat tai lai', async () => {
    const wrapper = mount(HopThoaiXungDotGiaoAn, { props: { hienThi: true, phienBanHienTai: 4 } })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('WORKOUT_TEMPLATE_STALE')
    await wrapper.get('button.nut--chinh').trigger('click')
    expect(wrapper.emitted('taiLai')).toHaveLength(1)
  })
})
