import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BoChonQuanHeBaiTap from './bo_chon_quan_he_bai_tap.vue'

const nhomCo = [
  { id: 1, name: 'Ngực', status: 'HOAT_DONG' },
  { id: 2, name: 'Lưng', status: 'NGUNG_SU_DUNG' },
  { id: 3, name: 'Vai', status: 'NGUNG_SU_DUNG' },
]

describe('bo_chon_quan_he_bai_tap FE3', () => {
  it('khóa quan hệ M061 đã chọn và echo nguyên vẹn id/role', async () => {
    const wrapper = mount(BoChonQuanHeBaiTap, {
      props: {
        equipment: [{ id: 8, code: 'DB', name: 'Tạ tay' }],
        muscleGroups: nhomCo,
        modelValue: { equipment_ids: [8], muscle_groups: [{ id: 2, role: 'PHU' }] },
      },
    })

    const checkboxes = wrapper.findAll('input[type="checkbox"]')
    const roles = wrapper.findAll('select').filter((select) => select.attributes('multiple') === undefined)
    expect(checkboxes[0].element.disabled).toBe(false)
    expect(checkboxes[1].element.disabled).toBe(true)
    expect(checkboxes[2].element.disabled).toBe(true)
    expect(roles[0].element.disabled).toBe(true)

    await checkboxes[1].trigger('change')
    await roles[0].trigger('change')
    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    expect(wrapper.emitted('capNhat')).toBeUndefined()

    await checkboxes[0].setValue(true)
    expect(wrapper.emitted('capNhat')?.at(-1)?.[0]).toEqual({
      equipment_ids: [8],
      muscle_groups: [{ id: 2, role: 'PHU' }, { id: 1, role: 'CHINH' }],
    })
  })
})
