import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  CAC_CHUYEN_DOI_VAI_TRO,
  CAC_TRANG_THAI_TAI_KHOAN,
  CAC_VAI_TRO_TAI_KHOAN,
  capVaiTro,
  capNhatTrangThaiTaiKhoan,
  laIdTaiKhoanHopLe,
  taiChiTietTaiKhoan,
  taiChiTietHuanLuyenVien,
  taiChiTietHoiVien,
  taiChiTietNhanVienLeTan,
  taiDanhSachTaiKhoan,
  taiDanhSachHuanLuyenVien,
  taiDanhSachHoiVien,
  taiDanhSachNhanVienLeTan,
  thuHoiVaiTro,
} from './tai_khoan.api.js'

const PHAN_HOI = {
  data: {
    items: [{ id: 7, name: 'Nguyễn Minh Anh', roles: [] }],
    pagination: { current_page: 1, per_page: 20, total: 1, last_page: 1 },
  },
}

describe('tai_khoan.api FE1-T03', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('gui GET accounts voi query rong khi dung default Backend', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await expect(taiDanhSachTaiKhoan()).resolves.toBe(PHAN_HOI)

    expect(get).toHaveBeenCalledWith('/admin/accounts', { params: {} })
  })

  it('tai danh sach Hoi vien luon ep role MEMBER va chi gui filter duoc phep', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachHoiVien({
      search: '  Anh  ',
      status: 'HOAT_DONG',
      role: 'PT',
      branch_id: 9,
      page: 2,
      per_page: 20,
    })

    expect(get).toHaveBeenCalledWith('/admin/accounts', {
      params: {
        search: 'Anh',
        status: 'HOAT_DONG',
        role: 'MEMBER',
        page: 2,
        per_page: 20,
      },
    })
    expect(JSON.stringify(get.mock.calls[0])).not.toMatch(/branch_id|role_id|actor/i)
  })

  it('gui dung search/status/role/page/per_page allow-list sau khi trim', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachTaiKhoan({
      search: '  member.search  ',
      status: 'HOAT_DONG',
      role: 'MEMBER',
      page: 3,
      per_page: 50,
    })

    expect(get).toHaveBeenCalledWith('/admin/accounts', {
      params: {
        search: 'member.search',
        status: 'HOAT_DONG',
        role: 'MEMBER',
        page: 3,
        per_page: 50,
      },
    })
  })

  it('khong gui filter rong hoac authority field', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachTaiKhoan({
      search: ' ',
      status: '',
      role: '',
      branch_id: 99,
      actor_id: 10,
      sort: 'email',
      permission: 'ADMIN',
    })

    expect(get.mock.calls[0][1]).toEqual({ params: {} })
    expect(JSON.stringify(get.mock.calls[0])).not.toMatch(/branch|actor|permission|sort|role_id/i)
  })

  it('cong bo dung enum backend cho status va role', () => {
    expect(CAC_TRANG_THAI_TAI_KHOAN).toEqual(['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'])
    expect(CAC_VAI_TRO_TAI_KHOAN).toEqual(['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'])
  })

  it('cho phep search toi da 150 ky tu', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachTaiKhoan({ search: 'a'.repeat(150) })

    expect(get).toHaveBeenCalledTimes(1)
  })

  it('chan search qua dai truoc request va map field 422 an toan', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiDanhSachTaiKhoan({ search: 'a'.repeat(151) })).rejects.toMatchObject({
      httpStatus: 422,
      code: 'INVALID_ACCOUNT_FILTER',
      fieldErrors: { search: ['Từ khóa không được dài quá 150 ký tự.'] },
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('chan status va role ngoai enum Backend', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiDanhSachTaiKhoan({ status: 'ACTIVE' })).rejects.toMatchObject({
      httpStatus: 422,
      fieldErrors: { status: ['Trạng thái tài khoản không hợp lệ.'] },
    })
    await expect(taiDanhSachTaiKhoan({ role: 'OWNER' })).rejects.toMatchObject({
      httpStatus: 422,
      fieldErrors: { role: ['Vai trò tài khoản không hợp lệ.'] },
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('chan page khong phai so nguyen duong', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiDanhSachTaiKhoan({ page: 0 })).rejects.toMatchObject({
      httpStatus: 422,
      fieldErrors: { page: ['Trang phải là một số nguyên từ 1 trở lên.'] },
    })
    await expect(taiDanhSachTaiKhoan({ page: 1.5 })).rejects.toMatchObject({ httpStatus: 422 })

    expect(get).not.toHaveBeenCalled()
  })

  it('chan per_page vuot gioi han 1..100 cua Backend', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiDanhSachTaiKhoan({ per_page: 0 })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiDanhSachTaiKhoan({ per_page: 101 })).rejects.toMatchObject({
      fieldErrors: { per_page: ['Số dòng mỗi trang phải nằm trong khoảng từ 1 đến 100.'] },
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('giu nguyen envelope response exact tu Backend', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    const ketQua = await taiDanhSachTaiKhoan({ page: 1, per_page: 20 })

    expect(ketQua).toBe(PHAN_HOI)
    expect(ketQua.data.pagination).toEqual(PHAN_HOI.data.pagination)
  })

  it.each([
    [{ httpStatus: 401, message: 'Thông tin xác thực không hợp lệ.' }],
    [{ httpStatus: 403, message: 'Bạn không có quyền thực hiện thao tác này.' }],
    [{ httpStatus: 503, message: 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.' }],
    [{ httpStatus: null, isNetworkError: true, message: 'Không thể kết nối đến máy chủ.' }],
  ])('giu nguyen normalized error %j tu Axios client', async (loi) => {
    const get = vi.spyOn(ketNoiApi, 'get').mockRejectedValue(loi)

    await expect(taiDanhSachTaiKhoan({ page: 1 })).rejects.toBe(loi)
    expect(get).toHaveBeenCalledWith('/admin/accounts', { params: { page: 1 } })
  })

  it('khong gui Idempotency-Key hay mutation method', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachTaiKhoan({ page: 1 })

    expect(get.mock.calls[0][0]).toBe('/admin/accounts')
    expect(get.mock.calls[0][1]).not.toHaveProperty('headers')
  })

  it('khong mutate object filter do caller so huu', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })
    const boLoc = { search: '  Anh  ', role: 'MEMBER', page: 2 }

    await taiDanhSachTaiKhoan(boLoc)

    expect(boLoc).toEqual({ search: '  Anh  ', role: 'MEMBER', page: 2 })
    expect(get).toHaveBeenCalledWith('/admin/accounts', {
      params: { search: 'Anh', role: 'MEMBER', page: 2 },
    })
  })

  it.each([7, '7', ' 7 '])('chap nhan account id duong hop le %j', (id) => {
    expect(laIdTaiKhoanHopLe(id)).toBe(true)
  })

  it.each([0, -1, '0', '-1', '1.5', 'abc', '', null, undefined, {}, []])(
    'tu choi account id khong an toan %j',
    (id) => {
      expect(laIdTaiKhoanHopLe(id)).toBe(false)
    },
  )

  it('gui GET detail dung named path va giu envelope Backend', async () => {
    const phanHoi = { data: { id: 7, name: 'Nguyễn Minh Anh', status: 'HOAT_DONG', roles: [] } }
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: phanHoi })

    await expect(taiChiTietTaiKhoan(7)).resolves.toBe(phanHoi)

    expect(get).toHaveBeenCalledWith('/admin/accounts/7')
    expect(get.mock.calls[0][1]).toBeUndefined()
  })

  it('tai chi tiet Hoi vien dung lai Account detail, khong them role hay API rieng', async () => {
    const phanHoi = { data: { id: 7, name: 'Nguyễn Minh Anh', status: 'HOAT_DONG', roles: [] } }
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: phanHoi })

    await expect(taiChiTietHoiVien(' 7 ')).resolves.toBe(phanHoi)

    expect(get).toHaveBeenCalledWith('/admin/accounts/7')
    expect(get.mock.calls[0][1]).toBeUndefined()
  })

  it('tai chi tiet Huan luyen vien dung mot Account detail GET va khong them query', async () => {
    const phanHoi = {
      data: {
        id: 7,
        name: 'Nguyễn Minh Anh',
        status: 'HOAT_DONG',
        roles: [{ code: 'PT', active: true }],
        trainer_profile: null,
      },
    }
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: phanHoi })

    await expect(taiChiTietHuanLuyenVien(' 7 ')).resolves.toBe(phanHoi)

    expect(get).toHaveBeenCalledTimes(1)
    expect(get).toHaveBeenCalledWith('/admin/accounts/7')
    expect(get.mock.calls[0][1]).toBeUndefined()
  })

  it('tai danh sach Nhan vien le tan luon ep role RECEPTIONIST va loai authority field', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachNhanVienLeTan({
      search: '  Le Tan  ',
      status: 'HOAT_DONG',
      role: 'ADMIN',
      branch_id: 9,
      page: 2,
      per_page: 20,
    })

    expect(get).toHaveBeenCalledWith('/admin/accounts', {
      params: {
        search: 'Le Tan',
        status: 'HOAT_DONG',
        role: 'RECEPTIONIST',
        page: 2,
        per_page: 20,
      },
    })
    expect(JSON.stringify(get.mock.calls[0])).not.toMatch(/branch_id|role_id|actor/i)
  })

  it('tai danh sach Huan luyen vien luon ep role PT va chi gui filter duoc phep', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI })

    await taiDanhSachHuanLuyenVien({
      search: '  PT Anh  ',
      status: 'HOAT_DONG',
      role: 'ADMIN',
      branch_id: 9,
      authority: 'SUPER_ADMIN',
      page: 2,
      per_page: 20,
    })

    expect(get).toHaveBeenCalledWith('/admin/accounts', {
      params: {
        search: 'PT Anh',
        status: 'HOAT_DONG',
        role: 'PT',
        page: 2,
        per_page: 20,
      },
    })
    expect(JSON.stringify(get.mock.calls[0])).not.toMatch(/branch_id|authority|role_id|actor/i)
  })

  it('tai chi tiet Nhan vien le tan dung Account detail, khong them query', async () => {
    const phanHoi = { data: { id: 7, name: 'Lễ tân', status: 'HOAT_DONG', roles: [] } }
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: phanHoi })

    await expect(taiChiTietNhanVienLeTan(' 7 ')).resolves.toBe(phanHoi)

    expect(get).toHaveBeenCalledWith('/admin/accounts/7')
    expect(get.mock.calls[0][1]).toBeUndefined()
  })

  it('chan detail id sai truoc request va hien unavailable an toan', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiChiTietTaiKhoan('-3')).rejects.toMatchObject({
      httpStatus: 404,
      message: 'Không thể truy cập dữ liệu tài khoản này.',
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('wrapper detail PT tu choi id khong hop le truoc request', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiChiTietHuanLuyenVien('7/../8')).rejects.toMatchObject({
      httpStatus: 404,
      message: 'Không thể truy cập dữ liệu tài khoản này.',
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('gui PATCH status voi body exact va khong them headers', async () => {
    const patch = vi.spyOn(ketNoiApi, 'patch').mockResolvedValue({ data: { id: 7 } })

    await expect(capNhatTrangThaiTaiKhoan('7', 'BI_KHOA')).resolves.toEqual({ id: 7 })

    expect(patch).toHaveBeenCalledWith('/admin/accounts/7/status', { status: 'BI_KHOA' })
    expect(patch.mock.calls[0][2]).toBeUndefined()
    expect(JSON.stringify(patch.mock.calls[0])).not.toMatch(/role|branch|actor|reason|current|idempotency/i)
  })

  it('chan status ngoai enum va khong gui PATCH', async () => {
    const patch = vi.spyOn(ketNoiApi, 'patch')

    await expect(capNhatTrangThaiTaiKhoan(7, 'ACTIVE')).rejects.toMatchObject({
      httpStatus: 422,
      fieldErrors: { status: ['Trạng thái tài khoản không hợp lệ.'] },
    })

    expect(patch).not.toHaveBeenCalled()
  })

  it.each([
    [{ httpStatus: 422, code: 'INVALID_ACCOUNT_STATUS' }],
    [{ httpStatus: 409, code: 'LAST_ACTIVE_ADMIN_PROTECTED' }],
    [{ httpStatus: 401 }],
    [{ httpStatus: 403 }],
    [{ httpStatus: 404 }],
    [{ httpStatus: 503 }],
    [{ httpStatus: null, isNetworkError: true }],
  ])('giu nguyen normalized error cua status mutation %j', async (loi) => {
    const patch = vi.spyOn(ketNoiApi, 'patch').mockRejectedValue(loi)

    await expect(capNhatTrangThaiTaiKhoan(7, 'BI_KHOA')).rejects.toBe(loi)
    expect(patch).toHaveBeenCalledWith('/admin/accounts/7/status', { status: 'BI_KHOA' })
  })

  it('cong bo transition role exact tu Backend', () => {
    expect(CAC_CHUYEN_DOI_VAI_TRO).toEqual(['GRANTED', 'REGRANTED', 'REVOKED', 'UNCHANGED'])
  })

  it('PUT cap role dung path, khong body va khong headers', async () => {
    const put = vi.spyOn(ketNoiApi, 'put').mockResolvedValue({
      data: {
        data: {
          account_id: 7, role: 'PT', active: true, changed: true, transition: 'GRANTED',
        },
      },
    })

    await expect(capVaiTro('7', 'PT')).resolves.toEqual({
      data: {
        account_id: 7, role: 'PT', active: true, changed: true, transition: 'GRANTED',
      },
    })

    expect(put).toHaveBeenCalledWith('/admin/accounts/7/roles/PT')
    expect(put.mock.calls[0][1]).toBeUndefined()
    expect(JSON.stringify(put.mock.calls[0])).not.toMatch(/idempotency|actor|profile|current/i)
  })

  it('DELETE thu hoi role dung path, khong body va khong headers', async () => {
    const del = vi.spyOn(ketNoiApi, 'delete').mockResolvedValue({
      data: {
        data: {
          account_id: 7, role: 'ADMIN', active: false, changed: true, transition: 'REVOKED',
        },
      },
    })

    await expect(thuHoiVaiTro(7, 'ADMIN')).resolves.toMatchObject({
      data: { account_id: 7, role: 'ADMIN', transition: 'REVOKED' },
    })

    expect(del).toHaveBeenCalledWith('/admin/accounts/7/roles/ADMIN')
    expect(del.mock.calls[0][1]).toBeUndefined()
  })

  it.each([
    [7, 'admin'], [7, 'FREE'], [7, 'Premium'], [7, 'OWNER'], [7, ''], [7, null],
    ['bad', 'PT'], [0, 'PT'], [-1, 'PT'], [{}, 'PT'],
  ])('chan role/id khong hop le truoc mutation %j %j', async (id, role) => {
    const put = vi.spyOn(ketNoiApi, 'put')
    const del = vi.spyOn(ketNoiApi, 'delete')

    const ketQuaCap = capVaiTro(id, role)
    if (typeof id === 'number' && Number.isSafeInteger(id) && id > 0) {
      await expect(ketQuaCap).rejects.toMatchObject({
        httpStatus: expect.any(Number),
        fieldErrors: { role: ['Vai trò tài khoản không hợp lệ.'] },
      })
    } else {
      await expect(ketQuaCap).rejects.toMatchObject({ httpStatus: 404 })
    }
    await expect(thuHoiVaiTro(id, role)).rejects.toMatchObject({
      httpStatus: expect.any(Number),
    })

    expect(put).not.toHaveBeenCalled()
    expect(del).not.toHaveBeenCalled()
  })

  it.each([
    [{ httpStatus: 409, code: 'ROLE_CONFLICT' }],
    [{ httpStatus: 409, code: 'TRAINER_PROFILE_REQUIRED' }],
    [{ httpStatus: 409, code: 'LAST_ACTIVE_ADMIN_PROTECTED' }],
    [{ httpStatus: 422, code: 'INVALID_ROLE' }],
    [{ httpStatus: 401 }],
    [{ httpStatus: 403 }],
    [{ httpStatus: 404 }],
    [{ httpStatus: 503 }],
    [{ httpStatus: null, isNetworkError: true }],
  ])('giu nguyen normalized error role mutation %j', async (loi) => {
    const put = vi.spyOn(ketNoiApi, 'put').mockRejectedValue(loi)

    await expect(capVaiTro(7, 'PT')).rejects.toBe(loi)
    expect(put).toHaveBeenCalledWith('/admin/accounts/7/roles/PT')
  })
})
