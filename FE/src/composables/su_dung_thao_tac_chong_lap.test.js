import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('../utils/khoa_idempotency.js', () => ({
  taoKhoaIdempotency: vi.fn(),
}))

import { taoKhoaIdempotency } from '../utils/khoa_idempotency.js'
import { suDungThaoTacChongLap } from './su_dung_thao_tac_chong_lap.js'

describe('suDungThaoTacChongLap', () => {
  beforeEach(() => {
    vi.mocked(taoKhoaIdempotency).mockReset()
    vi.mocked(taoKhoaIdempotency)
      .mockReturnValueOnce('uuid-action-01')
      .mockReturnValueOnce('uuid-action-02')
      .mockReturnValueOnce('uuid-action-03')
  })

  it('tao API lifecycle day du', () => {
    const thaoTac = suDungThaoTacChongLap()

    expect(Object.keys(thaoTac)).toEqual([
      'batDauThaoTac',
      'layKhoaHienTai',
      'ketThucThaoTac',
      'huyThaoTac',
    ])
  })

  it('bat dau voi null truoc khi tao action', () => {
    expect(suDungThaoTacChongLap().layKhoaHienTai()).toBeNull()
    expect(taoKhoaIdempotency).not.toHaveBeenCalled()
  })

  it('lan bat dau dau tien tao mot khoa', () => {
    const thaoTac = suDungThaoTacChongLap()

    expect(thaoTac.batDauThaoTac()).toBe('uuid-action-01')
    expect(thaoTac.layKhoaHienTai()).toBe('uuid-action-01')
    expect(taoKhoaIdempotency).toHaveBeenCalledTimes(1)
  })

  it('lap lai bat dau trong cung action tra lai cung khoa', () => {
    const thaoTac = suDungThaoTacChongLap()

    expect(thaoTac.batDauThaoTac()).toBe('uuid-action-01')
    expect(thaoTac.batDauThaoTac()).toBe('uuid-action-01')
    expect(taoKhoaIdempotency).toHaveBeenCalledTimes(1)
  })

  it('getter khong tao khoa ngam', () => {
    const thaoTac = suDungThaoTacChongLap()

    expect(thaoTac.layKhoaHienTai()).toBeNull()
    expect(thaoTac.layKhoaHienTai()).toBeNull()
    expect(taoKhoaIdempotency).not.toHaveBeenCalled()
  })

  it('giu khoa qua cac lan retry network', () => {
    const thaoTac = suDungThaoTacChongLap()
    const khoaLanDau = thaoTac.batDauThaoTac()

    expect([1, 2, 3].map(() => thaoTac.batDauThaoTac())).toEqual([
      khoaLanDau,
      khoaLanDau,
      khoaLanDau,
    ])
    expect(taoKhoaIdempotency).toHaveBeenCalledTimes(1)
  })

  it('giu khoa qua timeout cho den khi caller ket thuc', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.batDauThaoTac()
    expect(thaoTac.layKhoaHienTai()).toBe('uuid-action-01')
    expect(thaoTac.layKhoaHienTai()).toBe('uuid-action-01')
  })

  it('ket thuc terminal se clear khoa', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.batDauThaoTac()
    thaoTac.ketThucThaoTac()

    expect(thaoTac.layKhoaHienTai()).toBeNull()
  })

  it('huy action se clear khoa', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.batDauThaoTac()
    thaoTac.huyThaoTac()

    expect(thaoTac.layKhoaHienTai()).toBeNull()
  })

  it('action moi sau ket thuc tao khoa moi', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.batDauThaoTac()
    thaoTac.ketThucThaoTac()

    expect(thaoTac.batDauThaoTac()).toBe('uuid-action-02')
    expect(taoKhoaIdempotency).toHaveBeenCalledTimes(2)
  })

  it('action moi sau huy tao khoa moi', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.batDauThaoTac()
    thaoTac.huyThaoTac()

    expect(thaoTac.batDauThaoTac()).toBe('uuid-action-02')
    expect(taoKhoaIdempotency).toHaveBeenCalledTimes(2)
  })

  it('huy khi chua bat dau khong tao UUID', () => {
    const thaoTac = suDungThaoTacChongLap()

    thaoTac.huyThaoTac()

    expect(thaoTac.layKhoaHienTai()).toBeNull()
    expect(taoKhoaIdempotency).not.toHaveBeenCalled()
  })

  it('loi tao khoa khong de lai state dang giu', () => {
    vi.mocked(taoKhoaIdempotency).mockReset().mockImplementation(() => {
      throw new Error('runtime error')
    })
    const thaoTac = suDungThaoTacChongLap()

    expect(() => thaoTac.batDauThaoTac()).toThrow('runtime error')
    expect(thaoTac.layKhoaHienTai()).toBeNull()
  })

  it('hai composable doc lap khong chia se khoa', () => {
    const thaoTacMot = suDungThaoTacChongLap()
    const thaoTacHai = suDungThaoTacChongLap()

    expect(thaoTacMot.batDauThaoTac()).toBe('uuid-action-01')
    expect(thaoTacHai.batDauThaoTac()).toBe('uuid-action-02')
    expect(thaoTacMot.layKhoaHienTai()).not.toBe(thaoTacHai.layKhoaHienTai())
  })
})
