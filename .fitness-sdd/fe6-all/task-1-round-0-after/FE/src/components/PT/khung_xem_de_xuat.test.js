import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
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
})
