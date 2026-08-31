<script setup>
import { computed } from 'vue'

const props = defineProps({
  trangThai: {
    type: String,
    default: 'trung_tinh',
  },
  nhan: {
    type: String,
    default: '',
  },
})

const KIEU_TRANG_THAI = Object.freeze({
  trung_tinh: 'trung-tinh',
  neutral: 'trung-tinh',
  thanh_cong: 'thanh-cong',
  success: 'thanh-cong',
  canh_bao: 'canh-bao',
  warning: 'canh-bao',
  nguy_hiem: 'nguy-hiem',
  danger: 'nguy-hiem',
  thong_tin: 'thong-tin',
  info: 'thong-tin',
})

/**
 * Chuyen semantic status sang lop mau tap trung cua Visual UI System.
 *
 * Dau vao: ma status presentation tu caller, co the la gia tri Backend chua duoc biet.
 * Cach hoat dong: chi map cac kieu generic da duoc phe duyet; gia tri la fallback trung tinh.
 * Ket qua: mot lop semantic de CSS hien mau kem text label.
 * Side effect: khong thay doi status, khong goi API va khong suy transition nghiep vu.
 * UI rule: mau khong la tin hieu duy nhat vi component luon hien text.
 */
function layKieuTrangThai(trangThai) {
  const khoa = typeof trangThai === 'string' ? trangThai.trim().toLowerCase() : ''
  return KIEU_TRANG_THAI[khoa] ?? 'trung-tinh'
}

const kieuTrangThai = computed(() => layKieuTrangThai(props.trangThai))
const nhanHienThi = computed(() => {
  const nhan = typeof props.nhan === 'string' ? props.nhan.trim() : ''
  return nhan || props.trangThai || 'Không xác định'
})
</script>

<template>
  <span
    class="huy-hieu-trang-thai"
    :class="`huy-hieu-trang-thai--${kieuTrangThai}`"
  >
    {{ nhanHienThi }}
  </span>
</template>
