import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  onboardTaiKhoanHuanLuyenVien,
  taiHoSoHuanLuyenVien,
  taoHuanLuyenVien,
} from '../services/huan_luyen_vien.api.js'
import { useHuanLuyenVienStore } from './huan_luyen_vien.store.js'

vi.mock('../services/huan_luyen_vien.api.js', async () => {
  const actual = await vi.importActual('../services/huan_luyen_vien.api.js')
  return {
    ...actual,
    onboardTaiKhoanHuanLuyenVien: vi.fn(),
    taiHoSoHuanLuyenVien: vi.fn(),
    taoHuanLuyenVien: vi.fn(),
  }
})

const PROFILE = {
  account_id: 7,
  trainer_profile_id: 11,
  trainer_code: 'PT000011',
  status: 'HOAT_DONG',
  introduction: null,
  specialties: null,
  updated_at: '2026-09-07T10:00:00.000Z',
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

describe('huan_luyen_vien.store FE2-T02/T04', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    globalThis.crypto ??= {}
    globalThis.crypto.randomUUID = vi.fn(() => '00000000-0000-4000-8000-000000000001')
  })

  it('giu key onboarding qua loi va commit ket qua validated sau success', async () => {
    taoHuanLuyenVien
      .mockRejectedValueOnce({ httpStatus: 503, isNetworkError: true, message: 'retry' })
      .mockResolvedValueOnce({ data: {
        account: { id: 7, email: 'pt@example.com', status: 'HOAT_DONG' },
        trainer_profile: { id: 11, trainer_code: 'PT000011', status: 'HOAT_DONG' },
        role: { code: 'PT', active: true }, invitation: 'QUEUED',
      } })
    const store = useHuanLuyenVienStore()

    await store.taoMoiHuanLuyenVien({ duLieu: { name: 'PT', email: 'pt@example.com' } })
    const key = store.khoaOnboarding
    await store.taoMoiHuanLuyenVien({ duLieu: { name: 'PT', email: 'pt@example.com' } })

    expect(key).toBe('00000000-0000-4000-8000-000000000001')
    expect(taoHuanLuyenVien).toHaveBeenNthCalledWith(2, { name: 'PT', email: 'pt@example.com' }, key)
    expect(store.ketQuaOnboarding.account.id).toBe(7)
    expect(store.khoaOnboarding).toBeNull()
  })

  it('chi doi key onboarding khi form da loi co material edit', async () => {
    globalThis.crypto.randomUUID
      .mockReturnValueOnce('00000000-0000-4000-8000-000000000001')
      .mockReturnValueOnce('00000000-0000-4000-8000-000000000002')
    taoHuanLuyenVien.mockRejectedValueOnce({ httpStatus: 503, isNetworkError: true, message: 'retry' })
    const store = useHuanLuyenVienStore()

    await store.taoMoiHuanLuyenVien({ duLieu: { name: 'PT', email: 'pt@example.com' } })
    expect(store.khoaOnboarding).toBe('00000000-0000-4000-8000-000000000001')
    store.danhDauThayDoiNoiDungOnboarding()
    expect(store.khoaOnboarding).toBe('00000000-0000-4000-8000-000000000002')
    expect(store.loiOnboarding).toBeNull()
  })

  it('vo hieu response profile cu sau cleanup', async () => {
    let resolveRequest
    taiHoSoHuanLuyenVien.mockReturnValueOnce(new Promise((resolve) => { resolveRequest = resolve }))
    const store = useHuanLuyenVienStore()
    const request = store.taiHoSoHuanLuyenVien(7)
    store.xoaDuLieu()
    resolveRequest({ data: PROFILE })
    await request

    expect(store.hoSoDaChon).toBeNull()
  })

  it('edit dung allow-list key va refetch profile authoritative', async () => {
    onboardTaiKhoanHuanLuyenVien.mockResolvedValue({ data: {} })
    taiHoSoHuanLuyenVien.mockResolvedValue({ data: PROFILE })
    const store = useHuanLuyenVienStore()

    await store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A', status: 'HOAT_DONG', account_id: 999 })

    expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledWith(7,
      { introduction: 'A', status: 'HOAT_DONG', account_id: 999 },
      expect.any(String))
    expect(store.hoSoDaChon).toEqual(PROFILE)
  })

  it('cleanup sau POST profile pending chan GET tiep theo va phuc hoi state', async () => {
    const pendingPost = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockReturnValueOnce(pendingPost.promise)
    const store = useHuanLuyenVienStore()
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingPost.resolve({ data: {} })
    await request

    expect(taiHoSoHuanLuyenVien).not.toHaveBeenCalled()
    expect(store.hoSoDaChon).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
  })

  it('cleanup trong GET profile thanh cong chan profile message key va loading', async () => {
    const pendingGet = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockResolvedValueOnce({ data: {} })
    taiHoSoHuanLuyenVien.mockReturnValueOnce(pendingGet.promise)
    const store = useHuanLuyenVienStore()
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingGet.resolve({ data: PROFILE })
    await request

    expect(store.hoSoDaChon).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
    expect(store.dangTaiHoSo).toBe(false)
  })

  it('cleanup trong GET profile sau 409 chan loi conflict va state cu', async () => {
    const pendingGet = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    taiHoSoHuanLuyenVien.mockReturnValueOnce(pendingGet.promise)
    const store = useHuanLuyenVienStore()
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingGet.resolve({ data: PROFILE })
    await request

    expect(store.hoSoDaChon).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
    expect(store.dangTaiHoSo).toBe(false)
  })

  it('xoaHoSoDangChon trong POST profile pending chan GET tiep theo va reset read update state', async () => {
    const pendingPost = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockReturnValueOnce(pendingPost.promise)
    const store = useHuanLuyenVienStore()
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(onboardTaiKhoanHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaHoSoDangChon()
    pendingPost.resolve({ data: {} })
    await request

    expect(taiHoSoHuanLuyenVien).not.toHaveBeenCalled()
    expect(store.hoSoDaChon).toBeNull()
    expect(store.dangTaiHoSo).toBe(false)
    expect(store.loiTaiHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
  })

  it('xoaHoSoDangChon trong GET profile thanh cong pending chan profile key message error loading', async () => {
    const pendingGet = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockResolvedValueOnce({ data: {} })
    taiHoSoHuanLuyenVien.mockReturnValueOnce(pendingGet.promise)
    const store = useHuanLuyenVienStore()
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaHoSoDangChon()
    pendingGet.resolve({ data: PROFILE })
    await request

    expect(store.hoSoDaChon).toBeNull()
    expect(store.dangTaiHoSo).toBe(false)
    expect(store.loiTaiHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
  })

  it('xoaHoSoDangChon trong GET profile sau 409 giu nguyen onboarding va chan commit cu', async () => {
    const pendingGet = taoDeferred()
    onboardTaiKhoanHuanLuyenVien.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    taiHoSoHuanLuyenVien.mockReturnValueOnce(pendingGet.promise)
    const store = useHuanLuyenVienStore()
    const onboardingError = { httpStatus: 422, message: 'onboarding error' }
    const onboardingResult = { account: { id: 7 } }
    store.soThuTuOnboarding = 41
    store.dangOnboarding = true
    store.loiOnboarding = onboardingError
    store.ketQuaOnboarding = onboardingResult
    store.khoaOnboarding = 'onboarding-key'
    const onboardingBefore = {
      sequence: store.soThuTuOnboarding,
      loading: store.dangOnboarding,
      error: store.loiOnboarding,
      result: store.ketQuaOnboarding,
      key: store.khoaOnboarding,
    }
    const request = store.capNhatHoSoHuanLuyenVien(7, { introduction: 'A' })

    await vi.waitFor(() => expect(taiHoSoHuanLuyenVien).toHaveBeenCalledTimes(1))
    store.xoaHoSoDangChon()
    pendingGet.resolve({ data: PROFILE })
    await request

    expect(store.soThuTuOnboarding).toBe(onboardingBefore.sequence)
    expect(store.dangOnboarding).toBe(onboardingBefore.loading)
    expect(store.loiOnboarding).toBe(onboardingBefore.error)
    expect(store.ketQuaOnboarding).toBe(onboardingBefore.result)
    expect(store.khoaOnboarding).toBe(onboardingBefore.key)
    expect(store.hoSoDaChon).toBeNull()
    expect(store.dangTaiHoSo).toBe(false)
    expect(store.loiTaiHoSo).toBeNull()
    expect(store.dangCapNhatHoSo).toBe(false)
    expect(store.loiCapNhatHoSo).toBeNull()
    expect(store.thongBaoCapNhatHoSo).toBeNull()
    expect(store.khoaCapNhatHoSo).toBeNull()
  })
})
