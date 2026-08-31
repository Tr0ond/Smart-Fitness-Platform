import axios from 'axios'
import { chuanHoaLoiApi } from '../utils/loi_api.js'

const DIA_CHI_API_PHAT_TRIEN = 'http://127.0.0.1:8000/api'
const CHE_DO_CHO_PHEP_FALLBACK = new Set(['development', 'test'])
const GIA_TRI_PROTOCOL_HOP_LE = new Set(['http:', 'https:'])
const MAY_CHU_NOI_BO = new Set(['localhost', '127.0.0.1', '::1', '[::1]'])

let boDocTokenHienTai = () => null
let xuLy401HienTai = null

/**
 * Kiem tra va chuan hoa dia chi API theo che do chay cua Frontend.
 *
 * Dau vao: giaTriTuMoiTruong la VITE_API_BASE_URL va cheDoChay la mode cua Vite.
 * Cach hoat dong: loai bo khoang trang, kiem tra URL/protocol va chan may chu noi bo
 * trong production/staging; chi cho phep fallback localhost trong development/test.
 * Ket qua: tra ve base URL da chuan hoa hoac nem loi cau hinh co kiem soat.
 * Side effect: khong goi mang va khong doc/ghi credential.
 * Business Rule: production/staging khong duoc am tham fallback localhost.
 */
export function kiemTraCauHinhApi(giaTriTuMoiTruong, cheDoChay = import.meta.env.MODE) {
  const giaTri = typeof giaTriTuMoiTruong === 'string' ? giaTriTuMoiTruong.trim() : ''

  if (giaTri === '') {
    if (CHE_DO_CHO_PHEP_FALLBACK.has(cheDoChay)) {
      return DIA_CHI_API_PHAT_TRIEN
    }

    throw new Error('VITE_API_BASE_URL la bat buoc trong moi truong production/staging.')
  }

  let diaChiApi

  try {
    diaChiApi = new URL(giaTri)
  } catch {
    throw new Error('VITE_API_BASE_URL phai la mot URL hop le.')
  }

  if (!GIA_TRI_PROTOCOL_HOP_LE.has(diaChiApi.protocol) || diaChiApi.username || diaChiApi.password) {
    throw new Error('VITE_API_BASE_URL phai dung giao thuc HTTP/HTTPS va khong chua credential.')
  }

  const tenMayChu = diaChiApi.hostname.toLowerCase()
  const laMayChuNoiBo = MAY_CHU_NOI_BO.has(tenMayChu)

  if (!CHE_DO_CHO_PHEP_FALLBACK.has(cheDoChay) && laMayChuNoiBo) {
    throw new Error('VITE_API_BASE_URL khong duoc tro den may chu noi bo trong production/staging.')
  }

  return diaChiApi.toString().replace(/\/$/, '')
}

/**
 * Lay cau hinh HTTP dung chung tu environment cua Vite.
 *
 * Dau vao: khong co; ham doc duy nhat VITE_API_BASE_URL tu import.meta.env.
 * Cach hoat dong: chuyen gia tri environment qua ham validate theo mode hien tai.
 * Ket qua: tra ve object cau hinh co apiBaseUrl va mode.
 * Side effect: co the nem loi cau hinh truoc khi tao Axios client, khong goi API.
 * Business Rule: chi mot ownership doc VITE_API_BASE_URL va khong fallback localhost o production.
 */
export function layCauHinhMoiTruong() {
  const cheDoChay = import.meta.env.MODE

  return Object.freeze({
    mode: cheDoChay,
    apiBaseUrl: kiemTraCauHinhApi(import.meta.env.VITE_API_BASE_URL, cheDoChay),
  })
}

/**
 * Dang ky bo doc token dung tai thoi diem request ma khong tao phu thuoc vao Pinia.
 *
 * Dau vao: ham khong tham so tra ve Bearer token hien tai hoac null.
 * Cach hoat dong: Axios goi lai callback nay truoc moi request, vi vay login, restore
 * va logout khong bi dung token snapshot luc module duoc nap.
 * Ket qua: cap nhat nguon token cho single Axios client.
 * Side effect: thay doi callback noi bo cua HTTP client; khong luu token.
 * Business Rule: token chi duoc them vao Authorization khi caller da dang ky accessor.
 */
export function datBoDocToken(boDocToken) {
  boDocTokenHienTai = typeof boDocToken === 'function' ? boDocToken : () => null
}

/**
 * Dang ky hook don dep phien khi authenticated request nhan 401.
 *
 * Dau vao: ham xu ly nhan token Bearer snapshot cua request bi 401.
 * Cach hoat dong: response interceptor chi goi hook cho response 401 cua request co Bearer
 * va khong dua snapshot nay vao normalized error tra cho UI.
 * Ket qua: Auth Store co the doi chieu request voi phien hien tai truoc khi don dep.
 * Side effect: hook co the xoa token memory va sessionStorage; khong navigate.
 * Business Rule: late 401 cua token cu khong duoc xoa phien moi; 403/404 khong tu dong logout.
 */
export function datXuLy401(xuLy401) {
  xuLy401HienTai = typeof xuLy401 === 'function' ? xuLy401 : null
}

function layAuthorizationHeader(headers) {
  if (headers === null || typeof headers !== 'object') {
    return null
  }

  if (typeof headers.get === 'function') {
    try {
      const authorization = headers.get('Authorization')

      if (typeof authorization === 'string' && authorization.trim() !== '') {
        return authorization.trim()
      }
    } catch {
      // Fallback sang Object.keys neu headers la AxiosHeaders khong doc duoc.
    }
  }

  const capHeader = Object.entries(headers).find(([tenHeader, giaTri]) => (
    tenHeader.toLowerCase() === 'authorization'
      && typeof giaTri === 'string'
      && giaTri.trim() !== ''
  ))

  return capHeader ? capHeader[1].trim() : null
}

function layBearerToken(headers) {
  const authorization = layAuthorizationHeader(headers)
  const ketQua = typeof authorization === 'string'
    ? authorization.match(/^Bearer\s+(.+)$/i)
    : null

  return typeof ketQua?.[1] === 'string' && ketQua[1].trim() !== ''
    ? ketQua[1].trim()
    : null
}

/**
 * Chen Bearer token vao request theo accessor hien tai.
 *
 * Dau vao: Axios request config truoc khi gui.
 * Cach hoat dong: doc token moi nhat, giu Authorization caller da set va luu snapshot
 * Bearer noi bo tren request de 401 interceptor doi chieu dung phien.
 * Ket qua: tra ve config da duoc chuan bi cho Axios.
 * Side effect: them Authorization vao headers cua request; khong log token.
 * Business Rule: khong tao client thu hai va khong snapshot token luc import module.
 */
function chenBearerToken(config) {
  const token = typeof boDocTokenHienTai === 'function' ? boDocTokenHienTai() : null
  const tokenHienTai = typeof token === 'string' ? token.trim() : ''
  const headers = config.headers ?? {}
  let tokenDaGui = layBearerToken(headers)

  delete config.__tokenBearerDaGui

  if (tokenDaGui === null && tokenHienTai !== '' && layAuthorizationHeader(headers) === null) {
    headers.Authorization = `Bearer ${tokenHienTai}`
    config.headers = headers
    tokenDaGui = tokenHienTai
  }

  if (tokenDaGui !== null) {
    config.__tokenBearerDaGui = tokenDaGui
  }

  return config
}

function xuLyLoiPhanHoi(error) {
  const tokenDaGui = typeof error?.config?.__tokenBearerDaGui === 'string'
    ? error.config.__tokenBearerDaGui
    : layBearerToken(error?.config?.headers)

  if (error?.response?.status === 401 && tokenDaGui !== null && typeof xuLy401HienTai === 'function') {
    try {
      xuLy401HienTai({ tokenDaGui })
    } catch {
      // Cleanup khong duoc che mat loi HTTP chuan hoa cua request.
    }
  }

  return Promise.reject(chuanHoaLoiApi(error))
}

// Chi co mot HTTP client; token/401 hook duoc noi qua callback de tranh circular dependency.
const cauHinhMoiTruong = layCauHinhMoiTruong()
const ketNoiApi = axios.create({
  baseURL: cauHinhMoiTruong.apiBaseUrl,
  timeout: 15000,
  headers: { Accept: 'application/json' },
})

ketNoiApi.interceptors.request.use(chenBearerToken)
ketNoiApi.interceptors.response.use(
  (response) => response,
  xuLyLoiPhanHoi,
)

export default ketNoiApi
