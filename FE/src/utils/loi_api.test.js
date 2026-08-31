import { describe, expect, it, vi } from 'vitest'
import { chuanHoaLoiApi } from './loi_api.js'

describe('chuanHoaLoiApi', () => {
  it('giu HTTP status va Backend code cho 401/403/404/409', () => {
    expect(chuanHoaLoiApi({ response: { status: 401, data: { message: 'Chưa xác thực.' }, headers: {} } })).toMatchObject({
      httpStatus: 401,
      code: null,
      message: 'Chưa xác thực.',
      isNetworkError: false,
    })
    expect(chuanHoaLoiApi({ response: { status: 403, data: { message: 'Không có quyền truy cập.' }, headers: {} } })).toMatchObject({
      httpStatus: 403,
      code: null,
      message: 'Không có quyền truy cập.',
    })
    expect(chuanHoaLoiApi({ response: { status: 404, data: { message: 'Gói tập không tồn tại.', code: 'PACKAGE_NOT_FOUND' }, headers: {} } })).toMatchObject({
      httpStatus: 404,
      code: 'PACKAGE_NOT_FOUND',
    })
    expect(chuanHoaLoiApi({ response: { status: 409, data: { message: 'Khóa đã được dùng.', code: 'IDEMPOTENCY_CONFLICT' }, headers: {} } })).toMatchObject({
      httpStatus: 409,
      code: 'IDEMPOTENCY_CONFLICT',
      message: 'Khóa đã được dùng.',
    })
  })

  it('map Laravel validation errors cua 422 vao fieldErrors', () => {
    const loi = chuanHoaLoiApi({
      response: {
        status: 422,
        data: {
          message: 'The given data was invalid.',
          errors: {
            email: ['Email không hợp lệ.'],
            password: 'Mật khẩu chưa đạt yêu cầu.',
          },
        },
        headers: {},
      },
    })

    expect(loi).toMatchObject({
      httpStatus: 422,
      code: null,
      message: 'The given data was invalid.',
      fieldErrors: {
        email: ['Email không hợp lệ.'],
        password: ['Mật khẩu chưa đạt yêu cầu.'],
      },
    })
  })

  it('doc Retry-After va request id tu response headers', () => {
    expect(chuanHoaLoiApi({
      response: {
        status: 429,
        data: { message: 'Too Many Attempts.' },
        headers: { 'Retry-After': '60', 'X-Request-Id': 'req-123' },
      },
    })).toMatchObject({
      httpStatus: 429,
      retryAfter: 60,
      originalRequestId: 'req-123',
    })
  })

  it('ho tro Retry-After dang HTTP-date', () => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-09-01T00:00:00.000Z'))

    try {
      expect(chuanHoaLoiApi({
        response: {
          status: 429,
          data: { message: 'Too Many Attempts.' },
          headers: { 'Retry-After': 'Tue, 01 Sep 2026 00:00:30 GMT' },
        },
      })).toMatchObject({ retryAfter: 30 })
    } finally {
      vi.useRealTimers()
    }
  })

  it('giu message controlled cua 5xx va thay message technical bang generic an toan', () => {
    expect(chuanHoaLoiApi({
      response: {
        status: 503,
        data: { message: 'Nhà cung cấp AI tạm thời không khả dụng.', code: 'AI_PROVIDER_UNAVAILABLE' },
        headers: {},
      },
    })).toMatchObject({
      httpStatus: 503,
      code: 'AI_PROVIDER_UNAVAILABLE',
      message: 'Nhà cung cấp AI tạm thời không khả dụng.',
    })

    const loiKyThuat = chuanHoaLoiApi({
      response: {
        status: 500,
        data: {
          message: 'SQLSTATE[HY000]: query failed',
          exception: 'RuntimeException',
          trace: ['secret'],
        },
        headers: {},
      },
    })

    expect(loiKyThuat).toMatchObject({
      httpStatus: 500,
      code: null,
      message: 'Máy chủ đang gặp sự cố. Vui lòng thử lại sau.',
    })
    expect(loiKyThuat).not.toHaveProperty('trace')
    expect(JSON.stringify(loiKyThuat)).not.toContain('SQLSTATE')
  })

  it('phan biet network/timeout va khong lo config hoac token', () => {
    const loi = chuanHoaLoiApi({
      code: 'ECONNABORTED',
      message: 'timeout of 15000ms exceeded',
      config: { headers: { Authorization: 'Bearer secret-token' } },
    })

    expect(loi).toMatchObject({
      httpStatus: null,
      code: null,
      message: 'Không thể kết nối đến máy chủ.',
      isNetworkError: true,
    })
    expect(JSON.stringify(loi)).not.toContain('Authorization')
    expect(JSON.stringify(loi)).not.toContain('secret-token')
  })

  it('khong crash voi response malformed', () => {
    expect(chuanHoaLoiApi({ response: { status: '422', data: null, headers: null } })).toEqual({
      httpStatus: null,
      code: null,
      message: 'Không thể xử lý yêu cầu.',
      fieldErrors: {},
      retryAfter: null,
      isNetworkError: false,
      originalRequestId: null,
    })
  })
})
