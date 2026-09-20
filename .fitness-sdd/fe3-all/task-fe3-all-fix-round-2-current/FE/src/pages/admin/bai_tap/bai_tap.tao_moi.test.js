import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const api = vi.hoisted(() => ({
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import BaiTapTaoMoi from './bai_tap.tao_moi.vue'

async function dienForm(wrapper, { instructions = 'Giữ lưng thẳng.' } = {}) {
  await wrapper.find('#bai-tap-create-code').setValue('SQUAT')
  await wrapper.find('#bai-tap-create-name').setValue('Squat')
  await wrapper.find('#bai-tap-create-difficulty').setValue('Beginner')
  await wrapper.find('#bai-tap-create-instructions').setValue(instructions)
  await wrapper.find('#bai-tap-equipment').setValue(['3'])
  await wrapper.find('input[type="checkbox"]').setValue(true)
}

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
  api.taiDanhSachDungCu.mockResolvedValue({ data: [{ id: 3, code: 'DB', name: 'Tạ tay', status: 'HOAT_DONG' }] })
  api.taiDanhSachNhomCo.mockResolvedValue({ data: [{ id: 8, code: 'NGUC', name: 'Ngực', status: 'HOAT_DONG' }] })
})

describe('bai_tap.tao_moi FE3', () => {
  it('mount form, gửi đủ instructions/quan hệ Q11 và điều hướng', async () => {
    api.taoBaiTap.mockResolvedValueOnce({ data: { id: 12 } })
    const wrapper = mount(BaiTapTaoMoi)
    await flushPromises()
    await dienForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoBaiTap).toHaveBeenCalledTimes(1)
    expect(api.taoBaiTap.mock.calls[0][0]).toMatchObject({
      code: 'SQUAT',
      instructions: 'Giữ lưng thẳng.',
      image_path: null,
      video_path: null,
      equipment_ids: [3],
      muscle_groups: [{ id: 8, role: 'CHINH' }],
    })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietBaiTap', params: { id: 12 } })
  })

  it('instructions thiếu hoặc blank bị chặn trước request', async () => {
    const wrapper = mount(BaiTapTaoMoi)
    await flushPromises()
    await dienForm(wrapper, { instructions: '   ' })
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoBaiTap).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Hướng dẫn là bắt buộc')
  })

  it('khóa double-submit khi POST đang chờ', async () => {
    let resolveRequest
    api.taoBaiTap.mockReturnValueOnce(new Promise((resolve) => { resolveRequest = resolve }))
    const wrapper = mount(BaiTapTaoMoi)
    await flushPromises()
    await dienForm(wrapper)
    const form = wrapper.find('form')
    await form.trigger('submit')
    await form.trigger('submit')
    expect(api.taoBaiTap).toHaveBeenCalledTimes(1)
    resolveRequest({ data: { id: 13 } })
    await flushPromises()
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietBaiTap', params: { id: 13 } })
  })
})
