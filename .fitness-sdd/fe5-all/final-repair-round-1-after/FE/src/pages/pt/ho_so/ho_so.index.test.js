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
      data: { trainer_code: 'PT-01', introduction: 'Intro cũ', specialties: 'Strength' },
    })
    capNhatHoSoCaNhanHuanLuyenVien.mockResolvedValue({
      data: { trainer_code: 'PT-01', introduction: 'Intro mới', specialties: 'Strength' },
    })
  })

  it('unwraps the Backend envelope, preserves the untouched field, and exposes labelled controls', async () => {
    const router = await taoRouter()
    const wrapper = mount(HoSoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.text()).toContain('PT-01')
    expect(wrapper.get('#pt-ho-so-introduction').element.value).toBe('Intro cũ')
    expect(wrapper.get('#pt-ho-so-specialties').element.value).toBe('Strength')
    await wrapper.get('#pt-ho-so-introduction').setValue('Intro mới')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(capNhatHoSoCaNhanHuanLuyenVien).toHaveBeenCalledWith({
      introduction: 'Intro mới', specialties: 'Strength',
    })
    expect(wrapper.get('#pt-ho-so-introduction').element.value).toBe('Intro mới')
    expect(wrapper.get('#pt-ho-so-specialties').element.value).toBe('Strength')
    expect(wrapper.get('label[for="pt-ho-so-introduction"]').text()).toContain('Giới thiệu')
  })

  it('reconciles a timeout from the Backend envelope without repeating PATCH or replacing the draft', async () => {
    const hoSoLuu = { trainer_code: 'PT-01', introduction: 'Intro cũ', specialties: 'Strength' }
    taiHoSoCaNhanHuanLuyenVien
      .mockReset()
      .mockResolvedValueOnce({ data: hoSoLuu })
      .mockResolvedValueOnce({ data: hoSoLuu })
    capNhatHoSoCaNhanHuanLuyenVien.mockRejectedValue({ httpStatus: 503, message: 'timeout' })

    const router = await taoRouter()
    const wrapper = mount(HoSoPt, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await wrapper.get('#pt-ho-so-introduction').setValue('Bản nháp cần giữ')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taiHoSoCaNhanHuanLuyenVien).toHaveBeenCalledTimes(2)
    expect(capNhatHoSoCaNhanHuanLuyenVien).toHaveBeenCalledTimes(1)
    expect(wrapper.get('#pt-ho-so-introduction').element.value).toBe('Bản nháp cần giữ')
    expect(wrapper.get('#pt-ho-so-specialties').element.value).toBe('Strength')
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
