import { beforeEach, describe, expect, it, vi } from 'vitest'

const { get, post, patch } = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn() }))
vi.mock('./api.js', () => ({ default: { get, post, patch } }))
import {
  capNhatBaiTap,
  taiChiTietBaiTap,
  taoBaiTap,
  taoPayloadQuanHeBaiTap,
  taiDanhSachBaiTap,
} from './bai_tap.api.js'

const base = {
  code: 'SQ',
  name: 'Squat',
  difficulty: 'Beginner',
  instructions: 'Giữ lưng thẳng.',
  status: 'HOAT_DONG',
  equipment_ids: [1],
  muscle_groups: [{ id: 2, role: 'CHINH' }],
}

beforeEach(() => {
  vi.clearAllMocks()
  get.mockResolvedValue({ data: { data: [] } })
  post.mockResolvedValue({ data: { data: { id: 1 } } })
  patch.mockResolvedValue({ data: { data: { id: 1 } } })
})

describe('bai_tap.api FE3', () => {
  it('tao quan he equipment va muscle voi vai tro', () => {
    expect(taoPayloadQuanHeBaiTap({ equipmentIds: [1, '2'], muscleGroups: [{ id: 4, role: 'CHINH' }] })).toEqual({
      equipment_ids: [1, 2],
      muscle_groups: [{ id: 4, role: 'CHINH' }],
    })
  })

  it('gui filters/detail va relation arrays, khong co authority', async () => {
    await taiDanhSachBaiTap({ search: 'squat', status: 'HOAT_DONG', branch_id: 8 })
    await taiChiTietBaiTap('2')
    await taoBaiTap({ ...base, created_by_id: 4 })
    await capNhatBaiTap(2, { name: 'Moi', equipment_ids: [], muscle_groups: [] })
    expect(get).toHaveBeenNthCalledWith(1, '/admin/exercises', { params: { search: 'squat', status: 'HOAT_DONG' } })
    expect(get).toHaveBeenNthCalledWith(2, '/admin/exercises/2')
    expect(post.mock.calls[0][1]).not.toHaveProperty('created_by_id')
    expect(patch).toHaveBeenCalledWith('/admin/exercises/2', expect.objectContaining({ equipment_ids: [], muscle_groups: [] }))
  })

  it('giữ nguyên nullable image/video trong create va update', async () => {
    await taoBaiTap({ ...base, image_path: null, video_path: null })
    await capNhatBaiTap(1, { image_path: null, video_path: null })
    expect(post.mock.calls[0][1]).toEqual(expect.objectContaining({ image_path: null, video_path: null }))
    expect(patch.mock.calls[0][1]).toEqual({ image_path: null, video_path: null })
  })

  it('tao bai tap chan khi thieu instructions truoc network', async () => {
    const payload = { ...base }
    delete payload.instructions

    await expect(taoBaiTap(payload)).rejects.toMatchObject({
      httpStatus: 422,
      code: 'INVALID_EXERCISE_REQUEST',
      fieldErrors: { instructions: ['Giá trị không hợp lệ.'] },
    })
    expect(post).not.toHaveBeenCalled()
  })

  it('instructions required: null/blank/object bị chặn trước network', async () => {
    await expect(taoBaiTap({ ...base, instructions: null })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoBaiTap({ ...base, instructions: '   ' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(taoBaiTap({ ...base, instructions: {} })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(capNhatBaiTap(1, { instructions: null })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(capNhatBaiTap(1, { instructions: '   ' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(capNhatBaiTap(1, { instructions: {} })).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
    expect(patch).not.toHaveBeenCalled()
  })

  it('partial update cho phép bỏ qua instructions và giữ PATCH hợp lệ khi có giá trị', async () => {
    await capNhatBaiTap(1, { name: 'Moi' })
    await capNhatBaiTap(1, { instructions: 'Hướng dẫn mới.' })
    expect(patch).toHaveBeenNthCalledWith(1, '/admin/exercises/1', { name: 'Moi' })
    expect(patch).toHaveBeenNthCalledWith(2, '/admin/exercises/1', { instructions: 'Hướng dẫn mới.' })
  })

  it('chặn relation duplicate/role sai va giữ semantics AND', async () => {
    expect(() => taoPayloadQuanHeBaiTap({ equipmentIds: [1, 1], muscleGroups: [] })).toThrow()
    expect(() => taoPayloadQuanHeBaiTap({ equipmentIds: [], muscleGroups: [{ id: 1, role: 'SAI' }] })).toThrow()
    await taoBaiTap({ ...base, equipment_ids: [1, 2] })
    expect(post.mock.calls[0][1].equipment_ids).toEqual([1, 2])
  })
})
