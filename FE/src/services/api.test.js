import { afterEach, describe, expect, it, vi } from 'vitest'
import ketNoiApi, {
  datBoDocToken,
  datXuLy401,
  kiemTraCauHinhApi,
  layCauHinhMoiTruong,
} from './api.js'

afterEach(() => {
  datBoDocToken(null)
  datXuLy401(null)
})

describe('cau hinh va Axios client', () => {
  it('cho phep fallback localhost chi trong development', () => {
    expect(kiemTraCauHinhApi(undefined, 'development')).toBe('http://127.0.0.1:8000/api')
    expect(kiemTraCauHinhApi('', 'test')).toBe('http://127.0.0.1:8000/api')
  })

  it('fail fast co kiem soat khi production/staging thieu URL', () => {
    expect(() => kiemTraCauHinhApi(undefined, 'production')).toThrow('VITE_API_BASE_URL')
    expect(() => kiemTraCauHinhApi('  ', 'staging')).toThrow('VITE_API_BASE_URL')
  })

  it('tu choi localhost va protocol khong hop le o production', () => {
    expect(() => kiemTraCauHinhApi('http://localhost:8000/api', 'production')).toThrow('may chu noi bo')
    expect(() => kiemTraCauHinhApi('http://127.0.0.1:8000/api', 'staging')).toThrow('may chu noi bo')
    expect(() => kiemTraCauHinhApi('ftp://api.example.com/api', 'production')).toThrow('HTTP/HTTPS')
    expect(() => kiemTraCauHinhApi('not a url', 'production')).toThrow('URL hop le')
  })

  it('chuan hoa URL va lay ownership environment duy nhat', () => {
    expect(kiemTraCauHinhApi(' https://api.example.com/api/ ', 'production')).toBe('https://api.example.com/api')
    expect(layCauHinhMoiTruong().apiBaseUrl).toBe('http://127.0.0.1:8000/api')
  })

  it('tao single Axios client voi baseURL, timeout va Accept baseline', () => {
    expect(ketNoiApi.defaults.baseURL).toBe('http://127.0.0.1:8000/api')
    expect(ketNoiApi.defaults.timeout).toBe(15000)
    expect(ketNoiApi.defaults.headers.Accept).toBe('application/json')
    expect(ketNoiApi.interceptors.response.handlers).toHaveLength(1)
  })

  it('response interceptor reject normalized error thay vi expose Axios error', async () => {
    const interceptor = ketNoiApi.interceptors.response.handlers[0]

    await expect(interceptor.rejected({
      response: {
        status: 409,
        data: { message: 'Xung đột trạng thái.', code: 'IDEMPOTENCY_CONFLICT' },
        headers: {},
      },
      config: { headers: { Authorization: 'Bearer secret-token' } },
    })).rejects.toEqual({
      httpStatus: 409,
      code: 'IDEMPOTENCY_CONFLICT',
      message: 'Xung đột trạng thái.',
      fieldErrors: {},
      retryAfter: null,
      isNetworkError: false,
      originalRequestId: null,
    })
  })

  it('giu Idempotency-Key do caller truyen va khong inject global', async () => {
    const adapter = vi.fn(async (config) => ({
      data: { ok: true },
      status: 200,
      statusText: 'OK',
      headers: {},
      config,
    }))

    await ketNoiApi.post('/action', {}, {
      adapter,
      headers: { 'Idempotency-Key': 'uuid-test' },
    })
    await ketNoiApi.post('/action', {}, { adapter })

    expect(adapter).toHaveBeenCalledTimes(2)
    expect(adapter.mock.calls[0][0].headers['Idempotency-Key']).toBe('uuid-test')
    expect(adapter.mock.calls[1][0].headers['Idempotency-Key']).toBeUndefined()
  })

  it('401 hook nhan Bearer snapshot cua request nhung normalized error khong lo token', async () => {
    const tokenDaGui = 'token-snapshot-cu'
    const xuLy401 = vi.fn()
    datBoDocToken(() => tokenDaGui)
    datXuLy401(xuLy401)
    const adapter = vi.fn(async (config) => Promise.reject({
      config,
      response: { status: 401, data: { message: 'Chưa xác thực.' }, headers: {} },
    }))

    let loiDaChuanHoa

    try {
      await ketNoiApi.get('/protected', { adapter })
    } catch (error) {
      loiDaChuanHoa = error
    }

    expect(xuLy401).toHaveBeenCalledWith({ tokenDaGui })
    expect(loiDaChuanHoa).toMatchObject({ httpStatus: 401 })
    expect(JSON.stringify(loiDaChuanHoa)).not.toContain(tokenDaGui)
  })
})
