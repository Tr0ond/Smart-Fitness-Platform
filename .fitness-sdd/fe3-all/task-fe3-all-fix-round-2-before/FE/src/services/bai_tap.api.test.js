import { beforeEach, describe, expect, it, vi } from 'vitest'
const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import { capNhatBaiTap, taoBaiTap, taoPayloadQuanHeBaiTap, taiDanhSachBaiTap } from './bai_tap.api.js'
beforeEach(() => { vi.clearAllMocks(); get.mockResolvedValue({ data: { data: [] } }); post.mockResolvedValue({ data: { data: { id: 1 } } }); patch.mockResolvedValue({ data: { data: { id: 1 } } }) })
describe('bai_tap.api FE3', () => {
  it('tao quan he equipment va muscle voi vai tro', () => { expect(taoPayloadQuanHeBaiTap({ equipmentIds: [1, '2'], muscleGroups: [{ id: 4, role: 'CHINH' }] })).toEqual({ equipment_ids: [1, 2], muscle_groups: [{ id: 4, role: 'CHINH' }] }) })
  it('gui filters va relation arrays, khong co authority', async () => { await taiDanhSachBaiTap({ search: 'squat', status: 'HOAT_DONG', branch_id: 8 }); await taoBaiTap({ code: 'SQ', name: 'Squat', difficulty: 'D', status: 'HOAT_DONG', equipment_ids: [1], muscle_groups: [{ id: 2, role: 'CHINH' }], created_by_id: 4 }); await capNhatBaiTap(2, { name: 'Moi', equipment_ids: [], muscle_groups: [] }); expect(get).toHaveBeenCalledWith('/admin/exercises', { params: { search: 'squat', status: 'HOAT_DONG' } }); expect(post.mock.calls[0][1]).not.toHaveProperty('created_by_id'); expect(patch).toHaveBeenCalledWith('/admin/exercises/2', expect.objectContaining({ equipment_ids: [], muscle_groups: [] })) })
})
