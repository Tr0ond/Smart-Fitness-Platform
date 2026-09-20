import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const router = { push: vi.fn() }
const api = vi.hoisted(() => ({
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('vue-router', () => ({ useRouter: () => router }))
vi.mock('../../../services/goi_tap.api.js', () => api)
vi.mock('../../../services/dung_cu.api.js', () => api)
vi.mock('../../../services/nhom_co.api.js', () => api)
vi.mock('../../../services/bai_tap.api.js', () => api)
vi.mock('../../../services/giao_an_mau.api.js', () => api)
import GoiTapTaoMoi from './goi_tap.tao_moi.vue'

async function dienForm(wrapper) {
  await wrapper.find('[name="code"]').setValue('BASIC')
  await wrapper.find('[name="name"]').setValue('Gói cơ bản')
  await wrapper.find('[name="price"]').setValue(100)
  await wrapper.find('[name="duration_days"]').setValue(30)
}

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('goi_tap.tao_moi FE3', () => {
  it('mount form, gửi metadata/benefits và điều hướng sau khi tạo thành công', async () => {
    api.taoGoiTap.mockResolvedValueOnce({ data: { id: 9 } })
    const wrapper = mount(GoiTapTaoMoi)
    await dienForm(wrapper)
    expect(wrapper.find('[name="price"]').attributes()).toMatchObject({ min: '1', max: '999999999999999', step: '1' })
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoGoiTap).toHaveBeenCalledTimes(1)
    expect(api.taoGoiTap.mock.calls[0][0]).toMatchObject({ code: 'BASIC', name: 'Gói cơ bản', price: 100, benefits: expect.any(Object) })
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGoiTap', params: { id: 9 } })
  })

  it('chặn price 0/fraction và duration ngoài bounds trước request', async () => {
    const wrapper = mount(GoiTapTaoMoi)
    await dienForm(wrapper)
    await wrapper.find('[name="price"]').setValue('0')
    await wrapper.find('form').trigger('submit')
    expect(api.taoGoiTap).not.toHaveBeenCalled()
    await wrapper.find('[name="price"]').setValue('1.5')
    await wrapper.find('[name="duration_days"]').setValue('65536')
    await wrapper.find('form').trigger('submit')
    expect(api.taoGoiTap).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Thời hạn')
  })

  it('chặn bộ benefits rỗng và không phát POST', async () => {
    const wrapper = mount(GoiTapTaoMoi)
    await dienForm(wrapper)
    await wrapper.findAll('input[type="checkbox"]')[0].setValue(false)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
    expect(api.taoGoiTap).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('ít nhất một quyền lợi')
  })

  it('khóa double-submit khi POST đang chờ', async () => {
    let resolveRequest
    api.taoGoiTap.mockReturnValueOnce(new Promise((resolve) => { resolveRequest = resolve }))
    const wrapper = mount(GoiTapTaoMoi)
    await dienForm(wrapper)
    const form = wrapper.find('form')
    await form.trigger('submit')
    await form.trigger('submit')
    expect(api.taoGoiTap).toHaveBeenCalledTimes(1)
    resolveRequest({ data: { id: 10 } })
    await flushPromises()
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGoiTap', params: { id: 10 } })
  })
})
