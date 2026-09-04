import ketNoiApi from './api.js'

/**
 * Tai tong quan Dashboard Admin theo bo loc ngay tuy chon.
 *
 * Dau vao: boLoc co the rong, hoac co dong thoi from/to theo dinh dang YYYY-MM-DD.
 * Cach hoat dong: chi tao query allow-list `from` va `to` khi ca cap cung co gia tri,
 * sau do goi GET /admin/dashboard qua Axios client duy nhat.
 * Ket qua: tra nguyen body response Backend, gom data.branch, data.period va cac nhom count.
 * Side effect: chi phat sinh mot HTTP GET read-only; khong mutation, khong idempotency key,
 * khong chon branch/timezone, khong tinh revenue hay metric phat sinh o Frontend.
 * Business Rule: Backend la authority cho branch, timezone, default period va validation.
 */
export async function taiTongQuanAdmin(boLoc = {}) {
  const tuNgay = typeof boLoc?.from === 'string' ? boLoc.from.trim() : ''
  const denNgay = typeof boLoc?.to === 'string' ? boLoc.to.trim() : ''

  if ((tuNgay === '') !== (denNgay === '')) {
    const loi = new Error('Từ ngày và Đến ngày phải được chọn cùng nhau.')
    loi.code = 'INVALID_DASHBOARD_PERIOD'
    throw loi
  }

  if (tuNgay === '' && denNgay === '') {
    const phanHoi = await ketNoiApi.get('/admin/dashboard')
    return phanHoi.data
  }

  const phanHoi = await ketNoiApi.get('/admin/dashboard', {
    params: {
      from: tuNgay,
      to: denNgay,
    },
  })

  return phanHoi.data
}
