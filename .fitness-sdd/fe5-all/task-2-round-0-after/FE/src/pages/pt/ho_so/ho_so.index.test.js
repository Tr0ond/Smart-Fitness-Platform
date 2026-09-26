import { createPinia } from 'pinia'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import HoSoPt from './ho_so.index.vue'
import {
  capNhatHoSoCaNhanHuanLuyenVien,
  taiHoSoCaNhanHuanLuyenVien,
} from '../../../services/huan_luyen_vien.api.js'

vi.mock('../../../services/huan_luyen_vien.api.js', async () => {
  const actual = await vi.importActual('../../../services/huan_luyen_vien.api.js')
  return {
    ...actual,
    capNhatHoSoCaNhanHuanLuyenVien: vi.fn(),
    taiHoSoCaNhanHuanLuyenVien: vi.fn(),
  }
})

async function taoRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ name: 'ptHoSo', path: '/pt/ho-so', component: HoSoPt }],
  })
  await router.push({ name: 'ptHoSo' })
  return router
}

describe('ho_so.index FE5', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiHoSoCaNhanHuanLuyenVien.mockResolvedValue({
      trainer_code: 'PT-01', introduction: 'Intro cũ', specialties: 'Strength',
    })
    capNhatHoSoCaNhanHuanLuyenVien.mockResolvedValue({
      trainer_code: 'PT-01', introduction: 'Intro mới', specialties: 'Strength',
    })
  })

  it('loads self profile, submits only editable fields, and exposes labelled controls', async () => {
    const router = await taoRouter()
    const wrapper = mount(HoSoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('PT-01')
    expect(wrapper.get('#pt-ho-so-introduction').element.value).toBe('Intro cũ')
    await wrapper.get('#pt-ho-so-introduction').setValue('Intro mới')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(capNhatHoSoCaNhanHuanLuyenVien).toHaveBeenCalledWith({
      introduction: 'Intro mới', specialties: 'Strength',
    })
    expect(wrapper.get('label[for="pt-ho-so-introduction"]').text()).toContain('Giới thiệu')
  })

  it('keeps draft after normalized 422 response', async () => {
    capNhatHoSoCaNhanHuanLuyenVien.mockRejectedValue({
      httpStatus: 422,
      message: 'Nội dung không hợp lệ.',
      fieldErrors: { introduction: ['Nội dung không hợp lệ.'] },
    })
    const router = await taoRouter()
    const wrapper = mount(HoSoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await wrapper.get('#pt-ho-so-introduction').setValue('Draft cần giữ')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('#pt-ho-so-introduction').element.value).toBe('Draft cần giữ')
    expect(wrapper.text()).toContain('Nội dung không hợp lệ.')
  })
})
