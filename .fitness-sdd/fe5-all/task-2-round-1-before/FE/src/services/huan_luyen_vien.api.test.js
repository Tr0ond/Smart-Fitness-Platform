import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  capNhatHoSoCaNhanHuanLuyenVien,
  capNhatHoSoHuanLuyenVien,
  onboardTaiKhoanHuanLuyenVien,
  taiHoSoCaNhanHuanLuyenVien,
  taiHoSoHuanLuyenVien,
  taoHuanLuyenVien,
} from './huan_luyen_vien.api.js'

const KEY = '00000000-0000-4000-8000-000000000001'

describe('huan_luyen_vien.api FE2-T02/T04', () => {
  beforeEach(() => vi.restoreAllMocks())

  it('gui body/header allow-list cho account moi', async () => {
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { invitation: 'QUEUED' } })

    await taoHuanLuyenVien({
      name: 'PT A', email: 'pt@example.com', phone: '0900', introduction: 'Intro',
      specialties: 'Strength', status: 'HOAT_DONG', actor: 99, password: 'secret',
    }, KEY)

    expect(post).toHaveBeenCalledWith('/admin/trainers', {
      name: 'PT A', email: 'pt@example.com', phone: '0900', introduction: 'Intro',
      specialties: 'Strength', status: 'HOAT_DONG',
    }, { headers: { 'Idempotency-Key': KEY } })
  })

  it('dung GET profile va POST edit cung key', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { account_id: 7 } })
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue({ data: { account_id: 7 } })

    await taiHoSoHuanLuyenVien(7)
    await onboardTaiKhoanHuanLuyenVien(7, { introduction: 'A', status: 'NGUNG_NHAN_PHAN_CONG' }, KEY)
    await capNhatHoSoHuanLuyenVien(7, { specialties: 'B' }, KEY)

    expect(get).toHaveBeenCalledWith('/admin/accounts/7/trainer-profile')
    expect(post).toHaveBeenNthCalledWith(1, '/admin/accounts/7/trainer-profile', {
      introduction: 'A', status: 'NGUNG_NHAN_PHAN_CONG',
    }, { headers: { 'Idempotency-Key': KEY } })
    expect(post).toHaveBeenNthCalledWith(2, '/admin/accounts/7/trainer-profile', {
      specialties: 'B',
    }, { headers: { 'Idempotency-Key': KEY } })
  })

  it('chan id va status khong hop le truoc request', async () => {
    const get = vi.spyOn(ketNoiApi, 'get')
    await expect(taiHoSoHuanLuyenVien(0)).rejects.toMatchObject({ httpStatus: 404 })
    await expect(onboardTaiKhoanHuanLuyenVien(7, { status: 'UNKNOWN' }, KEY))
      .rejects.toMatchObject({ httpStatus: 422 })
    expect(get).not.toHaveBeenCalled()
  })

  it('self profile chi dung endpoint self va allow-list hai truong mo ta', async () => {
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue({ data: { id: 11 } })
    const patch = vi.spyOn(ketNoiApi, 'patch').mockResolvedValue({ data: { id: 11 } })

    await taiHoSoCaNhanHuanLuyenVien()
    await capNhatHoSoCaNhanHuanLuyenVien({
      gioi_thieu: '  Gioi thieu  ',
      specialties: ' Strength ',
      status: 'HOAT_DONG',
      role: 'ADMIN',
      branch_id: 9,
    })

    expect(get).toHaveBeenCalledWith('/profile/trainer')
    expect(patch).toHaveBeenCalledWith('/profile/trainer', {
      introduction: 'Gioi thieu',
      specialties: 'Strength',
    })
  })

  it('self profile chan authority field sai kieu truoc PATCH', async () => {
    const patch = vi.spyOn(ketNoiApi, 'patch')
    await expect(capNhatHoSoCaNhanHuanLuyenVien({ introduction: 123 })).rejects.toMatchObject({
      httpStatus: 422,
      fieldErrors: { introduction: expect.any(Array) },
    })
    expect(patch).not.toHaveBeenCalled()
  })
})
