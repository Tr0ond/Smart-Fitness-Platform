import { beforeEach, describe, expect, it, vi } from 'vitest'
const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import { capNhatGiaoAnMau, taoGiaoAnMau, taoPhienBanGiaoAnMau, xuLyGiaoAnMauDaCu } from './giao_an_mau.api.js'
const days = [{ order: 1, name: 'Ngay 1', estimated_minutes: 45, exercises: [{ exercise_id: 9, order: 1, target_sets: 3, min_reps: 8, max_reps: 12, rest_seconds: 60 }] }]
beforeEach(() => { vi.clearAllMocks(); get.mockResolvedValue({ data: { data: [] } }); post.mockResolvedValue({ data: { data: { id: 1, new_template_id: 2 } } }); patch.mockResolvedValue({ data: { data: { id: 1 } } }) })
describe('giao_an_mau.api FE3', () => {
  it('create co full days va revision co expected content version', async () => { await taoGiaoAnMau({ code: 'T1', name: 'T', goal: 'G', level: 'L', sessions_per_week: 1, status: 'HOAT_DONG', days }); await taoPhienBanGiaoAnMau(1, { new_code: 'T2', expected_content_version: 3, days, name: 'T2' }); expect(post).toHaveBeenNthCalledWith(1, '/admin/workout-templates', expect.objectContaining({ days })); expect(post).toHaveBeenNthCalledWith(2, '/admin/workout-templates/1/revisions', expect.objectContaining({ expected_content_version: 3, days })) })
  it('patch metadata khong gui days', async () => { await capNhatGiaoAnMau(1, { name: 'Moi', status: 'NGUNG_SU_DUNG', days }); expect(patch).toHaveBeenCalledWith('/admin/workout-templates/1', { name: 'Moi', status: 'NGUNG_SU_DUNG' }); expect(patch.mock.calls[0][1]).not.toHaveProperty('days') })
  it('chan so buoi khong khop so ngay', async () => { await expect(taoGiaoAnMau({ code: 'T', name: 'T', goal: 'G', level: 'L', sessions_per_week: 2, status: 'HOAT_DONG', days })).rejects.toMatchObject({ httpStatus: 422 }); expect(post).not.toHaveBeenCalled() })
  it('phan biet stale conflict va loi khac ma khong retry', () => { expect(xuLyGiaoAnMauDaCu({ httpStatus: 409, code: 'WORKOUT_TEMPLATE_STALE' })).toMatchObject({ laXungDot: true, giuBanNhap: true }); expect(xuLyGiaoAnMauDaCu({ httpStatus: 422, code: 'VALIDATION' }).laXungDot).toBe(false) })
})
