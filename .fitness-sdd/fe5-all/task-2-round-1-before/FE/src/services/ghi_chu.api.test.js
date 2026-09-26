import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import { taiDanhSachGhiChu, themGhiChuHuanLuyen } from './ghi_chu.api.js'

describe('ghi_chu.api FE5', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('uses newest-first notes GET and append-only POST allow-list', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: [] })
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { id: 3 } })
    await taiDanhSachGhiChu(7)
    await themGhiChuHuanLuyen(7, { noi_dung: '  Note  ', plan_id: 2, session_id: 4, role: 'ADMIN' })
    expect(get).toHaveBeenCalledWith('/pt/members/7/notes')
    expect(post).toHaveBeenCalledWith('/pt/members/7/notes', {
      content: 'Note', plan_id: 2, session_id: 4,
    })
  })

  it('validates non-empty content and linked ids before non-idempotent POST', async () => {
    const post = vi.spyOn(ketNoiApi, 'post')
    await expect(themGhiChuHuanLuyen(7, { content: '   ' })).rejects.toMatchObject({ httpStatus: 422 })
    await expect(themGhiChuHuanLuyen(7, { content: 'ok', session_id: 0 })).rejects.toMatchObject({ httpStatus: 422 })
    expect(post).not.toHaveBeenCalled()
  })
})
