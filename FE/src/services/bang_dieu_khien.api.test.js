import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import { taiTongQuanAdmin } from './bang_dieu_khien.api.js'

const PHAN_HOI_DASHBOARD = {
  data: {
    branch: { id: 1, timezone: 'Asia/Ho_Chi_Minh' },
    period: { from: '2026-08-03', to: '2026-09-01', inclusive_days: 30 },
    accounts: { active_members_count: 2, active_trainers_count: 1 },
    memberships: { active_terms_count: 1, awaiting_activation_terms_count: 0 },
    activity: {
      check_ins_today_count: 0,
      completed_workouts_today_count: 0,
      completed_workouts_in_period_count: 0,
    },
    payments: { successful_in_period_count: 0, reconciliation_required_count: 0 },
    pt: { active_assignments_count: 0 },
    generated_at: '2026-09-01T00:00:00.000Z',
  },
}

describe('bang_dieu_khien.api FE1-T02', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('GET Dashboard ban dau khong gui from/to va giu nguyen body', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI_DASHBOARD })

    await expect(taiTongQuanAdmin()).resolves.toBe(PHAN_HOI_DASHBOARD)

    expect(get).toHaveBeenCalledWith('/admin/dashboard')
  })

  it('GET Dashboard voi cap ngay gui exact from/to va khong gui branch/timezone/metric', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: PHAN_HOI_DASHBOARD })

    await taiTongQuanAdmin({ from: '2026-08-01', to: '2026-08-31' })

    expect(get).toHaveBeenCalledWith('/admin/dashboard', {
      params: { from: '2026-08-01', to: '2026-08-31' },
    })
    expect(JSON.stringify(get.mock.calls[0])).not.toMatch(/branch_id|chi_nhanh_id|timezone|metric|Idempotency-Key/)
  })

  it('tu choi cap ngay thieu ma khong gui request', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')

    await expect(taiTongQuanAdmin({ from: '2026-08-01' })).rejects.toMatchObject({
      code: 'INVALID_DASHBOARD_PERIOD',
    })

    expect(get).not.toHaveBeenCalled()
  })

  it('giu nguyen normalized 422/403/5xx hoac network error tu Axios client', async () => {
    const loi = {
      httpStatus: 422,
      code: 'INVALID_DASHBOARD_PERIOD',
      message: 'Khoảng Dashboard chưa hợp lệ.',
      fieldErrors: { to: ['Khoảng thời gian không hợp lệ.'] },
      isNetworkError: false,
    }
    vi.spyOn(ketNoiApi, 'get').mockRejectedValue(loi)

    await expect(taiTongQuanAdmin({ from: '2026-08-01', to: '2026-08-31' })).rejects.toBe(loi)
  })
})
