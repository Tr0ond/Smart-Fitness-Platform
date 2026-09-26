<script setup>
import KhungUngDung from '../components/bo_cuc/khung_ung_dung.vue'
import { useRouter } from 'vue-router'
import { taoDanhSachDieuHuongPt } from '../router/dieu_huong_pt.js'

const router = useRouter()
const mucDieuHuong = taoDanhSachDieuHuongPt(router)

defineProps({
  dangKhoiPhuc: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <KhungUngDung
    ma-vai-tro="PT"
    nhan-vai-tro="Huấn luyện viên"
    :muc-dieu-huong="mucDieuHuong"
    hien-duong-dan
    :dang-khoi-phuc="dangKhoiPhuc"
  >
    <slot />
  </KhungUngDung>
</template>
