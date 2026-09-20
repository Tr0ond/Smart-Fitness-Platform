import { beforeEach, describe, expect, it, vi } from 'vitest'

const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import {
  capNhatGiaoAnMau,
  taiChiTietGiaoAnMau,
  taiDanhSachGiaoAnMau,
  taoGiaoAnMau,
  taoPhienBanGiaoAnMau,
  xuLyGiaoAnMauDaCu,
} from './giao_an_mau.api.js'

const days = [{
  order: 1,
  name: 'Ngày 1',
  estimated_minutes: 45,
  exercises: [{
    exercise_id: 9,
    order: 1,
    target_sets: 3,
    min_reps: 8,
    max_reps: 12,
    rest_seconds: 60,
    notes: null,
  }],
}]

beforeEach(() => {
  vi.clearAllMocks()
  get.mockResolvedValue({ data: { data: [] } })
  post.mockResolvedValue({ data: { data: { id: 1, new_template_id: 2 } } })
  patch.mockResolvedValue({ data: { data: { id: 1 } } })
})

describe('giao_an_mau.api FE3', () => {
  it('create va revision gửi đủ tree, giữ nullable description/notes và version', async () => {
    await taoGiaoAnMau({
      code: 'T1', name: 'T', goal: 'G', level: 'L', sessions_per_week: 1,
      description: null, status: 'HOAT_DONG', days,
    })
    await taoPhienBanGiaoAnMau(1, {
      new_code: 'T2', name: 'T2', goal: 'G2', level: 'L', sessions_per_week: 1,
      description: null, status: 'HOAT_DONG', expected_content_version: 3, days,
    })

    expect(post).toHaveBeenNthCalledWith(1, '/admin/workout-templates', expect.objectContaining({
      description: null,
      days: [expect.objectContaining({ exercises: [expect.objectContaining({ notes: null })] })],
    }))
    expect(post).toHaveBeenNthCalledWith(2, '/admin/workout-templates/1/revisions', expect.objectContaining({
      expected_content_version: 3,
      description: null,
      days: [expect.objectContaining({ exercises: [expect.objectContaining({ notes: null })] })],
    }))
  })

  it('patch metadata không gửi days và giữ description null', async () => {
    await capNhatGiaoAnMau(1, { name: 'Moi', description: null, status: 'NGUNG_SU_DUNG', days })
    expect(patch).toHaveBeenCalledWith('/admin/workout-templates/1', { name: 'Moi', description: null, status: 'NGUNG_SU_DUNG' })
    expect(patch.mock.calls[0][1]).not.toHaveProperty('days')
  })

  it('đọc list/detail bằng GET và chuẩn hóa id trước request', async () => {
    await taiDanhSachGiaoAnMau()
    await taiChiTietGiaoAnMau('7')
    expect(get).toHaveBeenNthCalledWith(1, '/admin/workout-templates')
    expect(get).toHaveBeenNthCalledWith(2, '/admin/workout-templates/7')
  })

  it('chặn tree/session bounds và mismatch trước khi gọi mạng', async () => {
    await expect(taoGiaoAnMau({ code: 'T', name: 'T', goal: 'G', level: 'L', sessions_per_week: 2, days })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoGiaoAnMau({ code: 'T', name: 'T', goal: 'G', level: 'L', sessions_per_week: 8, days })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoPhienBanGiaoAnMau(1, { new_code: 'T2', name: 'T2', goal: 'G', level: 'L', sessions_per_week: 1, expected_content_version: 1, days: [{ ...days[0], order: 8 }] })).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
  })

  it('required strings không nhận null/object và id âm không phát request', async () => {
    await expect(taoGiaoAnMau({ code: null, name: 'T', goal: 'G', level: 'L', sessions_per_week: 1, days })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoGiaoAnMau({ code: {}, name: 'T', goal: 'G', level: 'L', sessions_per_week: 1, days })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taiChiTietGiaoAnMau(0)).rejects.toMatchObject({ httpStatus: 422 })
    expect(get).not.toHaveBeenCalled()
    expect(post).not.toHaveBeenCalled()
  })

  it('phân biệt stale conflict với lỗi khác, không tự retry', () => {
    expect(xuLyGiaoAnMauDaCu({ httpStatus: 409, code: 'WORKOUT_TEMPLATE_STALE' })).toMatchObject({ laXungDot: true, giuBanNhap: true })
    expect(xuLyGiaoAnMauDaCu({ httpStatus: 422, code: 'VALIDATION' }).laXungDot).toBe(false)
  })
})
