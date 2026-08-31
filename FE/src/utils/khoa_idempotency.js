/**
 * Tao khoa UUID cho mot lan bat dau thao tac co the duoc retry an toan.
 *
 * Dau vao: khong co.
 * Cach hoat dong: chi su dung crypto.randomUUID cua runtime hien tai; khong co
 * fallback yeu hon va khong tu dong tao lai khoa khi request gap timeout.
 * Ket qua: tra ve mot UUID string de caller gan vao Idempotency-Key.
 * Side effect: khong ghi storage, khong mutate store va khong goi Backend.
 * Business Rule: retry cung mot logical action phai giu nguyen khoa da tao.
 * Neu runtime khong co API UUID, ham fail co kiem soat de caller dung an toan.
 */
export function taoKhoaIdempotency() {
  if (typeof globalThis.crypto?.randomUUID !== 'function') {
    throw new Error('Khong the tao khoa idempotency trong runtime hien tai.')
  }

  const khoa = globalThis.crypto.randomUUID()

  if (typeof khoa !== 'string' || khoa.trim() === '') {
    throw new Error('Runtime khong tra ve khoa idempotency hop le.')
  }

  return khoa
}
