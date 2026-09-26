import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  taiChiTietHoiVien,
  taiDanhSachHoiVienDuocPhanCong,
} from '../services/hoi_vien_pt.api.js'
import { taiChiSoCoThe, taiTienDoBaiTap, taiTienDoHoiVien } from '../services/tien_do.api.js'
import { taiKeHoachTapHoiVien } from '../services/ke_hoach_tap_pt.api.js'
import { taiLichSuTapHoiVien } from '../services/lich_su_tap_pt.api.js'
import { taiDanhSachGhiChu, themGhiChuHuanLuyen } from '../services/ghi_chu.api.js'
import {
  useHoiVienPtStore,
  xoaDuLieuHoiVienPtNeuDaKhoiTao,
} from './hoi_vien_pt.store.js'

vi.mock('../services/hoi_vien_pt.api.js', async () => {
  const actual = await vi.importActual('../services/hoi_vien_pt.api.js')
  return {
    ...actual,
    taiChiTietHoiVien: vi.fn(),
    taiDanhSachHoiVienDuocPhanCong: vi.fn(),
  }
})

vi.mock('../services/tien_do.api.js', async () => {
  const actual = await vi.importActual('../services/tien_do.api.js')
  return {
    ...actual,
    taiChiSoCoThe: vi.fn(),
    taiTienDoBaiTap: vi.fn(),
    taiTienDoHoiVien: vi.fn(),
  }
})

vi.mock('../services/ke_hoach_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../services/ke_hoach_tap_pt.api.js')
  return { ...actual, taiKeHoachTapHoiVien: vi.fn() }
})

vi.mock('../services/lich_su_tap_pt.api.js', async () => {
  const actual = await vi.importActual('../services/lich_su_tap_pt.api.js')
  return { ...actual, taiLichSuTapHoiVien: vi.fn() }
})

vi.mock('../services/ghi_chu.api.js', async () => {
  const actual = await vi.importActual('../services/ghi_chu.api.js')
  return { ...actual, taiDanhSachGhiChu: vi.fn(), themGhiChuHuanLuyen: vi.fn() }
})

function taoChiTiet(id, name = `Member ${id}`) {
  return { data: { member: { id, name }, assignment: { id: id + 100, is_current: true } } }
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

describe('hoi_vien_pt.store FE5', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('commits only current assigned list and supports idempotent cleanup', async () => {
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValue({
      data: [{ id: 1, member: { id: 7, name: 'Member' }, is_current: true }],
    })
    const store = useHoiVienPtStore()
    await store.taiDanhSachHoiVienDuocPhanCong()
    expect(store.danhSachHoiVien).toHaveLength(1)
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 } }
    expect(xoaDuLieuHoiVienPtNeuDaKhoiTao()).toBe(true)
    expect(store.hoiVienDaChonId).toBeNull()
    expect(store.chiTietHoiVien).toBeNull()
    expect(xoaDuLieuHoiVienPtNeuDaKhoiTao()).toBe(true)
  })

  it('rejects late detail response A after switching to member B', async () => {
    const a = taoDeferred()
    const b = taoDeferred()
    taiChiTietHoiVien.mockImplementation((id) => id === 7 ? a.promise : b.promise)
    const store = useHoiVienPtStore()
    const requestA = store.taiChiTietHoiVien('7')
    const requestB = store.taiChiTietHoiVien('8')
    a.resolve(taoChiTiet(7))
    await requestA
    expect(store.chiTietHoiVien).toBeNull()
    b.resolve(taoChiTiet(8))
    await requestB
    expect(store.hoiVienDaChonId).toBe(8)
    expect(store.chiTietHoiVien.member.id).toBe(8)
  })

  it('purges selected scope on 403/404 and never leaves sensitive detail cache', async () => {
    taiChiTietHoiVien.mockRejectedValue({ httpStatus: 404, code: 'MEMBER_NOT_FOUND', message: 'hidden' })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietTheoHoiVien['7'] = { member: { id: 7 } }
    await store.taiChiTietHoiVien(7)
    expect(store.hoiVienDaChonId).toBeNull()
    expect(store.chiTietHoiVien).toBeNull()
    expect(store.chiTietTheoHoiVien['7']).toBeUndefined()
    expect(store.loiChiTietHoiVien.httpStatus).toBe(404)
  })

  it('assignment refresh purges a selected member that is no longer current', async () => {
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValue({
      data: [{ id: 2, member: { id: 8 }, is_current: true }],
    })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    await store.taiDanhSachHoiVienDuocPhanCong()
    expect(store.hoiVienDaChonId).toBeNull()
  })

  it('reconciles note timeout without blind retry and keeps mutation result unknown', async () => {
    themGhiChuHuanLuyen.mockRejectedValue({ httpStatus: 503, message: 'timeout' })
    taiDanhSachGhiChu.mockResolvedValue({ data: [] })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    await store.themGhiChuHuanLuyen(7, { content: 'keep draft' })
    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
    expect(taiDanhSachGhiChu).toHaveBeenCalledTimes(1)
    expect(store.loiThemGhiChu.outcomeUnknown).toBe(true)
  })

  it('does not commit a response after global cleanup invalidates request generation', async () => {
    const pending = taoDeferred()
    taiTienDoHoiVien.mockReturnValue(pending.promise)
    const store = useHoiVienPtStore()
    const request = store.taiTienDoHoiVien(7)
    store.xoaDuLieu()
    pending.resolve({ data: { completed_sessions_count: 99 } })
    await request
    expect(store.tienDo.overview).toBeNull()
  })

  it.each(['overview-first', 'body-first'])('keeps overview and body request states independent (%s)', async (completionOrder) => {
    const overview = taoDeferred()
    const body = taoDeferred()
    taiTienDoHoiVien.mockReturnValue(overview.promise)
    taiChiSoCoThe.mockReturnValue(body.promise)
    const store = useHoiVienPtStore()
    const requestOverview = store.taiTienDoHoiVien(7)
    const requestBody = store.taiChiSoCoThe(7)

    expect(store.dangTaiTongQuan).toBe(true)
    expect(store.dangTaiChiSoCoThe).toBe(true)

    if (completionOrder === 'overview-first') {
      overview.resolve({ data: { completed_sessions_count: 2 } })
      await requestOverview
      expect(store.dangTaiTongQuan).toBe(false)
      expect(store.dangTaiChiSoCoThe).toBe(true)
      body.reject({ httpStatus: 503, message: 'body unavailable' })
      await requestBody
      expect(store.loiTongQuan).toBeNull()
      expect(store.loiChiSoCoThe.httpStatus).toBe(503)
    } else {
      body.resolve({ data: { items: [] } })
      await requestBody
      expect(store.dangTaiChiSoCoThe).toBe(false)
      expect(store.dangTaiTongQuan).toBe(true)
      overview.reject({ httpStatus: 503, message: 'overview unavailable' })
      await requestOverview
      expect(store.loiChiSoCoThe).toBeNull()
      expect(store.loiTongQuan.httpStatus).toBe(503)
    }

    expect(store.dangTaiTongQuan).toBe(false)
    expect(store.dangTaiChiSoCoThe).toBe(false)
  })

  it('keeps official Plan errors separate when progress queries succeed', async () => {
    taiTienDoHoiVien.mockResolvedValue({ data: { completed_sessions_count: 3 } })
    taiChiSoCoThe.mockResolvedValue({ data: { items: [] } })
    taiKeHoachTapHoiVien.mockRejectedValue({ httpStatus: 503, message: 'plan unavailable' })
    const store = useHoiVienPtStore()

    await Promise.all([
      store.taiTienDoHoiVien(7),
      store.taiChiSoCoThe(7),
      store.taiKeHoachTapHoiVien(7),
    ])

    expect(store.tienDo.overview.completed_sessions_count).toBe(3)
    expect(store.tienDo.body.items).toEqual([])
    expect(store.loiTongQuan).toBeNull()
    expect(store.loiChiSoCoThe).toBeNull()
    expect(store.loiKeHoachTap.httpStatus).toBe(503)
  })

  it('does not commit a late exercise-trend result after cleanup', async () => {
    const pending = taoDeferred()
    taiTienDoBaiTap.mockReturnValue(pending.promise)
    const store = useHoiVienPtStore()
    const request = store.taiTienDoBaiTap(7, 12)

    expect(store.dangTaiTienDoBaiTap).toBe(true)
    store.xoaDuLieu()
    pending.resolve({ data: { exercise: { id: 12 }, items: [] } })
    await request

    expect(store.tienDo.exercises[12]).toBeUndefined()
    expect(store.dangTaiTienDoBaiTap).toBe(false)
    expect(store.loiTienDoBaiTap).toBeNull()
  })

  it.each([
    ['switching to member B', 'resolve'],
    ['switching to member B', 'reject'],
    ['unmounting the member page', 'resolve'],
    ['unmounting the member page', 'reject'],
    ['global/auth cleanup', 'resolve'],
    ['global/auth cleanup', 'reject'],
  ])('does not restore note mutation state after %s while reconciliation %s', async (cleanup, reconciliationResult) => {
    const post = taoDeferred()
    const reconciliation = taoDeferred()
    themGhiChuHuanLuyen.mockReturnValue(post.promise)
    taiDanhSachGhiChu.mockReturnValue(reconciliation.promise)
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    const request = store.themGhiChuHuanLuyen(7, { content: 'Draft A' })

    post.reject({ httpStatus: 503, message: 'timeout' })
    await vi.waitFor(() => expect(taiDanhSachGhiChu).toHaveBeenCalledTimes(1))

    if (cleanup === 'switching to member B') {
      store.chonHoiVien(8)
    } else if (cleanup === 'unmounting the member page') {
      store.xoaHoiVienDangChon()
    } else {
      store.xoaDuLieu()
    }

    if (reconciliationResult === 'resolve') {
      reconciliation.resolve({ data: [{ id: 70, content: 'Note A' }] })
    } else {
      reconciliation.reject({ httpStatus: 503, message: 'reconcile failed' })
    }
    await request

    expect(themGhiChuHuanLuyen).toHaveBeenCalledTimes(1)
    expect(taiDanhSachGhiChu).toHaveBeenCalledTimes(1)
    expect(store.danhSachGhiChu).toEqual([])
    expect(store.loiThemGhiChu).toBeNull()
    expect(store.ketQuaThemGhiChu).toBeNull()
    expect(store.dangThemGhiChu).toBe(false)
    expect(store.dangTaiGhiChu).toBe(false)
    expect(store.hoiVienDaChonId).toBe(cleanup === 'switching to member B' ? 8 : null)
  })

  it('uses only PT services for plan/history state', async () => {
    taiKeHoachTapHoiVien.mockResolvedValue({ data: { plan: { id: 4 }, future_schedule: [] } })
    taiLichSuTapHoiVien.mockResolvedValue({ data: [{ id: 3, status: 'HOAN_THANH' }] })
    const store = useHoiVienPtStore()
    await store.taiKeHoachTapHoiVien(7)
    await store.taiLichSuTapHoiVien(7)
    expect(store.keHoachTap.plan.id).toBe(4)
    expect(store.lichSuTap.items[0].id).toBe(3)
  })
})
