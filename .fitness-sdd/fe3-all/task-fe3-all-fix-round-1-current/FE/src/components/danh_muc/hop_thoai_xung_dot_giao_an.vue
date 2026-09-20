<script setup>
import HopThoaiXacNhan from '../dung_chung/hop_thoai_xac_nhan.vue'

const props = defineProps({
  hienThi: { type: Boolean, default: false },
  dangTai: { type: Boolean, default: false },
  maLoi: { type: String, default: 'WORKOUT_TEMPLATE_STALE' },
  phienBanHienTai: { type: [Number, String], default: null },
})
const emit = defineEmits(['dong', 'taiLai'])

/**
 * Confirm the explicit stale reconciliation requested by the page.
 * The dialog only emits; it never mutates the revision or submits a retry.
 */
function xuLyDoiSoat() {
  if (!props.dangTai) emit('taiLai')
}

function xuLyDong() {
  if (!props.dangTai) emit('dong')
}
</script>

<template>
  <HopThoaiXacNhan
    :hien-thi="props.hienThi"
    tieu-de="Giáo án đã thay đổi"
    :mo-ta="`Backend báo xung đột (${props.maLoi}). Bản nháp được giữ nguyên để đối soát.`"
    nhan-xac-nhan="Đối soát và cập nhật phiên bản"
    nhan-huy="Đóng"
    :dang-xu-ly="props.dangTai"
    id-hop-thoai="hop-thoai-xung-dot-giao-an"
    @xac-nhan="xuLyDoiSoat"
    @huy="xuLyDong"
    @dong="xuLyDong"
  >
    <p v-if="props.phienBanHienTai !== null">
      Nền mới nhất đang ở phiên bản {{ props.phienBanHienTai }}. Kiểm tra phần so sánh rồi đối soát khi bạn sẵn sàng.
    </p>
  </HopThoaiXacNhan>
</template>
