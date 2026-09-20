import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const api = vi.hoisted(() => ({
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/giao_an_mau.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
import GiaoAnMauTaoMoi from './giao_an_mau.tao_moi.vue'

async function dienForm(wrapper) {
  await wrapper.find('#giao-an-create-code').setValue('GA01')
  await wrapper.find('#giao-an-create-name').setValue('Sức mạnh')
  await wrapper.find('#giao-an-create-goal').setValue('Cơ bản')
  await wrapper.find('#giao-an-create-level').setValue('Beginner')
  await wrapper.find('#giao-an-create-sessions').setValue(1)
}

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('giao_an_mau.tao_moi FE3', () => {
  it('mount tree, gửi full metadata/days và điều hướng', async () => {
    api.taoGiaoAnMau.mockResolvedValueOnce({ data: { id: 14 } })
    const wrapper = mount(GiaoAnMauTaoMoi)
    await flushPromises()
    await dienForm(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(api.taoGiaoAnMau.mock.calls[0][0]).toMatchObject({ code: 'GA01', sessions_per_week: 1, days: [{ order: 1 }] })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 14 } })
  })

  it('chặn sessions ngoài 1..7 và mismatch với cây trước request', async () => {
    const wrapper = mount(GiaoAnMauTaoMoi)
    await flushPromises()
    await dienForm(wrapper)
    await wrapper.find('#giao-an-create-sessions').setValue(8)
    await wrapper.find('form').trigger('submit')
    expect(api.taoGiaoAnMau).not.toHaveBeenCalled()
  })

  it('khóa double-submit khi POST đang chờ', async () => {
    let resolveRequest
    api.taoGiaoAnMau.mockReturnValueOnce(new Promise((resolve) => { resolveRequest = resolve }))
    const wrapper = mount(GiaoAnMauTaoMoi)
    await flushPromises()
    await dienForm(wrapper)
    const form = wrapper.find('form')
    await form.trigger('submit')
    await form.trigger('submit')
    expect(api.taoGiaoAnMau).toHaveBeenCalledTimes(1)
    resolveRequest({ data: { id: 14 } })
    await flushPromises()
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 14 } })
  })
})
