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
})
