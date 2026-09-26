import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  taiChiTietHoiVien,
  taiDanhSachHoiVienDuocPhanCong,
} from '../services/hoi_vien_pt.api.js'
import { taiChiSoCoThe, taiTienDoBaiTap, taiTienDoHoiVien } from '../services/tien_do.api.js'
import { taiKeHoachTapHoiVien } from '../services/ke_hoach_tap_pt.api.js'
import {
  taiChiTietLichSuTapHoiVien,
  taiLichSuTapHoiVien,
} from '../services/lich_su_tap_pt.api.js'
import { taiDanhSachGhiChu, themGhiChuHuanLuyen } from '../services/ghi_chu.api.js'
import { hoanTatBuoiHuanLuyen, taiLichSuBuoiHuanLuyen } from '../services/buoi_huan_luyen.api.js'
import { taoKhoaIdempotency } from '../utils/khoa_idempotency.js'
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
  return {
    ...actual,
    taiChiTietLichSuTapHoiVien: vi.fn(),
    taiLichSuTapHoiVien: vi.fn(),
  }
})

vi.mock('../services/ghi_chu.api.js', async () => {
  const actual = await vi.importActual('../services/ghi_chu.api.js')
  return { ...actual, taiDanhSachGhiChu: vi.fn(), themGhiChuHuanLuyen: vi.fn() }
})

vi.mock('../services/buoi_huan_luyen.api.js', async () => {
  const actual = await vi.importActual('../services/buoi_huan_luyen.api.js')
  return {
    ...actual,
    hoanTatBuoiHuanLuyen: vi.fn(),
    taiLichSuBuoiHuanLuyen: vi.fn(),
  }
})

vi.mock('../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(),
}))

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
    taoKhoaIdempotency.mockReturnValue('9c87e6ae-69e1-4a15-8330-9848fe48c7d2')
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
    expect(store.danhSachHoiVien).toEqual([{ id: 2, member: { id: 8 }, is_current: true }])
    expect(store.daTaiDanhSachHoiVien).toBe(true)
  })

  it('invalidates the stale list on member scope loss and lets a fresh forced GET own the result', async () => {
    const oldList = taoDeferred()
    const freshList = taoDeferred()
    taiDanhSachHoiVienDuocPhanCong
      .mockResolvedValueOnce({ data: [{ id: 1, member: { id: 7, name: 'Member A' }, is_current: true }] })
      .mockReturnValueOnce(oldList.promise)
      .mockReturnValueOnce(freshList.promise)
    taiChiTietHoiVien.mockRejectedValueOnce({ httpStatus: 404, code: 'MEMBER_NOT_FOUND' })
    const store = useHoiVienPtStore()

    await store.taiDanhSachHoiVienDuocPhanCong()
    store.chonHoiVien(7)
    const oldRequest = store.taiDanhSachHoiVienDuocPhanCong({ force: true })
    await store.taiChiTietHoiVien(7)

    expect(store.danhSachHoiVien).toEqual([])
    expect(store.daTaiDanhSachHoiVien).toBe(false)
    expect(store.dangTaiDanhSachHoiVien).toBe(false)

    const freshRequest = store.taiDanhSachHoiVienDuocPhanCong({ force: true })
    expect(taiDanhSachHoiVienDuocPhanCong).toHaveBeenCalledTimes(3)
    oldList.resolve({ data: [{ id: 1, member: { id: 7, name: 'Stale A' }, is_current: true }] })
    await oldRequest
    expect(store.danhSachHoiVien).toEqual([])
    expect(store.daTaiDanhSachHoiVien).toBe(false)

    freshList.resolve({ data: [{ id: 2, member: { id: 8, name: 'Member B' }, is_current: true }] })
    await freshRequest
    expect(store.danhSachHoiVien).toEqual([{ id: 2, member: { id: 8, name: 'Member B' }, is_current: true }])
    expect(store.daTaiDanhSachHoiVien).toBe(true)
    expect(store.hoiVienDaChonId).toBeNull()
  })

  it.each([403, 404])('clears a cached assigned list on list-level %s and allows an authorized retry', async (httpStatus) => {
    taiDanhSachHoiVienDuocPhanCong.mockResolvedValueOnce({
      data: [{ id: 1, member: { id: 7, name: 'Stale A' }, is_current: true }],
    }).mockRejectedValueOnce({ httpStatus, message: 'list unavailable' })
    const store = useHoiVienPtStore()
    await store.taiDanhSachHoiVienDuocPhanCong()

    const denied = await store.taiDanhSachHoiVienDuocPhanCong({ force: true })
    expect(denied.scopeLost).toBe(true)
    expect(store.danhSachHoiVien).toEqual([])
    expect(store.daTaiDanhSachHoiVien).toBe(false)
    expect(store.loiDanhSachHoiVien.httpStatus).toBe(httpStatus)

    taiDanhSachHoiVienDuocPhanCong.mockResolvedValueOnce({
      data: [{ id: 2, member: { id: 8, name: 'Member B' }, is_current: true }],
    })
    await store.taiDanhSachHoiVienDuocPhanCong({ force: true })
    expect(store.danhSachHoiVien.map((item) => item.member.id)).toEqual([8])
    expect(store.daTaiDanhSachHoiVien).toBe(true)
    expect(store.loiDanhSachHoiVien).toBeNull()
  })

  it('hides previous session detail while another session loads, fails, and retries', async () => {
    const lateA = taoDeferred()
    const selectedB = taoDeferred()
    let detailACalls = 0
    let detailBCalls = 0
    taiChiTietLichSuTapHoiVien.mockImplementation((_memberId, sessionId) => {
      if (sessionId === 4) {
        detailACalls += 1
        return detailACalls === 1 ? Promise.resolve({ data: { id: 4, name: 'Session A', exercises: [{ id: 40, name: 'Exercise A' }] } }) : lateA.promise
      }
      detailBCalls += 1
      return detailBCalls === 1
        ? selectedB.promise
        : Promise.resolve({ data: { id: 5, name: 'Session B', exercises: [{ id: 50, name: 'Exercise B' }] } })
    })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)

    await store.taiChiTietLichSuTapHoiVien(7, 4)
    expect(store.chiTietPhien.name).toBe('Session A')
    const lateARequest = store.taiChiTietLichSuTapHoiVien(7, 4)
    const requestB = store.taiChiTietLichSuTapHoiVien(7, 5)
    expect(store.chiTietPhien).toBeNull()
    expect(store.dangTaiChiTietPhien).toBe(true)

    lateA.resolve({ data: { id: 4, name: 'Late Session A', exercises: [{ id: 41, name: 'Late Exercise A' }] } })
    await lateARequest
    expect(store.chiTietPhien).toBeNull()
    expect(store.dangTaiChiTietPhien).toBe(true)

    selectedB.reject({ httpStatus: 503, message: 'Session B unavailable' })
    await requestB
    expect(store.chiTietPhien).toBeNull()
    expect(store.loiChiTietPhien.httpStatus).toBe(503)
    expect(store.dangTaiChiTietPhien).toBe(false)

    await store.taiChiTietLichSuTapHoiVien(7, 5)
    expect(store.chiTietPhien).toMatchObject({ id: 5, name: 'Session B' })
    expect(store.chiTietPhien.exercises[0].name).toBe('Exercise B')
    expect(store.loiChiTietPhien).toBeNull()
    expect(taiChiTietLichSuTapHoiVien).toHaveBeenLastCalledWith(7, 5)
  })

  it('clears visible session detail when detail input is invalid', async () => {
    const store = useHoiVienPtStore()
    store.chiTietPhien = { id: 4, name: 'Old session' }

    await store.taiChiTietLichSuTapHoiVien(7, 0)

    expect(store.chiTietPhien).toBeNull()
    expect(store.dangTaiChiTietPhien).toBe(false)
    expect(store.loiChiTietPhien.httpStatus).toBe(404)
    expect(taiChiTietLichSuTapHoiVien).not.toHaveBeenCalled()
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

  it('retries unknown direct completion only after refetch with the same assignment, notes and key', async () => {
    const pending = taoDeferred()
    const history = { history_id: 91, assignment_id: 107, member_id: 7, status: 'HOAN_THANH' }
    taiLichSuBuoiHuanLuyen.mockResolvedValue({ data: [history] })
    hoanTatBuoiHuanLuyen
      .mockReturnValueOnce(pending.promise)
      .mockResolvedValueOnce({ data: { ...history, replayed: true } })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 107, is_current: true } }

    const completion = store.hoanTatBuoiHuanLuyen(7, 107, 'Ghi chú gốc')
    expect(store.dangHoanTatBuoiHuanLuyen).toBe(true)
    expect(Object.isFrozen(store.thaoTacBuoiHuanLuyenDangCho)).toBe(true)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1)
    await expect(store.hoanTatBuoiHuanLuyen(7, 107, 'Ghi chú gốc')).resolves.toBeNull()
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1)

    pending.reject({ isNetworkError: true, message: 'timeout' })
    await completion
    expect(taiLichSuBuoiHuanLuyen).toHaveBeenCalledTimes(1)
    expect(store.thaoTacBuoiHuanLuyenDangCho).toMatchObject({ outcomeUnknown: true, assignmentId: 107 })

    await store.hoanTatBuoiHuanLuyen(7, 999, 'Ghi chú đã sửa', { retry: true })

    expect(taiLichSuBuoiHuanLuyen).toHaveBeenCalledTimes(2)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(2)
    expect(hoanTatBuoiHuanLuyen.mock.calls[0]).toEqual([
      107, 'Ghi chú gốc', '9c87e6ae-69e1-4a15-8330-9848fe48c7d2',
    ])
    expect(hoanTatBuoiHuanLuyen.mock.calls[1]).toEqual(hoanTatBuoiHuanLuyen.mock.calls[0])
    expect(store.ketQuaBuoiHuanLuyen).toMatchObject({ history_id: 91, replayed: true })
    expect(store.lichSuBuoiHuanLuyen.filter((item) => item.history_id === 91)).toHaveLength(1)
    expect(store.thaoTacBuoiHuanLuyenDangCho).toBeNull()
  })

  it('does not commit a late direct completion after switching Member', async () => {
    const pending = taoDeferred()
    hoanTatBuoiHuanLuyen.mockReturnValue(pending.promise)
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 107, is_current: true } }
    const completion = store.hoanTatBuoiHuanLuyen(7, 107, 'Private Member A')

    store.chonHoiVien(8)
    pending.resolve({ data: { history_id: 91, member_id: 7, status: 'HOAN_THANH' } })
    await completion

    expect(store.hoiVienDaChonId).toBe(8)
    expect(store.lichSuBuoiHuanLuyen).toEqual([])
    expect(store.ketQuaBuoiHuanLuyen).toBeNull()
    expect(store.thaoTacBuoiHuanLuyenDangCho).toBeNull()
    expect(store.dangHoanTatBuoiHuanLuyen).toBe(false)
  })

  it('waits for successful history refetch before retrying an unknown direct completion', async () => {
    taiLichSuBuoiHuanLuyen
      .mockRejectedValueOnce({ httpStatus: 503, message: 'history unavailable' })
      .mockResolvedValueOnce({ data: [] })
    hoanTatBuoiHuanLuyen
      .mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
      .mockResolvedValueOnce({ data: { history_id: 92, member_id: 7, replayed: true } })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 107, is_current: true } }

    await store.hoanTatBuoiHuanLuyen(7, 107, 'Keep this note')
    expect(store.thaoTacBuoiHuanLuyenDangCho).toMatchObject({ outcomeUnknown: true })
    expect(store.loiBuoiHuanLuyen.httpStatus).toBe(503)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(1)

    await store.hoanTatBuoiHuanLuyen(7, 107, 'Keep this note', { retry: true })

    expect(taiLichSuBuoiHuanLuyen).toHaveBeenCalledTimes(2)
    expect(hoanTatBuoiHuanLuyen).toHaveBeenCalledTimes(2)
    expect(hoanTatBuoiHuanLuyen.mock.calls[1]).toEqual(hoanTatBuoiHuanLuyen.mock.calls[0])
    expect(store.ketQuaBuoiHuanLuyen).toMatchObject({ history_id: 92, replayed: true })
  })

  it('does not commit a late direct completion after auth cleanup', async () => {
    const pending = taoDeferred()
    hoanTatBuoiHuanLuyen.mockReturnValue(pending.promise)
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 107, is_current: true } }
    const completion = store.hoanTatBuoiHuanLuyen(7, 107, 'Private note')

    store.xoaDuLieu()
    pending.resolve({ data: { history_id: 93, member_id: 7, status: 'HOAN_THANH' } })
    await completion

    expect(store.hoiVienDaChonId).toBeNull()
    expect(store.lichSuBuoiHuanLuyen).toEqual([])
    expect(store.ketQuaBuoiHuanLuyen).toBeNull()
    expect(store.thaoTacBuoiHuanLuyenDangCho).toBeNull()
    expect(store.dangHoanTatBuoiHuanLuyen).toBe(false)
  })

  it.each([403, 404])('clears direct-session state after assignment scope loss (%s)', async (httpStatus) => {
    hoanTatBuoiHuanLuyen.mockRejectedValue({ httpStatus, message: 'assignment unavailable' })
    const store = useHoiVienPtStore()
    store.chonHoiVien(7)
    store.chiTietHoiVien = { member: { id: 7 }, assignment: { id: 107, is_current: true } }
    const proposalStore = (await import('./de_xuat.store.js')).useDeXuatStore()
    proposalStore.chonHoiVien(7)
    proposalStore.banNhap.title = 'Draft for member 7'

    await expect(store.hoanTatBuoiHuanLuyen(7, 107)).resolves.toMatchObject({ scopeLost: true })

    expect(store.hoiVienDaChonId).toBeNull()
    expect(store.thaoTacBuoiHuanLuyenDangCho).toBeNull()
    expect(store.lichSuBuoiHuanLuyen).toEqual([])
    expect(proposalStore.memberId).toBeNull()
    expect(proposalStore.banNhap.title).toBe('')
  })
})
