import { describe, expect, it, vi } from 'vitest'
import {
  dieuPhoiTheoVaiTro,
  dieuPhoiTrangGoc,
  taoBaoVeTuyenDuong,
} from './bao_ve_tuyen_duong.js'

function taoStore(thayDoi = {}) {
  return {
    token: null,
    nguoiDung: null,
    vaiTro: [],
    vaiTroDangDung: null,
    daKhoiPhucPhien: false,
    khoiPhucPhien: vi.fn().mockResolvedValue(false),
    dangXuat: vi.fn(),
    ...thayDoi,
  }
}

describe('bao_ve_tuyen_duong', () => {
  it('chi restore mot lan truoc cac protected decision song song', async () => {
    let tiepTucRestore
    const store = taoStore({
      khoiPhucPhien: vi.fn(() => new Promise((resolve) => {
        tiepTucRestore = resolve
      })),
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()
    const route = { name: 'khuVucBaoVe', meta: { yeuCauXacThuc: true } }

    const lanMot = baoVeTuyenDuong(route, store)
    const lanHai = baoVeTuyenDuong(route, store)
    await Promise.resolve()
    tiepTucRestore(false)

    await expect(Promise.all([lanMot, lanHai])).resolves.toEqual([
      { name: 'chonVaiTro' },
      { name: 'chonVaiTro' },
    ])
    expect(store.khoiPhucPhien).toHaveBeenCalledTimes(1)
  })

  it('chan protected route khi chua xac thuc va khong goi logout', async () => {
    const store = taoStore()
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'khuVucBaoVe', meta: { yeuCauXacThuc: true, vaiTro: 'PT' } },
      store,
    )).resolves.toEqual({ name: 'chonVaiTro' })
    expect(store.dangXuat).not.toHaveBeenCalled()
  })

  it('cho qua role dung va den 403 voi role sai ma khong xoa session', async () => {
    const store = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['PT'],
      vaiTroDangDung: 'PT',
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'ptArea', meta: { yeuCauXacThuc: true, vaiTro: 'PT' } },
      store,
    )).resolves.toBe(true)
    await expect(baoVeTuyenDuong(
      { name: 'adminArea', meta: { yeuCauXacThuc: true, vaiTro: 'ADMIN' } },
      store,
    )).resolves.toEqual({ name: 'khongCoQuyen' })
    expect(store.token).toBe('token')
    expect(store.dangXuat).not.toHaveBeenCalled()
  })

  it('stale actor bi thu hoi se ve selector va khong giu role cu', async () => {
    const store = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['ADMIN'],
      vaiTroDangDung: 'PT',
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'adminArea', meta: { yeuCauXacThuc: true } },
      store,
    )).resolves.toEqual({ name: 'chonVaiTro' })
  })

  it('active actor khac route role se bi tu choi ma khong logout', async () => {
    const store = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['ADMIN', 'PT'],
      vaiTroDangDung: 'PT',
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'adminArea', meta: { yeuCauXacThuc: true, vaiTro: 'ADMIN' } },
      store,
    )).resolves.toEqual({ name: 'khongCoQuyen' })
    expect(store.dangXuat).not.toHaveBeenCalled()
  })

  it('multi-role co role dung nhung chua chon actor van bi dua ve selector', async () => {
    const store = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['ADMIN', 'PT'],
      vaiTroDangDung: null,
      daKhoiPhucPhien: true,
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'adminArea', meta: { yeuCauXacThuc: true, vaiTro: 'ADMIN' } },
      store,
    )).resolves.toEqual({ name: 'chonVaiTro' })
    expect(store.dangXuat).not.toHaveBeenCalled()
  })

  it('root multi-role khong auto-priority va MEMBER-only khong co Web destination', () => {
    const multiRoleStore = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['ADMIN', 'PT'],
    })
    const memberStore = taoStore({
      token: 'token',
      nguoiDung: { id: 2 },
      vaiTro: ['MEMBER'],
    })

    expect(dieuPhoiTrangGoc(multiRoleStore)).toEqual({ name: 'chonVaiTro' })
    expect(multiRoleStore.vaiTroDangDung).toBeNull()
    expect(dieuPhoiTrangGoc(memberStore)).toEqual({ name: 'chonVaiTro' })
  })

  it('single ADMIN va actor ADMIN duoc dua toi Dashboard, khong auto-priority multi-role', () => {
    const adminStore = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['ADMIN'],
    })
    const multiRoleStore = taoStore({
      token: 'token',
      nguoiDung: { id: 2 },
      vaiTro: ['ADMIN', 'PT'],
    })

    expect(dieuPhoiTrangGoc(adminStore)).toEqual({ name: 'adminBangDieuKhien' })
    expect(dieuPhoiTheoVaiTro(multiRoleStore, 'ADMIN')).toEqual({ name: 'adminBangDieuKhien' })
    expect(dieuPhoiTheoVaiTro(multiRoleStore, 'PT')).toEqual({ name: 'ptHoiVien' })
  })

  it('public route khong can restore va root unauthenticated di neutral chooser', async () => {
    const store = taoStore()
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'adminDangNhap', meta: { congKhai: true } },
      store,
    )).resolves.toBe(true)
    expect(store.khoiPhucPhien).not.toHaveBeenCalled()
    await expect(baoVeTuyenDuong(
      { name: 'dieuPhoiTrangGoc', meta: {} },
      store,
    )).resolves.toEqual({ name: 'chonVaiTro' })
  })

  it('session da duoc Auth Store revalidate thi protected decision khong goi restore lan hai', async () => {
    const store = taoStore({
      token: 'token',
      nguoiDung: { id: 1 },
      vaiTro: ['PT'],
      vaiTroDangDung: 'PT',
      daKhoiPhucPhien: true,
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()

    await expect(baoVeTuyenDuong(
      { name: 'ptArea', meta: { yeuCauXacThuc: true, vaiTro: 'PT' } },
      store,
    )).resolves.toBe(true)
    expect(store.khoiPhucPhien).not.toHaveBeenCalled()
  })

  it('cho phep goi lai restore sau khi lan truoc da ket thuc loi tam thoi', async () => {
    const store = taoStore({
      khoiPhucPhien: vi.fn().mockResolvedValue(false),
    })
    const { baoVeTuyenDuong } = taoBaoVeTuyenDuong()
    const route = { name: 'khuVucBaoVe', meta: { yeuCauXacThuc: true } }

    await baoVeTuyenDuong(route, store)
    await baoVeTuyenDuong(route, store)

    expect(store.khoiPhucPhien).toHaveBeenCalledTimes(2)
  })
})
