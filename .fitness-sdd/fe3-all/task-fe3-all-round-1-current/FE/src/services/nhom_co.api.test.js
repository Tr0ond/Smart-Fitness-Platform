import { beforeEach, describe, expect, it, vi } from 'vitest'
const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import { capNhatNhomCo, taoNhomCo } from './nhom_co.api.js'
beforeEach(() => { vi.clearAllMocks(); get.mockResolvedValue({ data: { data: [] } }); post.mockResolvedValue({ data: { data: { id: 1 } } }); patch.mockResolvedValue({ data: { data: { id: 1 } } }) })
describe('nhom_co.api FE3', () => {
  it('hien inactive va gui status transition theo Backend', async () => { await taoNhomCo({ code: 'NG', name: 'Nguc', description: '', status: 'HOAT_DONG' }); await capNhatNhomCo(4, { status: 'NGUNG_SU_DUNG' }); expect(post).toHaveBeenCalledWith('/admin/muscle-groups', expect.objectContaining({ status: 'HOAT_DONG' })); expect(patch).toHaveBeenCalledWith('/admin/muscle-groups/4', { status: 'NGUNG_SU_DUNG' }) })
  it('chan ID am va khong goi API', async () => { await expect(capNhatNhomCo(-1, { status: 'HOAT_DONG' })).rejects.toMatchObject({ httpStatus: 422 }); expect(patch).not.toHaveBeenCalled() })
})
