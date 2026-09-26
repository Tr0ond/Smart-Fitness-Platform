import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  taiChiTietLichSuTapHoiVien,
  taiLichSuTapHoiVien,
} from './lich_su_tap_pt.api.js'

describe('lich_su_tap_pt.api FE5', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('reads immutable history list and detail with bounded cursor', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: [] })
    await taiLichSuTapHoiVien(7, { limit: 20, before_id: 15 })
    await taiChiTietLichSuTapHoiVien(7, 14)
    expect(get).toHaveBeenNthCalledWith(1, '/pt/members/7/workout/sessions', {
      params: { limit: 20, before_id: 15 },
    })
    expect(get).toHaveBeenNthCalledWith(2, '/pt/members/7/workout/sessions/14')
  })

  it('rejects mutation-like invalid cursor values and never sends DELETE', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')
    await expect(taiLichSuTapHoiVien(7, { limit: 101 })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiChiTietLichSuTapHoiVien(7, 0)).rejects.toMatchObject({ httpStatus: 404 })
    expect(get).not.toHaveBeenCalled()
  })
})
