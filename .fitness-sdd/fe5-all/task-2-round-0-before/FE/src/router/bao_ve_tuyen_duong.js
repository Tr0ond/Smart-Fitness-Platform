export const CAC_VAI_TRO_WEB = Object.freeze(['ADMIN', 'PT', 'RECEPTIONIST'])

const DIEM_DEN_ACTOR_DA_DANG_KY = Object.freeze({
  ADMIN: 'adminBangDieuKhien',
})

function laVaiTroWeb(vaiTro) {
  return CAC_VAI_TRO_WEB.includes(vaiTro)
}

function layVaiTroWeb(store) {
  return Array.isArray(store?.vaiTro)
    ? store.vaiTro.filter((vaiTro) => laVaiTroWeb(vaiTro))
    : []
}

function daXacThuc(store) {
  return typeof store?.token === 'string'
    && store.token.trim() !== ''
    && store.nguoiDung !== null
    && typeof store.nguoiDung === 'object'
}

/**
 * Dieu phoi root theo session va Web roles hien tai.
 *
 * Dau vao: Auth Store da hoan tat khoi phuc phien.
 * Cach hoat dong: khong tao role uu tien; nhieu role di toi selector, mot role chi di home neu home da dang ky.
 * Ket qua: route location noi bo, hoac selector trung lap cho trang thai chua co home.
 * Side effect: khong goi API, khong chon actor va khong tu dong tao dashboard.
 * Business Rule: MEMBER khong phai Web actor; unauthenticated root chi duoc vao neutral role chooser.
 */
export function dieuPhoiTrangGoc(store) {
  const danhSachVaiTroWeb = layVaiTroWeb(store)

  if (!daXacThuc(store) || danhSachVaiTroWeb.length !== 1) {
    return { name: 'chonVaiTro' }
  }

  const diemDenActor = DIEM_DEN_ACTOR_DA_DANG_KY[danhSachVaiTroWeb[0]]

  return diemDenActor === undefined ? { name: 'chonVaiTro' } : { name: diemDenActor }
}

/**
 * Bao ve route theo metadata UX, trong khi Backend van la authority phan quyen.
 *
 * Dau vao: route dich co meta congKhai/yeuCauXacThuc/vaiTro va Auth Store hien tai.
 * Cach hoat dong: restore mot lan truoc quyet dinh protected, kiem tra session/role/actor roi tra route location.
 * Ket qua: true de cho qua, hoac redirect noi bo den chooser/403; khong nhan raw redirect tu URL.
 * Side effect: co the goi Store khoi phuc phien; khong logout khi sai role va khong retry request.
 * Business Rule: guard chi la UX gate, khong thay the Backend authorization; route cong khai luon khong bi chan.
 */
export function taoBaoVeTuyenDuong() {
  let promiseKhoiPhuc = null

  async function khoiPhucMotLan(store) {
    if (store.daKhoiPhucPhien === true) {
      return true
    }

    if (promiseKhoiPhuc === null) {
      promiseKhoiPhuc = Promise.resolve()
        .then(() => store.khoiPhucPhien())
        .catch(() => false)
        .finally(() => {
          promiseKhoiPhuc = null
        })
    }

    return promiseKhoiPhuc
  }

  async function baoVeTuyenDuong(route, store) {
    const laRoot = route?.name === 'dieuPhoiTrangGoc'
    const laCongKhai = route?.meta?.congKhai === true
    const canKhoiPhuc = laRoot || route?.meta?.yeuCauXacThuc === true

    if (!laRoot && laCongKhai) {
      return true
    }

    if (canKhoiPhuc) {
      await khoiPhucMotLan(store)
    }

    if (laRoot) {
      return dieuPhoiTrangGoc(store)
    }

    if (route?.meta?.yeuCauXacThuc !== true) {
      return true
    }

    if (!daXacThuc(store)) {
      return route?.name === 'chonVaiTro' ? true : { name: 'chonVaiTro' }
    }

    const vaiTroYeuCau = route.meta.vaiTro
    const danhSachVaiTroYeuCau = typeof vaiTroYeuCau === 'string'
      ? [vaiTroYeuCau]
      : Array.isArray(vaiTroYeuCau) ? vaiTroYeuCau : []
    const coVaiTroDuocPhep = danhSachVaiTroYeuCau.length === 0
      || danhSachVaiTroYeuCau.some((vaiTro) => layVaiTroWeb(store).includes(vaiTro))

    if (!coVaiTroDuocPhep) {
      return { name: 'khongCoQuyen' }
    }

    const danhSachVaiTroWeb = layVaiTroWeb(store)

    if (danhSachVaiTroYeuCau.length > 0) {
      if (!laVaiTroWeb(store.vaiTroDangDung) || !danhSachVaiTroWeb.includes(store.vaiTroDangDung)) {
        return { name: 'chonVaiTro' }
      }

      if (!danhSachVaiTroYeuCau.includes(store.vaiTroDangDung)) {
        return { name: 'khongCoQuyen' }
      }
    } else if (store.vaiTroDangDung !== null && !danhSachVaiTroWeb.includes(store.vaiTroDangDung)) {
      return { name: 'chonVaiTro' }
    }

    return true
  }

  return { baoVeTuyenDuong, khoiPhucMotLan }
}

/**
 * Dieu phoi sau khi nguoi dung chon actor.
 *
 * Dau vao: Auth Store, actor da chon va router; actor phai nam trong Web roles hien tai.
 * Cach hoat dong: tra diem den da dang ky neu co; neu chua co thi giu o selector de khong tao fake business page.
 * Ket qua: route location noi bo hoac null de UI hien trang thai da chon.
 * Side effect: khong goi API va khong thay doi role authority.
 * Business Rule: khong priority role va khong coi MEMBER la actor Web.
 */
export function dieuPhoiTheoVaiTro(store, vaiTro) {
  if (!layVaiTroWeb(store).includes(vaiTro)) {
    return { name: 'khongCoQuyen' }
  }

  const diemDenActor = DIEM_DEN_ACTOR_DA_DANG_KY[vaiTro]

  return diemDenActor === undefined ? null : { name: diemDenActor }
}
