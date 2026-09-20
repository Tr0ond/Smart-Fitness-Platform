import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const apiMocks = vi.hoisted(() => ({
  taiDanhSachGoiTap: vi.fn(), taiChiTietGoiTap: vi.fn(), taoGoiTap: vi.fn(), capNhatGoiTap: vi.fn(), thayTheQuyenLoiGoiTap: vi.fn(),
  taiDanhSachDungCu: vi.fn(), taoDungCu: vi.fn(), capNhatDungCu: vi.fn(),
  taiDanhSachNhomCo: vi.fn(), taoNhomCo: vi.fn(), capNhatNhomCo: vi.fn(),
  taiDanhSachBaiTap: vi.fn(), taiChiTietBaiTap: vi.fn(), taoBaiTap: vi.fn(), capNhatBaiTap: vi.fn(),
  taiDanhSachGiaoAnMau: vi.fn(), taiChiTietGiaoAnMau: vi.fn(), taoGiaoAnMau: vi.fn(), capNhatGiaoAnMau: vi.fn(), taoPhienBanGiaoAnMau: vi.fn(),
}))
vi.mock('../services/goi_tap.api.js', () => ({ ...apiMocks }))
vi.mock('../services/dung_cu.api.js', () => ({ ...apiMocks }))
vi.mock('../services/nhom_co.api.js', () => ({ ...apiMocks }))
vi.mock('../services/bai_tap.api.js', () => ({ ...apiMocks }))
vi.mock('../services/giao_an_mau.api.js', () => ({ ...apiMocks }))
import { useDanhMucStore, xoaDuLieuDanhMucNeuDaKhoiTao } from './danh_muc.store.js'

beforeEach(() => { setActivePinia(createPinia()); vi.resetAllMocks(); Object.values(apiMocks).forEach((mock) => mock.mockResolvedValue({ data: [] })) })
describe('danh_muc.store FE3', () => {
  it('bo qua response stale khi hai request danh sach chong nhau', async () => {
    let resolveCu
    apiMocks.taiDanhSachDungCu.mockImplementationOnce(() => new Promise((resolve) => { resolveCu = resolve })).mockResolvedValueOnce({ data: [{ id: 2 }] })
    const store = useDanhMucStore()
    const requestCu = store.taiDanhSachDungCu(); const requestMoi = store.taiDanhSachDungCu(); await requestMoi; resolveCu({ data: [{ id: 1 }] }); await requestCu
    expect(store.dungCu.danhSach).toEqual([{ id: 2 }])
  })
  it('cleanup clear toan bo catalog va invalidate sequence', async () => {
    const store = useDanhMucStore(); await store.taiDanhSachGoiTap(); store.goiTap.danhSach = [{ id: 1 }]; store.baiTap.danhSach = [{ id: 2 }]; const before = store.goiTap.soThuTu; expect(xoaDuLieuDanhMucNeuDaKhoiTao()).toBe(true); expect(store.goiTap.danhSach).toEqual([]); expect(store.baiTap.danhSach).toEqual([]); expect(store.goiTap.soThuTu).toBeGreaterThan(before)
  })
  it('mutation network error duoc danh dau outcome unknown va khong blind retry', async () => { apiMocks.taoDungCu.mockRejectedValueOnce({ isNetworkError: true, message: 'offline' }); const store = useDanhMucStore(); await expect(store.taoDungCu({})).resolves.toBeNull(); expect(store.dungCu.loiMutation).toMatchObject({ outcomeUnknown: true }); expect(apiMocks.taoDungCu).toHaveBeenCalledTimes(1) })
})
