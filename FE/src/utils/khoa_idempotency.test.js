import { afterEach, describe, expect, it, vi } from 'vitest'
import { taoKhoaIdempotency } from './khoa_idempotency.js'

describe('taoKhoaIdempotency', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('goi crypto.randomUUID cua runtime', () => {
    const randomUUID = vi.fn(() => '550e8400-e29b-41d4-a716-446655440000')
    vi.stubGlobal('crypto', { randomUUID })

    expect(taoKhoaIdempotency()).toBe('550e8400-e29b-41d4-a716-446655440000')
    expect(randomUUID).toHaveBeenCalledTimes(1)
  })

  it('tra ve gia tri UUID runtime cung cap', () => {
    vi.stubGlobal('crypto', {
      randomUUID: vi.fn(() => 'uuid-action-01'),
    })

    expect(taoKhoaIdempotency()).toBe('uuid-action-01')
  })

  it('khong dung gia tri yeu hon khi crypto.randomUUID khong san sang', () => {
    vi.stubGlobal('crypto', {})

    expect(() => taoKhoaIdempotency()).toThrow('Khong the tao khoa idempotency')
  })

  it('bao loi co kiem soat khi runtime tra ve khoa rong', () => {
    vi.stubGlobal('crypto', {
      randomUUID: vi.fn(() => '   '),
    })

    expect(() => taoKhoaIdempotency()).toThrow('Runtime khong tra ve khoa idempotency')
  })

  it('khong ghi storage va khong goi API', () => {
    const randomUUID = vi.fn(() => 'uuid-side-effect-check')
    vi.stubGlobal('crypto', { randomUUID })

    expect(taoKhoaIdempotency()).toBe('uuid-side-effect-check')
    expect(window.sessionStorage.getItem('smart_fitness.auth.token')).toBeNull()
  })
})
