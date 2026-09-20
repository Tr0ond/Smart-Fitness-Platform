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
import GiaoAnMau from './giao_an_mau.index.vue'

const rows = (status = 'HOAT_DONG') => [{ id: 6, code: 'GA01', name: 'Sức mạnh', goal: 'Cơ bản', level: 'Beginner', sessions_per_week: 2, day_count: 2, status }]

beforeEach(() => {
  setActivePinia(createPinia())
  router.push.mockReset()
  Object.values(api).forEach((mock) => mock.mockReset().mockResolvedValue({ data: [] }))
})

describe('giao_an_mau.index FE3', () => {
  it('render data, navigate detail/create and keep dialog after 422', async () => {
    api.taiDanhSachGiaoAnMau.mockResolvedValueOnce({ data: rows() })
    api.capNhatGiaoAnMau.mockRejectedValueOnce({ httpStatus: 422, message: 'Không thể cập nhật.' })
    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    await wrapper.find('table button.nut--lien-ket').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminChiTietGiaoAnMau', params: { id: 6 } })
    await wrapper.findAll('button').find((button) => button.text() === 'Ngừng sử dụng').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    await wrapper.find('button.nut--chinh').trigger('click')
    expect(router.push).toHaveBeenCalledWith({ name: 'adminTaoGiaoAnMau' })
  })

  it('unknown -> GET previous -> explicit retry PATCH -> GET intended', async () => {
    api.taiDanhSachGiaoAnMau
      .mockResolvedValueOnce({ data: rows('HOAT_DONG') })
      .mockResolvedValueOnce({ data: rows('HOAT_DONG') })
      .mockResolvedValueOnce({ data: rows('NGUNG_SU_DUNG') })
    api.capNhatGiaoAnMau.mockRejectedValueOnce({ isNetworkError: true, message: 'Mạng không ổn định.' }).mockResolvedValueOnce({ data: { id: 6 } })
    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Đối soát trạng thái')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Thử lại cập nhật')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('unknown reconcile returning intended closes with one PATCH', async () => {
    api.taiDanhSachGiaoAnMau
      .mockResolvedValueOnce({ data: rows('HOAT_DONG') })
      .mockResolvedValueOnce({ data: rows('NGUNG_SU_DUNG') })
    api.capNhatGiaoAnMau.mockRejectedValueOnce({ isNetworkError: true, message: 'status result unknown' })

    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()

    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(api.taiDanhSachGiaoAnMau).toHaveBeenCalledTimes(2)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('missing reconcile stays open and a new target does not inherit uncertainty', async () => {
    const itemOne = rows('HOAT_DONG')[0]
    const itemTwo = { ...itemOne, id: 7, code: 'GA02', name: 'Mobility' }
    api.taiDanhSachGiaoAnMau
      .mockResolvedValueOnce({ data: [itemOne, itemTwo] })
      .mockResolvedValueOnce({ data: [itemTwo] })
      .mockResolvedValueOnce({ data: [{ ...itemTwo, status: 'NGUNG_SU_DUNG' }] })
    api.capNhatGiaoAnMau
      .mockRejectedValueOnce({ isNetworkError: true, message: 'first result unknown' })
      .mockResolvedValueOnce({ data: { id: 7 } })

    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    await wrapper.findAll('table button.nut--phu')[0].trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)

    await wrapper.find('[role="dialog"] button.nut--phu').trigger('click')
    await wrapper.find('table button.nut--phu').trigger('click')
    await wrapper.find('[role="dialog"] button.nut--nguy-hiem').trigger('click')
    await flushPromises()
    expect(api.capNhatGiaoAnMau).toHaveBeenNthCalledWith(2, 7, { status: 'NGUNG_SU_DUNG' })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('loading/error/empty states support retry', async () => {
    api.taiDanhSachGiaoAnMau.mockRejectedValueOnce({ httpStatus: 503, message: 'Không thể tải giáo án.' })
    const wrapper = mount(GiaoAnMau)
    await flushPromises()
    expect(wrapper.text()).toContain('Không thể tải giáo án.')
    api.taiDanhSachGiaoAnMau.mockResolvedValueOnce({ data: [] })
    await wrapper.findAll('button').find((button) => button.text().includes('Thử lại')).trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Chưa có giáo án mẫu')
  })
})
