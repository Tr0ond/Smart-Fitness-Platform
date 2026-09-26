import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import { taiChiSoCoThe, taiTienDoBaiTap, taiTienDoHoiVien } from './tien_do.api.js'

describe('tien_do.api FE5', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('sends exact PT progress URLs and read filters', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { items: [] } })
    await taiTienDoHoiVien(7, { from: '2026-09-01', to: '2026-09-22' })
    await taiChiSoCoThe(7, { limit: 20, before_id: 9, before_measured_at: '2026-09-20T10:00:00Z' })
    await taiTienDoBaiTap(7, 12, { from: '2026-09-01', limit: 10 })
    expect(get).toHaveBeenNthCalledWith(1, '/pt/members/7/progress/overview', {
      params: { from: '2026-09-01', to: '2026-09-22' },
    })
    expect(get).toHaveBeenNthCalledWith(2, '/pt/members/7/progress/body', {
      params: { limit: 20, before_id: 9, before_measured_at: '2026-09-20T10:00:00Z' },
    })
    expect(get).toHaveBeenNthCalledWith(3, '/pt/members/7/progress/exercises/12', {
      params: { from: '2026-09-01', limit: 10 },
    })
  })

  it('rejects invalid dates, exercise ids and bounds without fallback endpoint', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')
    await expect(taiTienDoHoiVien(7, { from: '09/01/2026' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiChiSoCoThe(7, { limit: 101 })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiTienDoBaiTap(7, 0)).rejects.toMatchObject({ httpStatus: 422 })
    expect(get).not.toHaveBeenCalled()
  })
})
