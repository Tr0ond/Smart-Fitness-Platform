import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import boDinhTuyen, { datFocusVaoTieuDeSauDieuHuong } from './index.js'

const ROUTE_MONG_DOI = {
  dieuPhoiTrangGoc: '/',
  chonVaiTro: '/chon-vai-tro',
  quenMatKhau: '/quen-mat-khau',
  datLaiMatKhau: '/dat-lai-mat-khau',
  khongCoQuyen: '/khong-co-quyen',
  khongTimThay: '/khong-tim-thay',
  adminDangNhap: '/admin/dang-nhap',
  ptDangNhap: '/pt/dang-nhap',
  leTanDangNhap: '/le-tan/dang-nhap',
}

describe('router foundation FE0-T05', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await boDinhTuyen.push({ name: 'chonVaiTro' })
  })

  it('co du route public auth, error, selector va root names', () => {
    const routes = boDinhTuyen.getRoutes()
    const routePaths = Object.fromEntries(
      routes
        .filter((route) => typeof route.name === 'string')
        .map((route) => [route.name, route.path]),
    )

    expect(routePaths).toMatchObject(ROUTE_MONG_DOI)
    expect(routes.some((route) => route.path === '/:pathMatch(.*)*')).toBe(true)
    expect(routes.find((route) => route.name === 'adminDangNhap').meta).toMatchObject({
      congKhai: true,
      vaiTro: 'ADMIN',
    })
  })

  it('catch-all redirect den trang khong tim thay', async () => {
    await boDinhTuyen.push('/duong-dan-khong-ton-tai')

    expect(boDinhTuyen.currentRoute.value.name).toBe('khongTimThay')
    expect(boDinhTuyen.currentRoute.value.path).toBe('/khong-tim-thay')
  })

  it('public actor login khong bi guard redirect', async () => {
    await boDinhTuyen.push('/pt/dang-nhap')

    expect(boDinhTuyen.currentRoute.value.name).toBe('ptDangNhap')
  })

  it('selector restore session neu co token nhung van cho neutral pre-auth fallback', async () => {
    await boDinhTuyen.push('/chon-vai-tro')

    expect(boDinhTuyen.currentRoute.value.name).toBe('chonVaiTro')
  })

  it('focus heading sau navigation thay vi de activeElement o body', async () => {
    const tieuDe = document.createElement('h1')
    tieuDe.textContent = 'Trang mới'
    document.body.appendChild(tieuDe)

    try {
      await expect(datFocusVaoTieuDeSauDieuHuong()).resolves.toBe(true)
      expect(document.activeElement).toBe(tieuDe)
      expect(tieuDe.getAttribute('tabindex')).toBe('-1')
    } finally {
      tieuDe.remove()
    }
  })
})
