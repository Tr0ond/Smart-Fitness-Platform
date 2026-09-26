import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import TheTienDo from './the_tien_do.vue'

describe('TheTienDo', () => {
  it('presents a metric without interpreting it as a diagnosis', () => {
    const wrapper = mount(TheTienDo, {
      props: { nhan: 'Cân nặng', giaTri: 72, donVi: 'kg', troGiup: 'Theo dữ liệu gần nhất' },
    })

    expect(wrapper.text()).toContain('Cân nặng')
    expect(wrapper.text()).toContain('72')
    expect(wrapper.text()).toContain('Theo dữ liệu gần nhất')
    expect(wrapper.attributes('aria-label')).toBeUndefined()
  })
})
