import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import KhungXemDeXuat from './khung_xem_de_xuat.vue'

describe('khung_xem_de_xuat FE6', () => {
  it('shows proposal status, plan changes, expiry and member-owned confirmation boundary', () => {
    const wrapper = mount(KhungXemDeXuat, {
      props: {
        mo: true,
        proposal: {
          id: 9,
          title: 'Điều chỉnh thân trên',
          status: 'CHO_XAC_NHAN',
          change_type: 'DIEU_CHINH',
          effective_from: '2026-10-02',
          expires_at: '2026-10-03T12:00:00Z',
          explanation: 'Tăng một hiệp cho nhóm cơ lưng.',
          content: {
            plan: { name: 'Kế hoạch sức mạnh', goal: 'Tăng sức mạnh', days: [{
              order: 1,
              name: 'Ngày thân trên',
              weekday: 2,
              estimated_minutes: 60,
              exercises: [{ exercise_id: 14, name: 'Kéo cáp', target_sets: 4, min_reps: 8, max_reps: 10 }],
            }] },
          },
        },
      },
      global: { stubs: { teleport: true } },
    })

    expect(wrapper.text()).toContain('Chờ hội viên xác nhận')
    expect(wrapper.text()).toContain('Kéo cáp')
    expect(wrapper.text()).toContain('hội viên xác nhận trên ứng dụng di động')
    expect(wrapper.text()).not.toContain('Áp dụng kế hoạch')
  })

  it('renders root-shaped proposal content with rest, notes and zero rest safely', () => {
    const wrapper = mount(KhungXemDeXuat, {
      props: {
        mo: true,
        proposal: {
          id: 14,
          title: 'Root DTO proposal',
          status: 'CHO_XAC_NHAN',
          change_type: 'DIEU_CHINH',
          effective_from: '2026-10-02',
          expires_at: '2026-10-03T12:00:00Z',
          explanation: 'Rest and notes changed.',
          content: {
            name: 'Root plan',
            goal: 'Strength',
            days: [{
              order: 1,
              name: 'Day one',
              weekday: 2,
              estimated_minutes: 60,
              exercises: [
                { exercise_id: 14, name: 'Cable pull', target_sets: 4, min_reps: 8, max_reps: 10,
                  target_weight_kg: '20.00', rest_seconds: 0, notes: 'Keep control <img src=x>' },
                { exercise_id: 15, name: 'Push up', target_sets: 3, min_reps: 8, max_reps: 12,
                  target_weight_kg: null, rest_seconds: 45, notes: null },
                { exercise_id: 16, name: 'Crunch', target_sets: 2, min_reps: 10, max_reps: 12,
                  target_weight_kg: null, rest_seconds: null, notes: '   ' },
              ],
            }],
          },
        },
      },
      global: { stubs: { teleport: true } },
    })

    expect(wrapper.text()).toContain('Root plan')
    expect(wrapper.text()).toContain('0 giây nghỉ')
    expect(wrapper.text()).toContain('45 giây nghỉ')
    expect(wrapper.text()).toContain('Keep control <img src=x>')
    expect(wrapper.html()).not.toContain('<img src="x">')
    expect(wrapper.text()).not.toContain('   ')
    expect(wrapper.text()).not.toContain('null giây nghỉ')
  })

  it('moves focus into the teleported preview, traps keyboard focus, closes on Escape and restores focus', async () => {
    const trigger = document.createElement('button')
    trigger.textContent = 'Xem trước'
    document.body.append(trigger)
    trigger.focus()
    const removeListener = vi.spyOn(window, 'removeEventListener')
    const wrapper = mount(KhungXemDeXuat, {
      props: {
        mo: false,
        proposal: { id: 12, title: 'Preview', status: 'CHO_XAC_NHAN', content: { plan: { days: [] } } },
      },
    })

    await wrapper.setProps({ mo: true })
    await nextTick()
    const dialog = document.body.querySelector('.pt-de-xuat-phu__khung')
    const close = dialog.querySelector('button:not([tabindex="-1"])')
    expect(document.activeElement).toBe(close)

    close.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true, cancelable: true }))
    expect(document.activeElement).toBe(close)
    close.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true, cancelable: true }))
    expect(document.activeElement).toBe(close)
    close.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }))
    expect(wrapper.emitted('dong')).toHaveLength(1)

    await wrapper.setProps({ mo: false })
    await nextTick()
    expect(document.activeElement).toBe(trigger)
    wrapper.unmount()
    expect(removeListener).toHaveBeenCalledWith('keydown', expect.any(Function))
    removeListener.mockRestore()
    trigger.remove()
  })

  it('does not throw when the preview trigger is removed before focus restoration', async () => {
    const trigger = document.createElement('button')
    document.body.append(trigger)
    trigger.focus()
    const wrapper = mount(KhungXemDeXuat, {
      props: {
        mo: false,
        proposal: { id: 13, title: 'Preview', status: 'CHO_XAC_NHAN', content: { plan: { days: [] } } },
      },
    })
    await wrapper.setProps({ mo: true })
    await nextTick()
    expect(document.activeElement).toBe(document.body.querySelector('.pt-de-xuat-phu__khung button'))

    trigger.remove()
    await wrapper.setProps({ mo: false })
    expect(document.activeElement).not.toBe(trigger)
    wrapper.unmount()
  })
})
