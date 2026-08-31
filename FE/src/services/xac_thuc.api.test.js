import { beforeEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi from './api.js'
import {
  dangNhap,
  dangXuat,
  datLaiMatKhau,
  guiYeuCauDatLaiMatKhau,
  taiThongTinNguoiDung,
} from './xac_thuc.api.js'

describe('xac_thuc.api', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('gui login payload dung contract va tra body Backend', async () => {
    const phanHoi = {
      data: {
        data: {
          access_token: 'token-login',
          token_type: 'Bearer',
          expires_at: '2026-09-30T00:00:00.000000Z',
          user: { id: 10, roles: ['PT'] },
        },
      },
    }
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue(phanHoi)

    await expect(dangNhap({
      email: 'pt@example.com',
      password: 'MatKhau!123',
      device_name: 'Chrome staff',
      role: 'ADMIN',
    })).resolves.toBe(phanHoi.data)

    expect(post).toHaveBeenCalledWith('/auth/login', {
      email: 'pt@example.com',
      password: 'MatKhau!123',
      device_name: 'Chrome staff',
    })
  })

  it('goi /auth/me theo endpoint Backend va giu nguyen body', async () => {
    const phanHoi = { data: { data: { id: 11, name: 'PT', email: 'pt@example.com', status: 'HOAT_DONG', roles: ['PT'] } } }
    const get = vi.spyOn(ketNoiApi, 'get').mockResolvedValue(phanHoi)

    await expect(taiThongTinNguoiDung()).resolves.toBe(phanHoi.data)
    expect(get).toHaveBeenCalledWith('/auth/me')
  })

  it('goi logout current token va khong chua payload ngoai contract', async () => {
    const phanHoi = { data: { data: null, message: 'Đăng xuất thành công.' } }
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue(phanHoi)

    await expect(dangXuat()).resolves.toBe(phanHoi.data)
    expect(post).toHaveBeenCalledWith('/auth/logout')
  })

  it('giu nguyen normalized error tu Axios client', async () => {
    const loi = {
      httpStatus: 401,
      code: null,
      message: 'Chưa xác thực.',
      fieldErrors: {},
      retryAfter: null,
      isNetworkError: false,
      originalRequestId: null,
    }
    vi.spyOn(ketNoiApi, 'get').mockRejectedValue(loi)

    await expect(taiThongTinNguoiDung()).rejects.toBe(loi)
  })

  it('gui forgot-password dung duy nhat field email', async () => {
    const phanHoi = { data: { data: null, message: 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu sẽ được gửi.' } }
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue(phanHoi)

    await expect(guiYeuCauDatLaiMatKhau('user@example.com')).resolves.toBe(phanHoi.data)
    expect(post).toHaveBeenCalledWith('/auth/forgot-password', { email: 'user@example.com' })
  })

  it('gui reset-password dung exact token/password/password_confirmation va khong them field', async () => {
    const phanHoi = { data: { data: null, message: 'Đặt lại mật khẩu thành công.' } }
    const post = vi.spyOn(ketNoiApi, 'post').mockResolvedValue(phanHoi)

    await expect(datLaiMatKhau({
      token: 'a'.repeat(64),
      password: 'NewPassword!456',
      password_confirmation: 'NewPassword!456',
      email: 'must-not-be-sent@example.com',
    })).resolves.toBe(phanHoi.data)

    expect(post).toHaveBeenCalledWith('/auth/reset-password', {
      token: 'a'.repeat(64),
      password: 'NewPassword!456',
      password_confirmation: 'NewPassword!456',
    })
  })
})
