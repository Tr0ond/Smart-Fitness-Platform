import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import App from '@/App.vue'

describe('App', () => {
  it('mounts the existing Vue SFC through the Vitest pipeline', () => {
    const wrapper = mount(App, {
      global: {
        stubs: {
          RouterView: {
            template: '<div data-testid="router-view-stub"></div>',
          },
        },
      },
    })

    expect(wrapper.find('[data-testid="router-view-stub"]').exists()).toBe(true)
  })
})
