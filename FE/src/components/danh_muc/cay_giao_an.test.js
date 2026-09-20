import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import CayGiaoAn from './cay_giao_an.vue'

describe('cay_giao_an FE3', () => {
  it('them ngay va emit tree model', async () => {
    const wrapper = mount(CayGiaoAn, { props: { modelValue: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [] }] } })
    await wrapper.get('button').trigger('click')
    expect(wrapper.findAll('article')).toHaveLength(2)
    expect(wrapper.emitted('update:modelValue')?.at(-1)?.[0]).toHaveLength(2)
  })

  it('hien thi bai tap lich su va cho sua prescription day du', async () => {
    const wrapper = mount(CayGiaoAn, {
      props: {
        modelValue: [{ order: 1, name: 'Ngay 1', estimated_minutes: 45, exercises: [{
          exercise_id: 11,
          exercise_name: 'Retired squat',
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 60,
          notes: null,
        }] }],
        exerciseOptions: [{ id: 12, name: 'Active squat' }],
      },
    })
    const select = wrapper.get('#giao-an-exercise-0-0')
    expect(select.get('option[value="11"]').text()).toContain('Retired squat')
    expect(select.get('option[value="11"]').attributes('disabled')).toBeDefined()
    expect(select.element.value).toBe('11')

    await select.setValue('12')
    await wrapper.get('#giao-an-rest-0-0').setValue(120)
    await wrapper.get('#giao-an-notes-0-0').setValue('Keep tempo')
    expect(wrapper.get('#giao-an-rest-0-0').attributes()).toMatchObject({ min: '0', max: '65535', step: '1', required: '' })
    expect(wrapper.get('#giao-an-notes-0-0').attributes('maxlength')).toBe('1000')
    expect(wrapper.emitted('update:modelValue')?.at(-1)?.[0][0].exercises[0]).toEqual({
      exercise_id: 12,
      order: 1,
      target_sets: 3,
      min_reps: 8,
      max_reps: 12,
      rest_seconds: 120,
      notes: 'Keep tempo',
    })

    await wrapper.setProps({ disabled: true })
    expect(wrapper.get('#giao-an-rest-0-0').element.disabled).toBe(true)
    expect(wrapper.get('#giao-an-notes-0-0').element.disabled).toBe(true)
  })

  it('xoa ghi chu thanh null', async () => {
    const wrapper = mount(CayGiaoAn, {
      props: {
        modelValue: [{ order: 1, name: 'Ngay 1', estimated_minutes: 45, exercises: [{
          exercise_id: 12,
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 60,
          notes: 'Cu',
        }] }],
        exerciseOptions: [{ id: 12, name: 'Active squat' }],
      },
    })
    await wrapper.get('#giao-an-notes-0-0').setValue('')
    expect(wrapper.emitted('update:modelValue')?.at(-1)?.[0][0].exercises[0].notes).toBeNull()
  })
})
