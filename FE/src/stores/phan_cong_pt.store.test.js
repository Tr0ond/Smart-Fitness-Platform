import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  ketThucPhanCong,
  phanCongLai,
  taiChiTietPhanCong,
  taiDanhSachPhanCong,
  taoPhanCong,
} from '../services/phan_cong_pt.api.js'
import { usePhanCongPtStore } from './phan_cong_pt.store.js'

vi.mock('../services/phan_cong_pt.api.js', async () => {
  const actual = await vi.importActual('../services/phan_cong_pt.api.js')
  return {
    ...actual,
    ketThucPhanCong: vi.fn(),
    phanCongLai: vi.fn(),
    taiChiTietPhanCong: vi.fn(),
    taiDanhSachPhanCong: vi.fn(),
    taoPhanCong: vi.fn(),
  }
})

const ASSIGNMENT = {
  id: 1,
  member: { id: 7, code: 'HV1', name: 'Member' },
  trainer: { id: 11, code: 'PT1', name: 'Trainer', status: 'HOAT_DONG' },
  start_at: '2026-09-07T10:00:00.000Z',
  end_at: null,
  reason: null,
  is_current: true,
  created_at: '2026-09-07T09:00:00.000Z',
  updated_at: '2026-09-07T09:00:00.000Z',
}

function phanHoiDanhSach(items = [], currentPage = 1, lastPage = 1) {
  return {
    data: {
      items,
      pagination: {
        current_page: currentPage,
        per_page: 100,
        total: items.length,
        last_page: lastPage,
      },
    },
  }
}

function taoAssignment(overrides = {}) {
  return {
    ...ASSIGNMENT,
    ...overrides,
    member: { ...ASSIGNMENT.member, ...(overrides.member ?? {}) },
    trainer: { ...ASSIGNMENT.trainer, ...(overrides.trainer ?? {}) },
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

function expectMutationDaReset(store) {
  expect(store.ketQuaMutation).toBeNull()
  expect(store.loiMutation).toBeNull()
  expect(store.dangMutation).toBe(false)
}

describe('phan_cong_pt.store FE2-T06/T07', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('validate list va chan response stale sau cleanup', async () => {
    taiDanhSachPhanCong.mockResolvedValue({
      data: { items: [ASSIGNMENT], pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 } },
    })
    const store = usePhanCongPtStore()
    await store.taiDanhSachPhanCong()
    expect(store.danhSach).toEqual([ASSIGNMENT])
    expect(store.daTaiLanDau).toBe(true)
  })

  it('end unknown outcome reconciles detail without second mutation', async () => {
    ketThucPhanCong.mockRejectedValue({ isNetworkError: true, message: 'timeout' })
    taiChiTietPhanCong.mockResolvedValue({ data: { ...ASSIGNMENT, end_at: '2026-09-07T11:00:00.000Z', is_current: false } })
    const store = usePhanCongPtStore()
    const result = await store.ketThucPhanCong(1, { reason: 'done' })
    expect(result.end_at).toBe('2026-09-07T11:00:00.000Z')
    expect(ketThucPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation).toBeNull()
  })

  it('chi commit detail khi response dung contract', async () => {
    taiChiTietPhanCong.mockResolvedValue({ data: ASSIGNMENT })
    const store = usePhanCongPtStore()
    await store.taiChiTietPhanCong(1)
    expect(store.chiTiet).toEqual(ASSIGNMENT)
    expect(store.chiTiet.trainer.id).toBe(11)
  })

  it('stale mutation response khong ghi de state moi', async () => {
    taiDanhSachPhanCong.mockResolvedValueOnce(phanHoiDanhSach())
    let resolveCreate
    taoPhanCong.mockReturnValueOnce(new Promise((resolve) => { resolveCreate = resolve }))
    const store = usePhanCongPtStore()
    const request = store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })
    store.xoaDuLieu()
    resolveCreate({ data: ASSIGNMENT })
    await request
    expect(store.ketQuaMutation).toBeNull()
  })

  it('reconciliation khong nhan row historical da co khi request bo start/end', async () => {
    const old = taoAssignment({ id: 30, end_at: '2026-09-06T10:00:00.000Z', is_current: false })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach([old]))
      .mockResolvedValueOnce(phanHoiDanhSach([old]))
    taoPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    const store = usePhanCongPtStore()

    const result = await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    expect(result).toBeNull()
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
  })

  it('reconciliation nhan duy nhat row moi dang mo khi bo start/end', async () => {
    const created = taoAssignment({ id: 31 })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
    taiChiTietPhanCong.mockResolvedValueOnce({ data: created })
    taoPhanCong.mockRejectedValueOnce({ httpStatus: 503, message: 'temporary' })
    const store = usePhanCongPtStore()

    const result = await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    expect(result).toEqual(created)
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation).toBeNull()
  })

  it('reconciliation nhan duy nhat row moi voi interval explicit', async () => {
    const created = taoAssignment({
      id: 32,
      start_at: '2026-09-08T10:00:00.000Z',
      end_at: '2026-09-08T11:00:00.000Z',
    })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
    taiChiTietPhanCong.mockResolvedValueOnce({ data: created })
    taoPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    const store = usePhanCongPtStore()

    const result = await store.taoMoiPhanCong({
      member_id: 7,
      trainer_id: 11,
      start_at: '2026-09-08T10:00:00.000Z',
      end_at: '2026-09-08T11:00:00.000Z',
    })

    expect(result).toEqual(created)
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
  })

  it('reconciliation fail closed khi request bo end nhung candidate co end', async () => {
    const created = taoAssignment({ id: 33, end_at: '2026-09-08T11:00:00.000Z' })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
    taoPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })).toBeNull()
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
  })

  it('reconciliation fail closed khi khong co exact candidate moi', async () => {
    const created = taoAssignment({ id: 34, start_at: '2026-09-08T10:00:00.000Z' })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
    taoPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({
      member_id: 7,
      trainer_id: 11,
      start_at: '2026-09-08T11:00:00.000Z',
    })).toBeNull()
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
  })

  it('reconciliation fail closed khi co hai candidate moi cung interval', async () => {
    const first = taoAssignment({ id: 35 })
    const second = taoAssignment({ id: 36 })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([first, second]))
    taoPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })).toBeNull()
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
  })

  it('baseline multi-page ghi nhan ID moi tren moi trang truoc POST', async () => {
    const firstPage = taoAssignment({ id: 37, end_at: '2026-09-06T10:00:00.000Z', is_current: false })
    const secondPage = taoAssignment({ id: 38, end_at: '2026-09-06T11:00:00.000Z', is_current: false })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach([firstPage], 1, 2))
      .mockResolvedValueOnce(phanHoiDanhSach([secondPage], 2, 2))
      .mockResolvedValueOnce(phanHoiDanhSach([firstPage], 1, 2))
      .mockResolvedValueOnce(phanHoiDanhSach([secondPage], 2, 2))
    taoPhanCong.mockImplementationOnce(() => {
      expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(2)
      return Promise.reject({ isNetworkError: true, message: 'timeout' })
    })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })).toBeNull()
    expect(taiDanhSachPhanCong.mock.calls[0][0]).toMatchObject({ page: 1, per_page: 100 })
    expect(taiDanhSachPhanCong.mock.calls[1][0]).toMatchObject({ page: 2, per_page: 100 })
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
  })

  it('cleanup trong luc baseline pending chan POST', async () => {
    let resolveBaseline
    taiDanhSachPhanCong.mockReturnValueOnce(new Promise((resolve) => { resolveBaseline = resolve }))
    const store = usePhanCongPtStore()
    const request = store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    store.xoaDuLieu()
    resolveBaseline(phanHoiDanhSach())
    await request

    expect(taoPhanCong).not.toHaveBeenCalled()
    expect(store.dangMutation).toBe(false)
  })

  it('baseline loi hien thi va khong gui POST', async () => {
    taiDanhSachPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'baseline unavailable' })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })).toBeNull()

    expect(taoPhanCong).not.toHaveBeenCalled()
    expect(store.loiMutation?.message).toBe('baseline unavailable')
    expect(store.loiMutation?.outcomeUnknown).toBeUndefined()
    expect(store.dangMutation).toBe(false)
  })

  it('malformed 2xx doi soat duy nhat va commit assignment authoritative', async () => {
    const created = taoAssignment({ id: 39 })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
      .mockResolvedValueOnce(phanHoiDanhSach([created]))
    taiChiTietPhanCong.mockResolvedValueOnce({ data: created })
    taoPhanCong.mockResolvedValueOnce({ data: { id: created.id } })
    const store = usePhanCongPtStore()

    const result = await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    expect(result).toEqual(created)
    expect(store.ketQuaMutation).toEqual(created)
    expect(store.loiMutation).toBeNull()
    expect(store.loiMutation?.code).not.toBe('ASSIGNMENT_DETAIL_RESPONSE_INVALID')
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(3)
  })

  it.each([
    ['zero candidate', () => phanHoiDanhSach()],
    ['multiple candidates', () => phanHoiDanhSach([taoAssignment({ id: 40 }), taoAssignment({ id: 41 })])],
    ['unreadable candidate inventory', () => ({ data: { items: [] } })],
  ])('malformed 2xx %s fail closed after one reconciliation', async (_label, taoResponse) => {
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockImplementationOnce(taoResponse)
    taoPhanCong.mockResolvedValueOnce({ data: { id: 99 } })
    const store = usePhanCongPtStore()

    expect(await store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })).toBeNull()

    expect(store.ketQuaMutation).toBeNull()
    expect(store.loiMutation?.outcomeUnknown).toBe(true)
    expect(store.loiMutation?.code).toBe('ASSIGNMENT_OUTCOME_UNKNOWN')
    expect(taoPhanCong).toHaveBeenCalledTimes(1)
    expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(2)
  })

  it('cleanup khi POST create pending khong doi soat hoac hoi sinh state', async () => {
    const pendingCreate = taoDeferred()
    taiDanhSachPhanCong.mockResolvedValueOnce(phanHoiDanhSach())
    taoPhanCong.mockReturnValueOnce(pendingCreate.promise)
    const store = usePhanCongPtStore()
    const request = store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    await vi.waitFor(() => expect(taoPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingCreate.resolve({ data: { id: 99 } })
    await request

    expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1)
    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list create thanh cong chan detail va commit', async () => {
    const pendingList = taoDeferred()
    const created = taoAssignment({ id: 42 })
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockReturnValueOnce(pendingList.promise)
    taoPhanCong.mockResolvedValueOnce({ data: created })
    const store = usePhanCongPtStore()
    const request = store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(2))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach([created]))
    await request

    expect(taiChiTietPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list create 409 chan conflict state', async () => {
    const pendingList = taoDeferred()
    taiDanhSachPhanCong
      .mockResolvedValueOnce(phanHoiDanhSach())
      .mockReturnValueOnce(pendingList.promise)
    taoPhanCong.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    const store = usePhanCongPtStore()
    const request = store.taoMoiPhanCong({ member_id: 7, trainer_id: 11 })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(2))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach())
    await request

    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list end thanh cong chan detail va commit', async () => {
    const pendingList = taoDeferred()
    const ended = taoAssignment({ end_at: '2026-09-08T10:00:00.000Z', is_current: false })
    ketThucPhanCong.mockResolvedValueOnce({ data: ended })
    taiDanhSachPhanCong.mockReturnValueOnce(pendingList.promise)
    const store = usePhanCongPtStore()
    const request = store.ketThucPhanCong(1, { reason: 'done' })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach([ended]))
    await request

    expect(taiChiTietPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup trong reconciliation detail end tam thoi chan state', async () => {
    const pendingDetail = taoDeferred()
    ketThucPhanCong.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    taiChiTietPhanCong.mockReturnValueOnce(pendingDetail.promise)
    const store = usePhanCongPtStore()
    const request = store.ketThucPhanCong(1, { reason: 'done' })

    await vi.waitFor(() => expect(taiChiTietPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingDetail.resolve({ data: taoAssignment({ end_at: '2026-09-08T10:00:00.000Z', is_current: false }) })
    await request

    expect(taiDanhSachPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list end 409 chan conflict state', async () => {
    const pendingList = taoDeferred()
    ketThucPhanCong.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    taiDanhSachPhanCong.mockReturnValueOnce(pendingList.promise)
    const store = usePhanCongPtStore()
    const request = store.ketThucPhanCong(1, { reason: 'done' })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach())
    await request

    expect(taiChiTietPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list reassign thanh cong chan detail va commit', async () => {
    const pendingList = taoDeferred()
    const reassigned = taoAssignment({ id: 43, trainer: { id: 12, code: 'PT2', name: 'Trainer 2', status: 'HOAT_DONG' } })
    phanCongLai.mockResolvedValueOnce({ data: reassigned })
    taiDanhSachPhanCong.mockReturnValueOnce(pendingList.promise)
    const store = usePhanCongPtStore()
    const request = store.phanCongLai(1, { trainer_id: 12, start_at: reassigned.start_at })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach([reassigned]))
    await request

    expect(taiChiTietPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup khi detail cu reassign tam thoi pending chan list tiep theo', async () => {
    const pendingDetail = taoDeferred()
    phanCongLai.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    taiChiTietPhanCong.mockReturnValueOnce(pendingDetail.promise)
    const store = usePhanCongPtStore()
    const request = store.phanCongLai(1, { trainer_id: 12, start_at: ASSIGNMENT.start_at })

    await vi.waitFor(() => expect(taiChiTietPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingDetail.resolve({ data: ASSIGNMENT })
    await request

    expect(taiDanhSachPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })

  it('cleanup sau detail cu trong luc list reassign tam thoi pending chan commit', async () => {
    const pendingList = taoDeferred()
    const old = taoAssignment({ end_at: '2026-09-08T09:00:00.000Z', is_current: false })
    const reassigned = taoAssignment({ id: 44, trainer: { id: 12, code: 'PT2', name: 'Trainer 2', status: 'HOAT_DONG' }, start_at: old.end_at })
    phanCongLai.mockRejectedValueOnce({ isNetworkError: true, message: 'timeout' })
    taiChiTietPhanCong.mockResolvedValueOnce({ data: old })
    taiDanhSachPhanCong.mockReturnValueOnce(pendingList.promise)
    const store = usePhanCongPtStore()
    const request = store.phanCongLai(1, { trainer_id: 12, start_at: old.end_at })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach([reassigned]))
    await request

    expectMutationDaReset(store)
  })

  it('cleanup trong refresh list reassign 409 chan conflict state', async () => {
    const pendingList = taoDeferred()
    phanCongLai.mockRejectedValueOnce({ httpStatus: 409, message: 'conflict' })
    taiDanhSachPhanCong.mockReturnValueOnce(pendingList.promise)
    const store = usePhanCongPtStore()
    const request = store.phanCongLai(1, { trainer_id: 12, start_at: ASSIGNMENT.start_at })

    await vi.waitFor(() => expect(taiDanhSachPhanCong).toHaveBeenCalledTimes(1))
    store.xoaDuLieu()
    pendingList.resolve(phanHoiDanhSach())
    await request

    expect(taiChiTietPhanCong).not.toHaveBeenCalled()
    expectMutationDaReset(store)
  })
})
