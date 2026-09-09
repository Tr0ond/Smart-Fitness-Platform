import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  ketThucPhanCong,
  phanCongLai,
  taiDanhSachPhanCong,
  taoPhanCong,
} from './phan_cong_pt.api.js'

describe('phan_cong_pt.api FE2-T06/T07', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('gui dung read filters va assignment profile ids', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { items: [], pagination: {} } })
    await taiDanhSachPhanCong({ member_id: '7', trainer_id: 11, current: false, page: 2, per_page: 25, branch_id: 3 })
    expect(get).toHaveBeenCalledWith('/pt/assignments', {
      params: { member_id: 7, trainer_id: 11, current: false, page: 2, per_page: 25 },
    })
  })

  it('mutation khong gui Idempotency-Key va khong gui truong authority', async () => {
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { id: 1 } })
    const patch = vi.spyOn(ketNoiApi, 'patch').mockResolvedValue({ data: { id: 1 } })
    await taoPhanCong({ member_id: 7, trainer_id: 11, start_at: '2026-09-07T10:00:00.000Z', actor_id: 9 })
    await ketThucPhanCong(1, { reason: 'Done', end_at: 'bad' })
    await phanCongLai(1, { trainer_id: 12, start_at: '2026-09-07T11:00:00.000Z', branch_id: 2 })
    expect(post).toHaveBeenCalledWith('/pt/assignments', {
      member_id: 7, trainer_id: 11, start_at: '2026-09-07T10:00:00.000Z',
    })
    expect(patch).toHaveBeenCalledWith('/pt/assignments/1/end', { reason: 'Done' })
    expect(post).toHaveBeenNthCalledWith(2, '/pt/assignments/1/reassign', {
      trainer_id: 12, start_at: '2026-09-07T11:00:00.000Z',
    })
    expect(patch.mock.calls[0][2]).toBeUndefined()
  })

  it('chan id profile khong hop le truoc request', async () => {
    const post = vi.spyOn(ketNoiApi, 'post')
    await expect(taoPhanCong({ member_id: 0, trainer_id: 11 })).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
  })
})
