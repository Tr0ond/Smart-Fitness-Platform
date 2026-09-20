import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const api = vi.hoisted(() => ({
  taiDanhSachThanhToan: vi.fn(),
  taiChiTietThanhToan: vi.fn(),
  taiDanhSachCanDoiSoat: vi.fn(),
  taiSuKienThanhToan: vi.fn(),
  laIdThanhToanHopLe: (id) => (typeof id === 'number'
    ? Number.isSafeInteger(id) && id > 0
    : typeof id === 'string' && /^[1-9]\d*$/.test(id.trim()) && Number.isSafeInteger(Number(id.trim()))),
}))

vi.mock('../services/thanh_toan.api.js', () => api)

import {
  useThanhToanStore,
  xoaDuLieuThanhToanNeuDaKhoiTao,
} from './thanh_toan.store.js'

const THANH_TOAN = {
  payment_id: 7,
  status: 'THANH_CONG',
  order: { id: 8, code: 'ORD-07', status: 'DA_THANH_TOAN' },
  member: { id: 9, code: 'HV-09', email: 'member@example.com' },
  membership_term: { id: 10, status: 'CHO_KICH_HOAT' },
  events: [],
}

const SU_KIEN_CHUA_LIEN_KET = {
  event_id: 71,
  payment_id: null,
  processing_status: 'CAN_DOI_SOAT',
}

const SENTINELS = [
  'RAW-PAYMENT-SENTINEL',
  'RAW-ORDER-SENTINEL',
  'RAW-MEMBER-SENTINEL',
  'RAW-TERM-SENTINEL',
  'RAW-EVENT-SENTINEL',
  'SIGNATURE-SENTINEL',
  'HASH-SENTINEL',
  'IDEMPOTENCY-SENTINEL',
  'SECRET-SENTINEL',
]

const THANH_TOAN_NHIEM = {
  ...THANH_TOAN,
  raw_payload: SENTINELS[0],
  signature: SENTINELS[5],
  hash: SENTINELS[6],
  idempotency_key: SENTINELS[7],
  secret: SENTINELS[8],
  order: {
    ...THANH_TOAN.order,
    raw_payload: SENTINELS[1],
    signature: SENTINELS[5],
    ma_bam_noi_dung: SENTINELS[6],
    khoa_chong_lap: SENTINELS[7],
    secret: SENTINELS[8],
  },
  member: {
    ...THANH_TOAN.member,
    raw_payload: SENTINELS[2],
    signature: SENTINELS[5],
    hash: SENTINELS[6],
    idempotency_key: SENTINELS[7],
    secret: SENTINELS[8],
  },
  membership_term: {
    ...THANH_TOAN.membership_term,
    raw_payload: SENTINELS[3],
    signature: SENTINELS[5],
    hash: SENTINELS[6],
    idempotency_key: SENTINELS[7],
    secret: SENTINELS[8],
  },
  events: [{
    event_id: 71,
    payment_id: 7,
    processing_status: 'CAN_DOI_SOAT',
    raw_payload: SENTINELS[4],
    signature: SENTINELS[5],
    hash: SENTINELS[6],
    idempotency_key: SENTINELS[7],
    secret: SENTINELS[8],
  }],
}

const SU_KIEN_NHIEM = {
  ...SU_KIEN_CHUA_LIEN_KET,
  raw_payload: SENTINELS[4],
  signature: SENTINELS[5],
  hash: SENTINELS[6],
  idempotency_key: SENTINELS[7],
  secret: SENTINELS[8],
}

function phanHoi(items, pagination = {}) {
  return {
    data: {
      items,
      pagination: {
        current_page: pagination.current_page ?? 1,
        per_page: pagination.per_page ?? 20,
        total: pagination.total ?? items.length,
        last_page: pagination.last_page ?? 1,
      },
    },
  }
}

function deferred() {
  let resolve
  let reject
  const promise = new Promise((resolvePromise, rejectPromise) => {
    resolve = resolvePromise
    reject = rejectPromise
  })
  return { promise, resolve, reject }
}

function assertKhongCoSentinel(...giaTri) {
  const chuoi = JSON.stringify(giaTri)
  SENTINELS.forEach((sentinel) => {
    expect(chuoi).not.toContain(sentinel)
  })
}

beforeEach(() => {
  setActivePinia(createPinia())
  vi.clearAllMocks()
})

describe('thanh_toan.store FE4-ALL', () => {
  it('co state doc lap cho Payment, detail va hai hang doi', () => {
    const store = useThanhToanStore()

    expect(store.danhSachThanhToan).toEqual([])
    expect(store.danhSachCanDoiSoat).toEqual([])
    expect(store.danhSachSuKienThanhToan).toEqual([])
    expect(store.chiTietThanhToan).toBeNull()
    expect(store.boLocThanhToan).toMatchObject({ order_code: '', per_page: 20 })
    expect(store.boLocCanDoiSoat).toMatchObject({ payment_status: '', per_page: 20 })
    expect(store.boLocSuKienThanhToan).toMatchObject({ processing_status: '', per_page: 20 })
    expect(store.danhSachThanhToanCanDoiSoat).toBe(store.danhSachCanDoiSoat)
    expect(store.danhSachSuKien).toBe(store.danhSachSuKienThanhToan)
  })

  it('tai Payment list server pagination va giu filter da ap dung', async () => {
    api.taiDanhSachThanhToan.mockResolvedValue(phanHoi([THANH_TOAN], {
      total: 21,
      last_page: 2,
    }))
    const store = useThanhToanStore()

    await expect(store.apDungBoLocThanhToan({ order_code: ' ORD-07 ', per_page: 20 }))
      .resolves.toMatchObject({ items: [THANH_TOAN] })

    expect(api.taiDanhSachThanhToan).toHaveBeenCalledWith(expect.objectContaining({
      order_code: 'ORD-07',
      per_page: 20,
      page: 1,
    }))
    expect(store.danhSachThanhToan).toEqual([THANH_TOAN])
    expect(store.phanTrangThanhToan).toMatchObject({ current_page: 1, total: 21, last_page: 2 })
    expect(store.daTaiThanhToanLanDau).toBe(true)
  })

  it('project deep Safe DTO cho ca bon read action va loai bo sensitive fields', async () => {
    api.taiDanhSachThanhToan.mockResolvedValue(phanHoi([THANH_TOAN_NHIEM]))
    api.taiChiTietThanhToan.mockResolvedValue({ data: THANH_TOAN_NHIEM })
    api.taiDanhSachCanDoiSoat.mockResolvedValue(phanHoi([THANH_TOAN_NHIEM]))
    api.taiSuKienThanhToan.mockResolvedValue(phanHoi([SU_KIEN_NHIEM]))
    const store = useThanhToanStore()

    const danhSach = await store.taiDanhSachThanhToan()
    const chiTiet = await store.taiChiTietThanhToan(7)
    const canDoiSoat = await store.taiDanhSachCanDoiSoat()
    const suKien = await store.taiSuKienThanhToan()

    expect(danhSach.items[0]).toMatchObject({
      payment_id: 7,
      order: { code: 'ORD-07' },
      member: { code: 'HV-09' },
      membership_term: { status: 'CHO_KICH_HOAT' },
      events: [{ event_id: 71, processing_status: 'CAN_DOI_SOAT' }],
    })
    expect(chiTiet).toMatchObject({
      payment_id: 7,
      order: { code: 'ORD-07' },
      member: { code: 'HV-09' },
      membership_term: { status: 'CHO_KICH_HOAT' },
      events: [{ event_id: 71 }],
    })
    expect(canDoiSoat.items[0].payment_id).toBe(7)
    expect(suKien.items[0].payment_id).toBeNull()
    assertKhongCoSentinel(
      danhSach,
      chiTiet,
      canDoiSoat,
      suKien,
      store.danhSachThanhToan,
      store.chiTietThanhToan,
      store.danhSachCanDoiSoat,
      store.danhSachSuKienThanhToan,
    )
  })

  it('dat lai filter Payment ve gia tri mac dinh va trang 1', async () => {
    api.taiDanhSachThanhToan.mockResolvedValue(phanHoi([]))
    const store = useThanhToanStore()
    store.boLocThanhToan = {
      ...store.boLocThanhToan,
      order_code: 'ORD-CU',
      payment_status: 'CAN_DOI_SOAT',
      per_page: 20,
    }
    store.phanTrangThanhToan.current_page = 4

    await store.datLaiBoLocThanhToan()

    expect(store.boLocThanhToan).toMatchObject({ order_code: '', payment_status: '', per_page: 20 })
    expect(api.taiDanhSachThanhToan).toHaveBeenCalledWith(expect.objectContaining({
      order_code: '',
      payment_status: '',
      page: 1,
    }))
  })

  it('bo qua response cu khi request moi ve truoc', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiDanhSachThanhToan
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiDanhSachThanhToan({ boLoc: { order_code: 'CU' } })
    const yeuCauMoi = store.taiDanhSachThanhToan({ boLoc: { order_code: 'MOI' } })
    moi.resolve(phanHoi([{ ...THANH_TOAN, payment_id: 8 }]))
    await yeuCauMoi
    cu.resolve(phanHoi([{ ...THANH_TOAN, payment_id: 9 }]))
    await expect(yeuCauCu).resolves.toBeNull()

    expect(store.danhSachThanhToan[0].payment_id).toBe(8)
    expect(store.boLocThanhToan.order_code).toBe('MOI')
  })

  it('fail closed response sai va giu loi an toan', async () => {
    api.taiDanhSachThanhToan.mockResolvedValue({ data: { items: [THANH_TOAN], pagination: {} } })
    const store = useThanhToanStore()

    await expect(store.taiDanhSachThanhToan()).rejects.toMatchObject({
      code: 'PAYMENT_LIST_RESPONSE_INVALID',
    })
    expect(store.danhSachThanhToan).toEqual([])
    expect(store.loiThanhToan).toMatchObject({ code: 'PAYMENT_LIST_RESPONSE_INVALID' })
  })

  it.each([
    { httpStatus: 401, code: 'UNAUTHENTICATED', message: 'Chưa xác thực.' },
    { httpStatus: 422, code: 'INVALID_PAYMENT_FILTER', message: 'Bộ lọc không hợp lệ.', fieldErrors: { order_code: ['Sai mã đơn.'] } },
    { httpStatus: 503, code: 'SERVICE_UNAVAILABLE', message: 'Máy chủ tạm thời không khả dụng.' },
  ])('giu shared Auth va map loi %j, khong tu retry/cleanup credential', async (loi) => {
    api.taiDanhSachThanhToan.mockRejectedValueOnce(loi)
    const store = useThanhToanStore()
    store.danhSachThanhToan = [THANH_TOAN]

    await expect(store.taiDanhSachThanhToan()).rejects.toMatchObject({
      httpStatus: loi.httpStatus,
      code: loi.code,
    })
    expect(store.danhSachThanhToan).toEqual([THANH_TOAN])
    expect(store.loiThanhToan).toMatchObject({ httpStatus: loi.httpStatus, code: loi.code })
    expect(api.taiDanhSachThanhToan).toHaveBeenCalledTimes(1)
  })

  it('detail 404 generic va ID sai khong goi service', async () => {
    api.taiChiTietThanhToan.mockRejectedValue({
      httpStatus: 404,
      code: 'PAYMENT_NOT_FOUND',
      message: 'Không tìm thấy thông tin thanh toán.',
    })
    const store = useThanhToanStore()

    await expect(store.taiChiTietThanhToan(7)).rejects.toMatchObject({ httpStatus: 404 })
    expect(store.loiChiTiet).toMatchObject({ httpStatus: 404, code: 'PAYMENT_NOT_FOUND' })
    await expect(store.taiChiTietThanhToan(0)).rejects.toMatchObject({
      httpStatus: 404,
      code: 'PAYMENT_NOT_FOUND',
    })
    expect(api.taiChiTietThanhToan).toHaveBeenCalledTimes(1)
  })

  it('giu membership status tu DTO va khong tinh quyen loi tai client', async () => {
    api.taiChiTietThanhToan.mockResolvedValue({ data: THANH_TOAN })
    const store = useThanhToanStore()

    await store.taiChiTietThanhToan(7)
    expect(store.chiTietThanhToan.status).toBe('THANH_CONG')
    expect(store.chiTietThanhToan.membership_term.status).toBe('CHO_KICH_HOAT')
  })

  it('detail race bo qua stale success sau khi request moi thanh cong', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiChiTietThanhToan
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiChiTietThanhToan(7)
    const yeuCauMoi = store.taiChiTietThanhToan(8)
    moi.resolve({ data: { ...THANH_TOAN, payment_id: 8 } })
    await yeuCauMoi
    cu.resolve({ data: { ...THANH_TOAN, payment_id: 7 } })

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.chiTietThanhToan.payment_id).toBe(8)
    expect(store.loiChiTiet).toBeNull()
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.daTaiChiTietLanDau).toBe(true)
  })

  it('detail race bo qua stale error sau khi request moi thanh cong', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiChiTietThanhToan
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiChiTietThanhToan(7)
    const yeuCauMoi = store.taiChiTietThanhToan(8)
    moi.resolve({ data: { ...THANH_TOAN, payment_id: 8 } })
    await yeuCauMoi
    cu.reject({ httpStatus: 503, code: 'STALE_DETAIL_ERROR', message: 'stale' })

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.chiTietThanhToan.payment_id).toBe(8)
    expect(store.loiChiTiet).toBeNull()
    expect(store.dangTaiChiTiet).toBe(false)
    expect(store.daTaiChiTietLanDau).toBe(true)
  })

  it('reconciliation Payment race bo qua stale success va giu filter pagination moi', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiDanhSachCanDoiSoat
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiDanhSachCanDoiSoat({ boLoc: { order_code: 'A' }, trang: 3 })
    const yeuCauMoi = store.taiDanhSachCanDoiSoat({ boLoc: { order_code: 'B' }, trang: 5 })
    moi.resolve(phanHoi([{ ...THANH_TOAN, payment_id: 8 }], {
      current_page: 5,
      total: 50,
      last_page: 5,
    }))
    await yeuCauMoi
    cu.resolve(phanHoi([{ ...THANH_TOAN, payment_id: 7 }], {
      current_page: 3,
      total: 30,
      last_page: 3,
    }))

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.danhSachCanDoiSoat[0].payment_id).toBe(8)
    expect(store.boLocCanDoiSoat.order_code).toBe('B')
    expect(store.phanTrangCanDoiSoat).toMatchObject({ current_page: 5, total: 50, last_page: 5 })
    expect(store.loiCanDoiSoat).toBeNull()
    expect(store.dangTaiCanDoiSoat).toBe(false)
    expect(store.daTaiCanDoiSoatLanDau).toBe(true)
  })

  it('reconciliation Payment race bo qua stale error va giu filter pagination moi', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiDanhSachCanDoiSoat
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiDanhSachCanDoiSoat({ boLoc: { order_code: 'A' }, trang: 3 })
    const yeuCauMoi = store.taiDanhSachCanDoiSoat({ boLoc: { order_code: 'B' }, trang: 5 })
    moi.resolve(phanHoi([{ ...THANH_TOAN, payment_id: 8 }], {
      current_page: 5,
      total: 50,
      last_page: 5,
    }))
    await yeuCauMoi
    cu.reject({ httpStatus: 503, code: 'STALE_RECONCILIATION_ERROR', message: 'stale' })

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.danhSachCanDoiSoat[0].payment_id).toBe(8)
    expect(store.boLocCanDoiSoat.order_code).toBe('B')
    expect(store.phanTrangCanDoiSoat).toMatchObject({ current_page: 5, total: 50, last_page: 5 })
    expect(store.loiCanDoiSoat).toBeNull()
    expect(store.dangTaiCanDoiSoat).toBe(false)
    expect(store.daTaiCanDoiSoatLanDau).toBe(true)
  })

  it('reconciliation event race bo qua stale success va giu filter pagination moi', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiSuKienThanhToan
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiSuKienThanhToan({ boLoc: { processing_status: 'A' }, trang: 3 })
    const yeuCauMoi = store.taiSuKienThanhToan({ boLoc: { processing_status: 'B' }, trang: 5 })
    moi.resolve(phanHoi([{ ...SU_KIEN_CHUA_LIEN_KET, event_id: 81 }], {
      current_page: 5,
      total: 50,
      last_page: 5,
    }))
    await yeuCauMoi
    cu.resolve(phanHoi([{ ...SU_KIEN_CHUA_LIEN_KET, event_id: 71 }], {
      current_page: 3,
      total: 30,
      last_page: 3,
    }))

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.danhSachSuKienThanhToan[0].event_id).toBe(81)
    expect(store.boLocSuKienThanhToan.processing_status).toBe('B')
    expect(store.phanTrangSuKienThanhToan).toMatchObject({ current_page: 5, total: 50, last_page: 5 })
    expect(store.loiSuKienThanhToan).toBeNull()
    expect(store.dangTaiSuKienThanhToan).toBe(false)
    expect(store.daTaiSuKienThanhToanLanDau).toBe(true)
  })

  it('reconciliation event race bo qua stale error va giu filter pagination moi', async () => {
    const cu = deferred()
    const moi = deferred()
    api.taiSuKienThanhToan
      .mockImplementationOnce(() => cu.promise)
      .mockImplementationOnce(() => moi.promise)
    const store = useThanhToanStore()

    const yeuCauCu = store.taiSuKienThanhToan({ boLoc: { processing_status: 'A' }, trang: 3 })
    const yeuCauMoi = store.taiSuKienThanhToan({ boLoc: { processing_status: 'B' }, trang: 5 })
    moi.resolve(phanHoi([{ ...SU_KIEN_CHUA_LIEN_KET, event_id: 81 }], {
      current_page: 5,
      total: 50,
      last_page: 5,
    }))
    await yeuCauMoi
    cu.reject({ httpStatus: 503, code: 'STALE_EVENT_ERROR', message: 'stale' })

    await expect(yeuCauCu).resolves.toBeNull()
    expect(store.danhSachSuKienThanhToan[0].event_id).toBe(81)
    expect(store.boLocSuKienThanhToan.processing_status).toBe('B')
    expect(store.phanTrangSuKienThanhToan).toMatchObject({ current_page: 5, total: 50, last_page: 5 })
    expect(store.loiSuKienThanhToan).toBeNull()
    expect(store.dangTaiSuKienThanhToan).toBe(false)
    expect(store.daTaiSuKienThanhToanLanDau).toBe(true)
  })

  it('giu hai queue authoritative doc lap va chap nhan event payment_id null', async () => {
    api.taiDanhSachCanDoiSoat.mockResolvedValue(phanHoi([THANH_TOAN]))
    api.taiSuKienThanhToan.mockResolvedValue(phanHoi([SU_KIEN_CHUA_LIEN_KET]))
    const store = useThanhToanStore()

    await store.apDungBoLocCanDoiSoat({ payment_status: 'CAN_DOI_SOAT' })
    await store.apDungBoLocSuKienThanhToan({ processing_status: 'CAN_DOI_SOAT' })

    expect(api.taiDanhSachCanDoiSoat).toHaveBeenCalledWith(expect.objectContaining({
      payment_status: 'CAN_DOI_SOAT',
      page: 1,
    }))
    expect(api.taiSuKienThanhToan).toHaveBeenCalledWith(expect.objectContaining({
      processing_status: 'CAN_DOI_SOAT',
      page: 1,
    }))
    expect(store.danhSachCanDoiSoat).toEqual([THANH_TOAN])
    expect(store.danhSachSuKienThanhToan).toEqual([SU_KIEN_CHUA_LIEN_KET])
    expect(store.danhSachSuKienThanhToan[0].payment_id).toBeNull()
  })

  it('403 reset toan bo domain de page chuyen khung cam quyen', async () => {
    api.taiDanhSachCanDoiSoat.mockRejectedValue({
      httpStatus: 403,
      message: 'Bạn không có quyền truy cập.',
    })
    const store = useThanhToanStore()
    store.danhSachThanhToan = [THANH_TOAN]
    store.danhSachSuKienThanhToan = [SU_KIEN_CHUA_LIEN_KET]

    await expect(store.taiDanhSachCanDoiSoat()).rejects.toMatchObject({ httpStatus: 403 })
    expect(store.danhSachThanhToan).toEqual([])
    expect(store.danhSachCanDoiSoat).toEqual([])
    expect(store.danhSachSuKienThanhToan).toEqual([])
    expect(store.loiCanDoiSoat).toBeNull()
  })

  it('cleanup tang sequence va chan response tre sau logout/doi actor', async () => {
    const pending = deferred()
    api.taiDanhSachThanhToan.mockReturnValueOnce(pending.promise)
    const store = useThanhToanStore()
    const yeuCau = store.taiDanhSachThanhToan()
    const sequenceTruoc = store.soThuTuThanhToan

    expect(xoaDuLieuThanhToanNeuDaKhoiTao()).toBe(true)
    expect(store.soThuTuThanhToan).toBeGreaterThan(sequenceTruoc)
    expect(store.danhSachThanhToan).toEqual([])

    pending.resolve(phanHoi([THANH_TOAN]))
    await expect(yeuCau).resolves.toBeNull()
    expect(store.danhSachThanhToan).toEqual([])
  })

  it('cleanup helper khong tao store ngoai y muon', () => {
    const pinia = createPinia()
    expect(xoaDuLieuThanhToanNeuDaKhoiTao(pinia)).toBe(false)
    expect(pinia.state.value.thanh_toan).toBeUndefined()
  })
})
