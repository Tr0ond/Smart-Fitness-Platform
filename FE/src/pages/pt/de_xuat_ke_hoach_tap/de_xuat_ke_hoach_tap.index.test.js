import { createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import DanhSachDeXuat from './de_xuat_ke_hoach_tap.index.vue'
import TaoDeXuat from './de_xuat_ke_hoach_tap.tao_moi.vue'
import { useDeXuatStore } from '../../../stores/de_xuat.store.js'
import { taiDanhSachDeXuat, taoDeXuatKeHoach } from '../../../services/de_xuat.api.js'
import { taiChiTietHoiVien } from '../../../services/hoi_vien_pt.api.js'
import { taiKeHoachTapHoiVien } from '../../../services/ke_hoach_tap_pt.api.js'

vi.mock('../../../services/de_xuat.api.js', async () => {
  const actual = await vi.importActual('../../../services/de_xuat.api.js')
  return { ...actual, taiDanhSachDeXuat: vi.fn(), taoDeXuatKeHoach: vi.fn() }
})

vi.mock('../../../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/hoi_vien_pt.api.js')
  return { ...actual, taiChiTietHoiVien: vi.fn() }
})

vi.mock('../../../services/ke_hoach_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../../../services/ke_hoach_tap_pt.api.js')
  return { ...actual, taiKeHoachTapHoiVien: vi.fn() }
})

vi.mock('../../../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(() => '9c87e6ae-69e1-4a15-8330-9848fe48c7d2'),
}))

vi.mock('../../../components/PT/thanh_dieu_huong_hoi_vien.vue', () => ({
  default: { template: '<nav aria-label="Điều hướng hồ sơ hội viên" />' },
}))

const KE_HOACH = {
  plan: {
    id: 22,
    name: 'Nền tảng sức mạnh',
    current_version: {
      number: 3,
      goal: 'Tăng sức mạnh',
      template_id: 6,
      days: [{
        order: 1,
        weekday: 2,
        name: 'Thân trên',
        estimated_minutes: 55,
        exercises: [{ id: 91, exercise_id: 14, order: 1, target_sets: 3, min_reps: 6, max_reps: 8, rest_seconds: 90 }],
      }],
    },
  },
}

function taoDeferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })
  return { promise, resolve, reject }
}

async function taoRouter(pathName = 'ptTaoDeXuatKeHoach', id = '7') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/pt/hoi-vien', name: 'ptHoiVien', component: { template: '<div>Hội viên</div>' } },
      { path: '/pt/hoi-vien/:id/de-xuat', name: 'ptDeXuatKeHoach', component: DanhSachDeXuat },
      { path: '/pt/hoi-vien/:id/de-xuat/tao-moi', name: 'ptTaoDeXuatKeHoach', component: TaoDeXuat },
    ],
  })
  await router.push({ name: pathName, params: { id } })
  await router.isReady()
  return router
}

describe('de_xuat_ke_hoach_tap PT FE6', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    taiDanhSachDeXuat.mockResolvedValue({ data: [] })
    taiChiTietHoiVien.mockResolvedValue({ data: { member: { id: 7 }, assignment: { id: 71, is_current: true } } })
    taiKeHoachTapHoiVien.mockResolvedValue({ data: KE_HOACH })
  })

  it('lists backend statuses, previews proposal and exposes no PT apply action', async () => {
    taiDanhSachDeXuat.mockResolvedValue({ data: [{
      id: 5,
      title: 'Thay đổi buổi thân trên',
      change_type: 'THAY_BAI',
      explanation: 'Thay bài sau khi hội viên thống nhất mục tiêu.',
      effective_from: '2026-10-02',
      expires_at: '2026-10-03T12:00:00Z',
      status: 'CHO_XAC_NHAN',
      content: { plan: { name: 'Bản đề xuất', days: [] } },
    }] })
    const router = await taoRouter('ptDeXuatKeHoach')
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()

    expect(wrapper.text()).toContain('Tối đa 100 đề xuất gần nhất')
    expect(wrapper.text()).toContain('Chờ hội viên xác nhận')
    await wrapper.get('button[aria-label="Xem trước Thay đổi buổi thân trên"]').trigger('click')
    await flushPromises()
    expect(document.body.textContent).toContain('hội viên xác nhận trên ứng dụng di động')
    expect(wrapper.text()).not.toContain('Áp dụng kế hoạch')
  })

  it('uses the official plan as an adjustment draft and submits only Q13 allow-listed fields', async () => {
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.get('#pt-de-xuat-loai').element.value).toBe('DIEU_CHINH')
    expect(wrapper.get('#pt-de-xuat-ke-hoach-ten').element.value).toBe('Nền tảng sức mạnh')
    expect(wrapper.get('#pt-de-xuat-bai-id-0-0').element.value).toBe('14')
    expect(wrapper.text()).toContain('không phụ thuộc gói, chat hoặc lượt PT')

    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Điều chỉnh nhịp tập')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Thêm hiệp để tăng tải từ từ.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    taoDeXuatKeHoach.mockResolvedValue({ data: { id: 43, status: 'CHO_XAC_NHAN', expires_at: '2026-10-21T00:00:00Z' } })
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const [memberId, body, key] = taoDeXuatKeHoach.mock.calls[0]
    expect(memberId).toBe(7)
    expect(body).toMatchObject({
      change_type: 'DIEU_CHINH',
      title: 'Điều chỉnh nhịp tập',
      explanation: 'Thêm hiệp để tăng tải từ từ.',
      effective_from: '2026-10-20',
      plan: { name: 'Nền tảng sức mạnh', goal: 'Tăng sức mạnh' },
    })
    expect(body).not.toHaveProperty('source')
    expect(body).not.toHaveProperty('assignment_id')
    expect(body).not.toHaveProperty('membership')
    expect(body).not.toHaveProperty('quota')
    expect(key).toBe('9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
    expect(wrapper.text()).toContain('chờ hội viên xác nhận')
  })

  it('uses TAO_MOI without official plan and keeps draft after 409 until explicit refetch', async () => {
    taiKeHoachTapHoiVien.mockResolvedValue({ data: { plan: null } })
    taoDeXuatKeHoach.mockRejectedValue({ httpStatus: 409, message: 'Plan changed' })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    expect(wrapper.get('#pt-de-xuat-loai').element.value).toBe('TAO_MOI')
    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Bản mới')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Bản mới theo mục tiêu hội viên.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    await wrapper.get('#pt-de-xuat-ke-hoach-ten').setValue('Kế hoạch mới')
    await wrapper.get('#pt-de-xuat-ke-hoach-muc-tieu').setValue('Sức bền')
    await wrapper.get('#pt-de-xuat-bai-id-0-0').setValue('14')
    const store = useDeXuatStore()
    const draft = JSON.parse(JSON.stringify(store.banNhap))
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('Bản nháp được giữ nguyên')
    expect(store.banNhap).toEqual(draft)
    expect(store.loiTaoDeXuat.httpStatus).toBe(409)
    const nutTaiLai = wrapper.findAll('button').find((nut) => nut.text().includes('Tải lại ngữ cảnh'))
    await nutTaiLai.trigger('click')
    await flushPromises()
    expect(store.banNhap).toEqual(draft)
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
  })

  it('refetches and replays only the same frozen payload and key after unknown outcome', async () => {
    taoDeXuatKeHoach
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { id: 44, status: 'CHO_XAC_NHAN', replayed: true, expires_at: '2026-10-21T00:00:00Z' } })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Giữ đề xuất')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Chờ backend đối soát.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.text()).toContain('gửi nguyên body và khóa ban đầu')
    expect(wrapper.get('#pt-de-xuat-tieu-de').element.matches(':disabled')).toBe(true)
    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(1)

    const nutThuLai = wrapper.findAll('button').find((nut) => nut.text().includes('Gửi lại nguyên'))
    await nutThuLai.trigger('click')
    await flushPromises()
    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach.mock.calls[1]).toEqual(taoDeXuatKeHoach.mock.calls[0])
    expect(wrapper.text()).toContain('Backend xác nhận đề xuất đã được gửi trước đó')
  })

  it('preserves the same-member draft and maps nested 422 field errors', async () => {
    taoDeXuatKeHoach.mockRejectedValue({
      httpStatus: 422,
      message: 'Invalid request',
      fieldErrors: { 'plan.days.0.exercises.0.exercise_id': ['Bài tập không tồn tại.'] },
    })
    const router = await taoRouter()
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [createPinia(), router] } })
    await flushPromises()
    await wrapper.get('#pt-de-xuat-tieu-de').setValue('Giữ lại')
    await wrapper.get('#pt-de-xuat-giai-thich').setValue('Bản nháp được giữ nguyên.')
    await wrapper.get('#pt-de-xuat-ngay-ap-dung').setValue('2026-10-20')
    await wrapper.get('form').trigger('submit')
    await flushPromises()
    expect(wrapper.get('#pt-de-xuat-tieu-de').element.value).toBe('Giữ lại')
    expect(wrapper.text()).toContain('plan.days.0.exercises.0.exercise_id: Bài tập không tồn tại.')
  })

  it('preserves draft across list/create navigation but clears on Member switch', async () => {
    const pinia = createPinia()
    const router = await taoRouter('ptDeXuatKeHoach')
    const wrapper = mount({ template: '<RouterView />' }, { global: { plugins: [pinia, router] } })
    await flushPromises()
    const store = useDeXuatStore(pinia)
    store.banNhap.title = 'Draft across routes'
    await router.push({ name: 'ptTaoDeXuatKeHoach', params: { id: '7' } })
    await flushPromises()
    expect(store.banNhap.title).toBe('Draft across routes')

    await router.push({ name: 'ptTaoDeXuatKeHoach', params: { id: '8' } })
    await flushPromises()
    expect(store.memberId).toBe(8)
    expect(store.banNhap.title).not.toBe('Draft across routes')
  })
})
