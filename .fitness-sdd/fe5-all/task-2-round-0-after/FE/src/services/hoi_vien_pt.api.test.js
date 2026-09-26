import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  laIdHoiVienHopLe,
  taiChiTietHoiVien,
  taiDanhSachHoiVienDuocPhanCong,
} from './hoi_vien_pt.api.js'

describe('hoi_vien_pt.api FE5', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('uses only current assignment list and PT member detail endpoints', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { member: { id: 7 } } })
    await taiDanhSachHoiVienDuocPhanCong()
    await taiChiTietHoiVien('7')
    expect(get).toHaveBeenNthCalledWith(1, '/pt/members')
    expect(get).toHaveBeenNthCalledWith(2, '/pt/members/7')
    expect(get.mock.calls.map(([url]) => url).join(' ')).not.toContain('/admin/')
  })

  it('validates positive safe member ids before any request', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')
    expect(laIdHoiVienHopLe(7)).toBe(true)
    expect(laIdHoiVienHopLe(0)).toBe(false)
    expect(laIdHoiVienHopLe('1e3')).toBe(false)
    await expect(taiChiTietHoiVien(0)).rejects.toMatchObject({ httpStatus: 404 })
    expect(get).not.toHaveBeenCalled()
  })
})
