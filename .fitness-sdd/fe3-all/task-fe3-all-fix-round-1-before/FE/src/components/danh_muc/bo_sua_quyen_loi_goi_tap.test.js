import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BoSuaQuyenLoiGoiTap from './bo_sua_quyen_loi_goi_tap.vue'

describe('bo_sua_quyen_loi_goi_tap FE3', () => {
  it('render controls va phat payload quyen loi', async () => {
    const wrapper = mount(BoSuaQuyenLoiGoiTap, { props: { modelValue: { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: true, direct_trainer_sessions: 1 } } })
    await wrapper.get('button').trigger('click')
    expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(3)
    expect(wrapper.emitted('luu')?.[0][0]).toMatchObject({ trainer_chat: true, direct_trainer_sessions: 1 })
  })
})
