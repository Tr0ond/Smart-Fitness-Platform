import { describe, expect, it, vi } from 'vitest'
import {
  CAC_TEN_TUYEN_DUONG_NOI_BO_CHO_PHEP,
  laDuongDanNoiBoHopLe,
} from './duong_dan_an_toan.js'

const DANH_SACH_FIXTURE = ['adminHome', 'ptWorkspace']

describe('laDuongDanNoiBoHopLe', () => {
  it('chap nhan route name fixture duoc allow-list', () => {
    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE)).toBe(true)
  })

  it('chap nhan allow-list dang Set', () => {
    expect(laDuongDanNoiBoHopLe('ptWorkspace', new Set(DANH_SACH_FIXTURE))).toBe(true)
  })

  it('co allow-list noi bo mac dinh ro rang', () => {
    expect(CAC_TEN_TUYEN_DUONG_NOI_BO_CHO_PHEP).toContain('chonVaiTro')
    expect(laDuongDanNoiBoHopLe('chonVaiTro')).toBe(true)
  })

  it.each([
    'http://evil.example',
    'https://evil.example',
    '//evil.example',
    '\\evil.example',
    'javascript:alert(1)',
    'data:text/html,evil',
    'mailto:evil@example.com',
    'ftp://evil.example/file',
  ])('tu choi redirect nguy hiem %s', (giaTri) => {
    expect(laDuongDanNoiBoHopLe(giaTri, [giaTri])).toBe(false)
  })

  it.each(['', '   ', null, undefined, [], {}, 'unknownRoute', '/adminHome', 'adminHome?x=1'])(
    'tu choi input/route khong hop le %s',
    (giaTri) => {
      expect(laDuongDanNoiBoHopLe(giaTri, DANH_SACH_FIXTURE)).toBe(false)
    },
  )

  it.each(['%2F%2Fevil.example', 'adminHome%2F..%2Fevil', 'adminHome\\evil'])(
    'tu choi input encoded hoac backslash trick %s',
    (giaTri) => {
      expect(laDuongDanNoiBoHopLe(giaTri, [giaTri])).toBe(false)
    },
  )

  it('tu choi ten khong co trong allow-list du router co route', () => {
    const router = {
      hasRoute: vi.fn(() => true),
    }

    expect(laDuongDanNoiBoHopLe('unknownRoute', DANH_SACH_FIXTURE, router)).toBe(false)
    expect(router.hasRoute).not.toHaveBeenCalled()
  })

  it('kiem tra route ton tai khi router cung cap hasRoute', () => {
    const router = {
      hasRoute: vi.fn((tenTuyenDuong) => tenTuyenDuong === 'adminHome'),
    }

    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE, router)).toBe(true)
    expect(laDuongDanNoiBoHopLe('ptWorkspace', DANH_SACH_FIXTURE, router)).toBe(false)
  })

  it('kiem tra resolve neu router khong co hasRoute', () => {
    const router = {
      resolve: vi.fn(() => ({ matched: [{}] })),
    }

    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE, router)).toBe(true)
    expect(router.resolve).toHaveBeenCalledWith({ name: 'adminHome' })
  })

  it('fail closed khi router khong co API xac minh route', () => {
    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE, {})).toBe(false)
  })

  it('fail closed khi router resolve nem loi', () => {
    const router = {
      resolve: vi.fn(() => {
        throw new Error('unknown route')
      }),
    }

    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE, router)).toBe(false)
  })

  it('khong dung getRoutes lam allow-list', () => {
    const router = {
      getRoutes: vi.fn(() => [{ name: 'adminHome' }]),
    }

    expect(laDuongDanNoiBoHopLe('adminHome', DANH_SACH_FIXTURE, router)).toBe(false)
    expect(router.getRoutes).not.toHaveBeenCalled()
  })
})
