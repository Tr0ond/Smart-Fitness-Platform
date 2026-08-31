<script setup>
import { computed } from 'vue'

const props = defineProps({
  danhSach: {
    type: Array,
    default: () => [],
  },
})

const KIEU_THONG_BAO = Object.freeze({
  trung_tinh: 'trung-tinh',
  thanh_cong: 'thanh-cong',
  success: 'thanh-cong',
  thong_tin: 'thong-tin',
  info: 'thong-tin',
  canh_bao: 'canh-bao',
  warning: 'canh-bao',
  nguy_hiem: 'nguy-hiem',
  error: 'nguy-hiem',
})

/**
 * Chuan hoa kieu thong bao ve tap token presentation an toan.
 *
 * Dau vao: kieu generic cua message do caller truyen vao.
 * Cach hoat dong: map gia tri da biet, fallback trung tinh/thong tin neu unknown.
 * Ket qua: lop semantic va role text-first cho vung thong bao.
 * Side effect: khong tao store global, khong goi API va khong tu dong xoa message.
 * UI rule: noi dung luon render qua interpolation va aria-live duoc duy tri.
 */
function layKieuThongBao(kieu) {
  const khoa = typeof kieu === 'string' ? kieu.trim().toLowerCase() : 'trung_tinh'
  return KIEU_THONG_BAO[khoa] ?? 'trung-tinh'
}

const thongBaoAnToan = computed(() => props.danhSach
  .filter((thongBao) => thongBao !== null && typeof thongBao === 'object')
  .map((thongBao, chiSo) => ({
    id: thongBao.id ?? chiSo,
    kieu: layKieuThongBao(thongBao.kieu),
    noiDung: typeof thongBao.noiDung === 'string'
      ? thongBao.noiDung
      : typeof thongBao.thongBao === 'string' ? thongBao.thongBao : '',
  }))
  .filter((thongBao) => thongBao.noiDung !== '')
)
</script>

<template>
  <section
    v-if="thongBaoAnToan.length > 0"
    class="vung-thong-bao"
    aria-label="Vùng thông báo"
    aria-live="polite"
  >
    <ul class="vung-thong-bao__danh-sach">
      <li
        v-for="thongBao in thongBaoAnToan"
        :key="thongBao.id"
        class="vung-thong-bao__muc"
        :class="`vung-thong-bao__muc--${thongBao.kieu}`"
        :role="thongBao.kieu === 'nguy-hiem' ? 'alert' : 'status'"
      >
        {{ thongBao.noiDung }}
      </li>
    </ul>
  </section>
</template>
