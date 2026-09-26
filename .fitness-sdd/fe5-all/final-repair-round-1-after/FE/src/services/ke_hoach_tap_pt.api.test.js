import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import { taiKeHoachTapHoiVien } from './ke_hoach_tap_pt.api.js'

describe('ke_hoach_tap_pt.api FE5', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('reads official plan and future schedule through PT workspace contract', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { plan: null, future_schedule: [] } })
    await taiKeHoachTapHoiVien(7)
    expect(get).toHaveBeenCalledWith('/pt/members/7/workout/plans/current')
  })

  it('does not call proposal or member-self route for invalid member', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')
    await expect(taiKeHoachTapHoiVien('0')).rejects.toMatchObject({ httpStatus: 404 })
    expect(get).not.toHaveBeenCalled()
  })
})
