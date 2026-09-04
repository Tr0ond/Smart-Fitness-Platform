<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import KhungUngDung from '../components/bo_cuc/khung_ung_dung.vue'
import { taoDanhSachDieuHuongAdmin } from '../router/dieu_huong_admin.js'

defineProps({
  dangKhoiPhuc: {
    type: Boolean,
    default: false,
  },
})

const router = useRouter()
const mucDieuHuongAdmin = computed(() => taoDanhSachDieuHuongAdmin(router))
</script>

<template>
  <KhungUngDung
    ma-vai-tro="ADMIN"
    nhan-vai-tro="Quản trị viên"
    :muc-dieu-huong="mucDieuHuongAdmin"
    :hien-duong-dan="true"
    :dang-khoi-phuc="dangKhoiPhuc"
  >
    <slot />
  </KhungUngDung>
</template>
