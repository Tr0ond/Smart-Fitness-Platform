import { beforeEach, describe, expect, it, vi } from 'vitest'

const { get, post, patch, put } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), put: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch, put } }))
import { capNhatGoiTap, taoGoiTap, thayTheQuyenLoiGoiTap } from './goi_tap.api.js'

const quyenLoi = { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: true, direct_trainer_sessions: 2 }
beforeEach(() => { vi.clearAllMocks(); get.mockResolvedValue({ data: { data: [] } }); post.mockResolvedValue({ data: { data: { id: 1 } } }); patch.mockResolvedValue({ data: { data: { id: 1 } } }); put.mockResolvedValue({ data: { data: { id: 1 } } }) })
describe('goi_tap.api FE3', () => {
  it('gui create allow-list va khong gui authority fields', async () => { await taoGoiTap({ code: 'P1', name: 'Goi 1', price: 100, duration_days: 30, status: 'DANG_BAN', benefits: quyenLoi, branch_id: 9, created_by_id: 7 }); expect(post).toHaveBeenCalledWith('/admin/packages', expect.objectContaining({ code: 'P1', benefits: quyenLoi })); expect(post.mock.calls[0][1]).not.toHaveProperty('branch_id'); expect(post.mock.calls[0][1]).not.toHaveProperty('created_by_id') })
  it('patch metadata va benefits dung endpoint, khong co DELETE', async () => { await capNhatGoiTap(2, { name: 'Moi', status: 'NGUNG_BAN' }); await thayTheQuyenLoiGoiTap(2, quyenLoi); expect(patch).toHaveBeenCalledWith('/admin/packages/2', { name: 'Moi', status: 'NGUNG_BAN' }); expect(put).toHaveBeenCalledWith('/admin/packages/2/benefits', quyenLoi) })
  it('chan AI limit sai truoc khi goi mang', async () => { await expect(taoGoiTap({ code: 'P1', name: 'Goi 1', price: 1, duration_days: 1, status: 'DANG_BAN', benefits: { ...quyenLoi, fitness_assistant: false, fitness_assistant_limit: 3 } })).rejects.toMatchObject({ httpStatus: 422 }); expect(post).not.toHaveBeenCalled() })
})
