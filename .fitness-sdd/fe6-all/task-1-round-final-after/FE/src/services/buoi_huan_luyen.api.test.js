import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  hoanTatBuoiHuanLuyen,
  taiLichSuBuoiHuanLuyen,
} from './buoi_huan_luyen.api.js'

const KHOA = '9c87e6ae-69e1-4a15-8330-9848fe48c7d2'

describe('buoi_huan_luyen.api FE6', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('loads the PT-owned latest-100 history and sends only assignment and notes with UUIDv4 header', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: [{ history_id: 30 }] })
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { history_id: 31, replayed: false } })

    await expect(taiLichSuBuoiHuanLuyen()).resolves.toEqual([{ history_id: 30 }])
    await hoanTatBuoiHuanLuyen('71', '  Buổi đã hoàn tất  ', KHOA)

    expect(get).toHaveBeenCalledWith('/pt/direct-sessions/history')
    expect(get.mock.calls[0]).toHaveLength(1)
    expect(post).toHaveBeenCalledWith('/pt/direct-sessions/complete', {
      assignment_id: 71,
      notes: '  Buổi đã hoàn tất  ',
    }, { headers: { 'Idempotency-Key': KHOA } })
  })

  it('does not send client-owned member, trainer, term, quota or completion fields', async () => {
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { data: {} } })
    await hoanTatBuoiHuanLuyen(71, undefined, KHOA)

    expect(post.mock.calls[0][1]).toEqual({ assignment_id: 71 })
    expect(post.mock.calls[0][1]).not.toHaveProperty('member_id')
    expect(post.mock.calls[0][1]).not.toHaveProperty('term_id')
    expect(post.mock.calls[0][1]).not.toHaveProperty('quota')
    expect(post.mock.calls[0][1]).not.toHaveProperty('completed_at')
  })

  it('rejects invalid assignment, notes and non-UUIDv4 keys before network requests', async () => {
    const post = vi.spyOn(ketNoiApi, 'post')
    await expect(hoanTatBuoiHuanLuyen(0, 'note', KHOA)).rejects.toMatchObject({ httpStatus: 422 })
    await expect(hoanTatBuoiHuanLuyen(71, 'x'.repeat(1001), KHOA)).rejects.toMatchObject({ httpStatus: 422 })
    await expect(hoanTatBuoiHuanLuyen(71, 'note', 'stable-but-not-uuid')).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
  })
})
