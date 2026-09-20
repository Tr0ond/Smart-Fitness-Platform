import { beforeEach, describe, expect, it, vi } from 'vitest'
const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import { capNhatDungCu, taoDungCu } from './dung_cu.api.js'
beforeEach(() => { vi.clearAllMocks(); get.mockResolvedValue({ data: { data: [] } }); post.mockResolvedValue({ data: { data: { id: 1 } } }); patch.mockResolvedValue({ data: { data: { id: 1 } } }) })
describe('dung_cu.api FE3', () => {
  it('create va patch equipment dung endpoint', async () => { await taoDungCu({ code: 'DB', name: 'Dumbbell', description: '', status: 'HOAT_DONG' }); await capNhatDungCu(3, { name: 'Moi', status: 'NGUNG_SU_DUNG' }); expect(post).toHaveBeenCalledWith('/admin/equipment', { code: 'DB', name: 'Dumbbell', description: '', status: 'HOAT_DONG' }); expect(patch).toHaveBeenCalledWith('/admin/equipment/3', { name: 'Moi', status: 'NGUNG_SU_DUNG' }) })
  it('khong cho sua code trong patch', async () => { await capNhatDungCu(3, { code: 'KHAC', name: 'Moi', status: 'HOAT_DONG' }); expect(patch.mock.calls[0][1]).not.toHaveProperty('code') })
})
