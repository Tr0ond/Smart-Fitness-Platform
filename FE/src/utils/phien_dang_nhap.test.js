import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  KHOA_ACTOR_PHIEN,
  KHOA_TOKEN_PHIEN,
  docTokenPhienDangNhap,
  docVaiTroDangDung,
  luuTokenPhienDangNhap,
  luuVaiTroDangDung,
  xoaDuLieuPhienDangNhap,
  xoaVaiTroDangDung,
} from './phien_dang_nhap.js'

const KHOA_KHONG_LIEN_QUAN = 'smart_fitness.test.unrelated'

function donDepKhoaTest() {
  window.sessionStorage.removeItem(KHOA_TOKEN_PHIEN)
  window.sessionStorage.removeItem(KHOA_ACTOR_PHIEN)
  window.sessionStorage.removeItem(KHOA_KHONG_LIEN_QUAN)
}

describe('helper phien dang nhap', () => {
  beforeEach(() => {
    donDepKhoaTest()
  })

  it('co hai auth key co dinh', () => {
    expect(KHOA_TOKEN_PHIEN).toBe('smart_fitness.auth.token')
    expect(KHOA_ACTOR_PHIEN).toBe('smart_fitness.auth.actor')
  })

  it('luu va doc token da chuan hoa', () => {
    luuTokenPhienDangNhap('  token-01  ')

    expect(docTokenPhienDangNhap()).toBe('token-01')
  })

  it('doc token thieu tra ve null', () => {
    expect(docTokenPhienDangNhap()).toBeNull()
  })

  it('luu va doc actor hop le', () => {
    luuVaiTroDangDung(' PT ')

    expect(docVaiTroDangDung()).toBe('PT')
  })

  it('doc actor rong tra ve null', () => {
    window.sessionStorage.setItem(KHOA_ACTOR_PHIEN, '   ')

    expect(docVaiTroDangDung()).toBeNull()
  })

  it('xoa actor rieng le va giu token', () => {
    luuTokenPhienDangNhap('token-02')
    luuVaiTroDangDung('ADMIN')

    xoaVaiTroDangDung()

    expect(docTokenPhienDangNhap()).toBe('token-02')
    expect(docVaiTroDangDung()).toBeNull()
  })

  it('xoa ca token va actor', () => {
    luuTokenPhienDangNhap('token-03')
    luuVaiTroDangDung('RECEPTIONIST')

    xoaDuLieuPhienDangNhap()

    expect(docTokenPhienDangNhap()).toBeNull()
    expect(docVaiTroDangDung()).toBeNull()
  })

  it('cleanup khong xoa key session khong lien quan', () => {
    window.sessionStorage.setItem(KHOA_KHONG_LIEN_QUAN, 'giu-lai')
    luuTokenPhienDangNhap('token-04')
    luuVaiTroDangDung('PT')

    xoaDuLieuPhienDangNhap()

    expect(window.sessionStorage.getItem(KHOA_KHONG_LIEN_QUAN)).toBe('giu-lai')
  })

  it('tu choi token khong hop le ma khong dua raw value vao loi', () => {
    expect(() => luuTokenPhienDangNhap('  ')).toThrow('Token phien dang nhap khong hop le')
    expect(() => luuTokenPhienDangNhap(null)).toThrow('Token phien dang nhap khong hop le')
  })

  it('tu choi actor khong hop le ma khong dua raw value vao loi', () => {
    expect(() => luuVaiTroDangDung('')).toThrow('Vai tro dang dung khong hop le')
    expect(() => luuVaiTroDangDung({ role: 'PT' })).toThrow('Vai tro dang dung khong hop le')
  })

  it('storage doc loi duoc xu ly thanh null', () => {
    const spy = vi.spyOn(window.sessionStorage, 'getItem').mockImplementation(() => {
      throw new Error('storage unavailable')
    })

    expect(docTokenPhienDangNhap()).toBeNull()
    expect(docVaiTroDangDung()).toBeNull()
    spy.mockRestore()
  })

  it('storage ghi loi duoc bao bang loi an toan', () => {
    const token = 'token-not-in-error'
    const spy = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('quota')
    })

    expect(() => luuTokenPhienDangNhap(token)).toThrow('Khong the luu phien dang nhap')
    expect(() => luuTokenPhienDangNhap(token)).not.toThrow(token)
    spy.mockRestore()
  })

  it('storage remove loi khong lam cleanup bi vo', () => {
    const spy = vi.spyOn(window.sessionStorage, 'removeItem').mockImplementation(() => {
      throw new Error('storage unavailable')
    })

    expect(() => xoaDuLieuPhienDangNhap()).not.toThrow()
    spy.mockRestore()
  })

  it('khong tao key ngoai auth khi luu session', () => {
    luuTokenPhienDangNhap('token-05')
    luuVaiTroDangDung('PT')

    expect(window.sessionStorage.getItem('smart_fitness.auth.password')).toBeNull()
    expect(window.sessionStorage.getItem('smart_fitness.auth.reset')).toBeNull()
  })

  it('khong doc ghi hoac xoa localStorage', () => {
    const doc = vi.spyOn(window.localStorage, 'getItem')
    const ghi = vi.spyOn(window.localStorage, 'setItem')
    const xoa = vi.spyOn(window.localStorage, 'removeItem')

    luuTokenPhienDangNhap('token-06')
    docTokenPhienDangNhap()
    xoaDuLieuPhienDangNhap()

    expect(doc).not.toHaveBeenCalled()
    expect(ghi).not.toHaveBeenCalled()
    expect(xoa).not.toHaveBeenCalled()
    doc.mockRestore()
    ghi.mockRestore()
    xoa.mockRestore()
  })
})
