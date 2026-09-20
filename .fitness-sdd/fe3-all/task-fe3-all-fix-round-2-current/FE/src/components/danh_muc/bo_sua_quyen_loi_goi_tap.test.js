import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BoSuaQuyenLoiGoiTap from './bo_sua_quyen_loi_goi_tap.vue'

const quyenLoi = (overrides = {}) => ({
  gym_access: true,
  fitness_assistant: false,
  fitness_assistant_limit: 0,
  trainer_chat: false,
  direct_trainer_sessions: 0,
  ...overrides,
})

describe('bo_sua_quyen_loi_goi_tap FE3', () => {
  it('render controls va phat payload quyen loi', async () => {
    const wrapper = mount(BoSuaQuyenLoiGoiTap, { props: { modelValue: quyenLoi({ trainer_chat: true, direct_trainer_sessions: 1 }) } })
    await wrapper.get('button').trigger('click')
    expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(3)
    expect(wrapper.emitted('luu')?.[0][0]).toMatchObject({ trainer_chat: true, direct_trainer_sessions: 1 })
  })

  it('tắt/bật AI chuẩn hóa quota 0 rồi default dương', async () => {
    const wrapper = mount(BoSuaQuyenLoiGoiTap, { props: { modelValue: quyenLoi({ fitness_assistant: true, fitness_assistant_limit: 8 }) } })
    const assistant = wrapper.findAll('input[type="checkbox"]')[1]
    await assistant.setValue(false)
    expect(wrapper.emitted('update:modelValue').at(-1)[0]).toMatchObject({ fitness_assistant: false, fitness_assistant_limit: 0 })
    await assistant.setValue(true)
    expect(wrapper.emitted('update:modelValue').at(-1)[0]).toMatchObject({ fitness_assistant: true, fitness_assistant_limit: 1 })
  })

  it('có lựa chọn AI không giới hạn ánh xạ null và limited ánh xạ số dương', async () => {
    const wrapper = mount(BoSuaQuyenLoiGoiTap, { props: { modelValue: quyenLoi({ fitness_assistant: true, fitness_assistant_limit: 2 }) } })
    const mode = wrapper.get('#fitness-assistant-mode')
    await mode.setValue(true)
    expect(wrapper.emitted('update:modelValue').at(-1)[0]).toMatchObject({ fitness_assistant_limit: null })
    await mode.setValue(false)
    expect(wrapper.emitted('update:modelValue').at(-1)[0].fitness_assistant_limit).toBe(1)
  })

  it('chặn luu khi tất cả quyền lợi tắt và báo lỗi accessible', async () => {
    const wrapper = mount(BoSuaQuyenLoiGoiTap, { props: { modelValue: quyenLoi() } })
    await wrapper.get('input[type="checkbox"]').setValue(false)
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('luu')).toBeUndefined()
    expect(wrapper.get('[role="alert"]').text()).toContain('ít nhất một quyền lợi')
  })
})
