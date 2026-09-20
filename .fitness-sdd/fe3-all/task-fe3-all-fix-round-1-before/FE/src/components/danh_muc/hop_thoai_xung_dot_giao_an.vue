<script setup>
import HopThoaiXacNhan from '../dung_chung/hop_thoai_xac_nhan.vue'

const props = defineProps({
  hienThi: { type: Boolean, default: false },
  dangTai: { type: Boolean, default: false },
  maLoi: { type: String, default: 'WORKOUT_TEMPLATE_STALE' },
  phienBanHienTai: { type: [Number, String], default: null },
})
const emit = defineEmits(['dong', 'taiLai'])
</script>

<template>
  <HopThoaiXacNhan
    :hien-thi="props.hienThi"
    tieu-de="Giao an da thay doi"
    :mo-ta="`Backend bao xung dot (${props.maLoi}). Ban nhap dang duoc giu nguyen de doi soat.`"
    nhan-xac-nhan="Tai ban moi"
    nhan-huy="Dong"
    :dang-xu-ly="props.dangTai"
    id-hop-thoai="hop-thoai-xung-dot-giao-an"
    @xac-nhan="emit('taiLai')"
    @huy="emit('dong')"
    @dong="emit('dong')"
  >
    <p v-if="props.phienBanHienTai !== null">Phien ban hien tai: {{ props.phienBanHienTai }}. Ban co the xem lai va xac nhan tao phien ban moi.</p>
  </HopThoaiXacNhan>
</template>
