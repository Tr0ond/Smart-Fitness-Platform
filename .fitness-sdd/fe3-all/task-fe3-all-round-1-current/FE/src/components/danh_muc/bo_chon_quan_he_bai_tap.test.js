import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BoChonQuanHeBaiTap from './bo_chon_quan_he_bai_tap.vue'

describe('bo_chon_quan_he_bai_tap FE3', () => {
  it('cho chon nhom co active va role', async () => {
    const wrapper = mount(BoChonQuanHeBaiTap, { props: { equipment: [{ id: 1, code: 'DB', name: 'Dumbbell' }], muscleGroups: [{ id: 2, name: 'Nguc', status: 'HOAT_DONG' }] } })
    await wrapper.find('input[type="checkbox"]').setValue(true)
    expect(wrapper.emitted('capNhat')?.[0][0]).toEqual({ equipment_ids: [], muscle_groups: [{ id: 2, role: 'CHINH' }] })
  })
})
