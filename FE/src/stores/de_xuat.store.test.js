import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { taoDeXuatKeHoach, taiDanhSachDeXuat } from '../services/de_xuat.api.js'
import { useDeXuatStore, xoaDuLieuDeXuatPtNeuDaKhoiTao } from './de_xuat.store.js'

vi.mock('../services/de_xuat.api.js', async () => {
  const actual = await vi.importActual('../services/de_xuat.api.js')
  return {
    ...actual,
    taoDeXuatKeHoach: vi.fn(),
    taiDanhSachDeXuat: vi.fn(),
  }
})

vi.mock('../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(() => '9c87e6ae-69e1-4a15-8330-9848fe48c7d2'),
}))

function taoBanNhapHopLe(store) {
  store.banNhap = {
    change_type: 'TAO_MOI',
    title: 'Kế hoạch mới',
    explanation: 'Tạo kế hoạch theo mục tiêu đã thống nhất.',
    effective_from: '2026-10-02',
    plan: {
      name: 'Nền tảng',
      goal: 'Sức bền',
      days: [{
        order: 1,
        weekday: 2,
        name: 'Ngày 1',
        estimated_minutes: 60,
        exercises: [{
          exercise_id: 12,
          order: 1,
          target_sets: 3,
          min_reps: 8,
          max_reps: 12,
          rest_seconds: 90,
        }],
      }],
    },
  }
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

describe('de_xuat.store FE6', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
    taiDanhSachDeXuat.mockResolvedValue({ data: [] })
  })

  it('creates a PT proposal from current assignment context without membership or quota gates', async () => {
    taoDeXuatKeHoach.mockResolvedValue({ data: { id: 41, status: 'CHO_XAC_NHAN', source: 'HUAN_LUYEN_VIEN' } })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)

    await expect(store.taoDeXuatKeHoach()).resolves.toMatchObject({ id: 41, status: 'CHO_XAC_NHAN' })

    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
    expect(taoDeXuatKeHoach.mock.calls[0][0]).toBe(7)
    expect(taoDeXuatKeHoach.mock.calls[0][1]).toEqual(expect.objectContaining({ change_type: 'TAO_MOI' }))
    expect(taoDeXuatKeHoach.mock.calls[0][1]).not.toHaveProperty('source')
    expect(taoDeXuatKeHoach.mock.calls[0][2]).toBe('9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
    expect(store.banNhap.title).toBe('')
    expect(store.ketQuaDeXuat.replayed).toBeUndefined()
  })

  it('refetches then retries unknown outcome with the exact frozen body and the same key', async () => {
    taoDeXuatKeHoach
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { id: 42, status: 'CHO_XAC_NHAN', replayed: true } })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)
    const frozenDraft = JSON.parse(JSON.stringify(store.banNhap))

    await store.taoDeXuatKeHoach()
    expect(store.thaoTacDangCho.outcomeUnknown).toBe(true)
    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(1)
    expect(store.banNhap).toEqual(frozenDraft)

    await store.thuLaiDeXuat()
    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach.mock.calls[1]).toEqual(taoDeXuatKeHoach.mock.calls[0])
    expect(store.ketQuaDeXuat).toMatchObject({ id: 42, replayed: true })
    expect(store.thaoTacDangCho).toBeNull()
  })

  it('does not replay an unknown proposal until a fresh list request succeeds', async () => {
    taoDeXuatKeHoach
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { id: 43, status: 'CHO_XAC_NHAN', replayed: true } })
    taiDanhSachDeXuat
      .mockRejectedValueOnce({ httpStatus: 503, message: 'list unavailable' })
      .mockResolvedValueOnce({ data: [] })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)

    await store.taoDeXuatKeHoach()
    expect(store.thaoTacDangCho).toMatchObject({ outcomeUnknown: true })
    expect(store.loiTaiDanhSachDeXuat.httpStatus).toBe(503)
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)

    await store.thuLaiDeXuat()

    expect(taiDanhSachDeXuat).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(2)
    expect(taoDeXuatKeHoach.mock.calls[1]).toEqual(taoDeXuatKeHoach.mock.calls[0])
    expect(store.ketQuaDeXuat).toMatchObject({ id: 43, replayed: true })
  })

  it.each([403, 404])('reports assignment loss when unknown-outcome refetch returns %s', async (httpStatus) => {
    taoDeXuatKeHoach.mockRejectedValue({ isNetworkError: true, message: 'timeout' })
    taiDanhSachDeXuat.mockRejectedValue({ httpStatus, message: 'assignment unavailable' })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)

    await expect(store.taoDeXuatKeHoach()).resolves.toMatchObject({ scopeLost: true, memberId: 7 })

    expect(store.memberId).toBeNull()
    expect(store.thaoTacDangCho).toBeNull()
    expect(store.banNhap.title).toBe('')
    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
  })

  it('returns assignment loss instead of hiding it when retry preflight fails', async () => {
    taoDeXuatKeHoach
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { id: 45, replayed: true } })
    taiDanhSachDeXuat
      .mockResolvedValueOnce({ data: [] })
      .mockRejectedValueOnce({ httpStatus: 404, message: 'assignment unavailable' })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)
    await store.taoDeXuatKeHoach()

    await expect(store.thuLaiDeXuat()).resolves.toMatchObject({ scopeLost: true, memberId: 7 })

    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
    expect(store.memberId).toBeNull()
    expect(store.thaoTacDangCho).toBeNull()
  })

  it('does not write a late proposal response after auth cleanup', async () => {
    const pending = taoDeferred()
    taoDeXuatKeHoach.mockReturnValue(pending.promise)
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)
    const submit = store.taoDeXuatKeHoach()

    expect(store.dangTaoDeXuat).toBe(true)
    xoaDuLieuDeXuatPtNeuDaKhoiTao()
    pending.resolve({ data: { id: 44, status: 'CHO_XAC_NHAN' } })
    await submit

    expect(store.memberId).toBeNull()
    expect(store.danhSachDeXuat).toEqual([])
    expect(store.ketQuaDeXuat).toBeNull()
    expect(store.thaoTacDangCho).toBeNull()
    expect(store.dangTaoDeXuat).toBe(false)
  })

  it('keeps draft on 409 and does not retry automatically', async () => {
    taoDeXuatKeHoach.mockRejectedValue({ httpStatus: 409, message: 'Plan version changed' })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    taoBanNhapHopLe(store)
    const draft = JSON.parse(JSON.stringify(store.banNhap))

    await store.taoDeXuatKeHoach()

    expect(taoDeXuatKeHoach).toHaveBeenCalledTimes(1)
    expect(store.banNhap).toEqual(draft)
    expect(store.loiTaoDeXuat.httpStatus).toBe(409)
    expect(store.thaoTacDangCho).toBeNull()
  })

  it('purges draft and rejects late member-A list response after switching to member B', async () => {
    const responseA = taoDeferred()
    taiDanhSachDeXuat.mockReturnValue(responseA.promise)
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    store.banNhap.title = 'A private draft'
    const requestA = store.taiDanhSachDeXuat(7)

    store.chonHoiVien(8)
    responseA.resolve({ data: [{ id: 7, title: 'A private proposal' }] })
    await requestA

    expect(store.memberId).toBe(8)
    expect(store.danhSachDeXuat).toEqual([])
    expect(store.banNhap.title).toBe('')
    expect(store.dangTaiDanhSachDeXuat).toBe(false)
  })

  it.each([403, 404])('clears all proposal state after assignment scope loss (%s)', async (httpStatus) => {
    taiDanhSachDeXuat.mockRejectedValue({ httpStatus, message: 'out of scope' })
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    store.banNhap.title = 'private draft'

    await store.taiDanhSachDeXuat(7)

    expect(store.memberId).toBeNull()
    expect(store.banNhap.title).toBe('')
    expect(store.danhSachDeXuat).toEqual([])
    expect(store.thaoTacDangCho).toBeNull()
  })

  it('cleans proposal state only when its store exists', () => {
    const store = useDeXuatStore()
    store.chonHoiVien(7)
    store.banNhap.title = 'private draft'
    expect(xoaDuLieuDeXuatPtNeuDaKhoiTao()).toBe(true)
    expect(store.banNhap.title).toBe('')
    expect(xoaDuLieuDeXuatPtNeuDaKhoiTao()).toBe(true)
  })
})
