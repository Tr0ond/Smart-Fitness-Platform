import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { describe, expect, it } from 'vitest'
import HopThoaiXungDotGiaoAn from './hop_thoai_xung_dot_giao_an.vue'
import HopThoaiXacNhan from '../dung_chung/hop_thoai_xac_nhan.vue'

describe('hop_thoai_xung_dot_giao_an FE3', () => {
  it('hien stale warning va phat tai lai', async () => {
    const wrapper = mount(HopThoaiXungDotGiaoAn, { props: { hienThi: true, phienBanHienTai: 4 } })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('WORKOUT_TEMPLATE_STALE')
    await wrapper.get('button.nut--chinh').trigger('click')
    expect(wrapper.emitted('taiLai')).toHaveLength(1)
  })

  it('giữ focus trong dialog, Escape trả focus và khóa khi đang tải', async () => {
    const trigger = document.createElement('button')
    document.body.appendChild(trigger)
    trigger.focus()
    const wrapper = mount(HopThoaiXacNhan, {
      attachTo: document.body,
      props: { hienThi: false, tieuDe: 'Xác nhận thao tác', moTa: 'Mô tả thao tác' },
    })
    await wrapper.setProps({ hienThi: true })
    await nextTick()
    const nutDong = wrapper.get('[role="dialog"] button.nut--phu')
    const nutDoiSoat = wrapper.get('[role="dialog"] button.nut--chinh')
    expect(document.activeElement).toBe(nutDong.element)

    nutDoiSoat.element.focus()
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab' }))
    expect(document.activeElement).toBe(nutDong.element)
    nutDong.element.focus()
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true }))
    expect(document.activeElement).toBe(nutDoiSoat.element)

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await nextTick()
    expect(wrapper.emitted('huy')).toHaveLength(1)
    await wrapper.setProps({ hienThi: false })
    await nextTick()
    expect(document.activeElement).toBe(trigger)

    await wrapper.setProps({ hienThi: true, dangXuLy: true })
    await nextTick()
    expect(wrapper.get('[role="dialog"] button.nut--phu').element.disabled).toBe(true)
    expect(wrapper.get('[role="dialog"] button.nut--chinh').element.disabled).toBe(true)
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    expect(wrapper.emitted('huy')).toHaveLength(1)
    wrapper.unmount()
    trigger.remove()
  })
})
