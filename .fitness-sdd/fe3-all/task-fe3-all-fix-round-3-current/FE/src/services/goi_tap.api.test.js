import { beforeEach, describe, expect, it, vi } from 'vitest'

const { get, post, patch, put } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), put: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch, put } }))
import { capNhatGoiTap, taiChiTietGoiTap, taoGoiTap, taoPayloadQuyenLoiGoiTap, thayTheQuyenLoiGoiTap } from './goi_tap.api.js'

const quyenLoi = { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: true, direct_trainer_sessions: 2 }
const quyenLoiTatCaTat = { gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 }
const cacHinhDangQuyenLoiHopLe = [
  ['Gym-only', { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 }],
  ['Chat-only', { gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: true, direct_trainer_sessions: 0 }],
  ['Direct-session-only', { gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 1 }],
  ['Unlimited-AI', { gym_access: false, fitness_assistant: true, fitness_assistant_limit: null, trainer_chat: false, direct_trainer_sessions: 0 }],
  ['Limited-AI', { gym_access: false, fitness_assistant: true, fitness_assistant_limit: 25, trainer_chat: false, direct_trainer_sessions: 0 }],
]
beforeEach(() => {
  vi.clearAllMocks()
  get.mockResolvedValue({ data: { data: [] } })
  post.mockResolvedValue({ data: { data: { id: 1 } } })
  patch.mockResolvedValue({ data: { data: { id: 1 } } })
  put.mockResolvedValue({ data: { data: { id: 1 } } })
})

describe('goi_tap.api FE3', () => {
  it('gui create allow-list va khong gui authority fields', async () => {
    await taoGoiTap({ code: 'P1', name: 'Goi 1', price: 100, duration_days: 30, status: 'DANG_BAN', benefits: quyenLoi, branch_id: 9, created_by_id: 7 })
    expect(post).toHaveBeenCalledWith('/admin/packages', expect.objectContaining({ code: 'P1', benefits: quyenLoi }))
    expect(post.mock.calls[0][1]).not.toHaveProperty('branch_id')
    expect(post.mock.calls[0][1]).not.toHaveProperty('created_by_id')
  })

  it('patch metadata va benefits dung endpoint, khong co DELETE', async () => {
    await capNhatGoiTap(2, { name: 'Moi', status: 'NGUNG_BAN' })
    await thayTheQuyenLoiGoiTap(2, quyenLoi)
    expect(patch).toHaveBeenCalledWith('/admin/packages/2', { name: 'Moi', status: 'NGUNG_BAN' })
    expect(put).toHaveBeenCalledWith('/admin/packages/2/benefits', quyenLoi)
  })

  it('nullable description được giữ nguyên trong detail/PATCH', async () => {
    await taiChiTietGoiTap('2')
    await capNhatGoiTap(2, { description: null })
    expect(get).toHaveBeenCalledWith('/admin/packages/2')
    expect(patch).toHaveBeenCalledWith('/admin/packages/2', { description: null })
  })

  it('price phải là integer 1..999999999999999', async () => {
    for (const price of [0, 1.5, 1000000000000000]) {
      await expect(taoGoiTap({ code: 'P1', name: 'Goi 1', price, duration_days: 1, status: 'DANG_BAN', benefits: quyenLoi })).rejects.toMatchObject({ httpStatus: 422 })
    }
    expect(post).not.toHaveBeenCalled()
  })

  it('benefit invariant AI modes and at least one effective benefit', async () => {
    expect(() => taoPayloadQuyenLoiGoiTap({ ...quyenLoi, fitness_assistant: true, fitness_assistant_limit: 0 })).toThrow()
    expect(taoPayloadQuyenLoiGoiTap({ ...quyenLoi, fitness_assistant: true, fitness_assistant_limit: null })).toEqual(expect.objectContaining({ fitness_assistant_limit: null }))
  })

  it('tao va thay the quyen loi deu chan bo tat ca truoc network', async () => {
    await expect(taoGoiTap({ code: 'P1', name: 'Goi 1', price: 100, duration_days: 30, status: 'DANG_BAN', benefits: quyenLoiTatCaTat }))
      .rejects.toMatchObject({ httpStatus: 422 })
    await expect(thayTheQuyenLoiGoiTap(7, quyenLoiTatCaTat))
      .rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
    expect(put).not.toHaveBeenCalled()
  })

  it.each(cacHinhDangQuyenLoiHopLe)('public mutation giu payload exact cho %s', async (_ten, benefits) => {
    await taoGoiTap({ code: 'P1', name: 'Goi 1', price: 100, duration_days: 30, status: 'DANG_BAN', benefits })
    await thayTheQuyenLoiGoiTap(7, benefits)
    expect(post).toHaveBeenCalledWith('/admin/packages', {
      code: 'P1',
      name: 'Goi 1',
      price: 100,
      duration_days: 30,
      status: 'DANG_BAN',
      benefits,
    })
    expect(put).toHaveBeenCalledWith('/admin/packages/7/benefits', benefits)
  })
})
