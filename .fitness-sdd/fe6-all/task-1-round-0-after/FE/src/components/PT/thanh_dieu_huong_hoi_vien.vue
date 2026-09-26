<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { laMucDieuHuongDangHoatDong } from '../../router/dieu_huong_admin.js'

const props = defineProps({
  memberId: {
    type: [String, Number],
    required: true,
  },
})

const route = useRoute()
const router = useRouter()

const CAC_TAB = Object.freeze([
  Object.freeze({ nhan: 'Tổng quan', tenTuyenDuong: 'ptChiTietHoiVien', cacTuyenDuongLienQuan: ['ptChiTietHoiVien'] }),
  Object.freeze({ nhan: 'Tiến độ', tenTuyenDuong: 'ptTienDoHoiVien', cacTuyenDuongLienQuan: ['ptTienDoHoiVien'] }),
  Object.freeze({ nhan: 'Kế hoạch tập', tenTuyenDuong: 'ptKeHoachTapHoiVien', cacTuyenDuongLienQuan: ['ptKeHoachTapHoiVien'] }),
  Object.freeze({ nhan: 'Lịch sử tập', tenTuyenDuong: 'ptLichSuTapHoiVien', cacTuyenDuongLienQuan: ['ptLichSuTapHoiVien'] }),
  Object.freeze({ nhan: 'Ghi chú', tenTuyenDuong: 'ptGhiChuHoiVien', cacTuyenDuongLienQuan: ['ptGhiChuHoiVien'] }),
  Object.freeze({ nhan: 'Buổi huấn luyện', tenTuyenDuong: 'ptBuoiHuanLuyen', cacTuyenDuongLienQuan: ['ptBuoiHuanLuyen'] }),
  Object.freeze({ nhan: 'Đề xuất', tenTuyenDuong: 'ptDeXuatKeHoach', cacTuyenDuongLienQuan: ['ptDeXuatKeHoach', 'ptTaoDeXuatKeHoach'] }),
])

const idAnToan = computed(() => String(props.memberId ?? '').trim())
const cacTabSanSang = computed(() => CAC_TAB.filter(tab => router.hasRoute(tab.tenTuyenDuong)))

function taoLienKet(tab) {
  return { name: tab.tenTuyenDuong, params: { id: idAnToan.value } }
}

function dangHoatDong(tab) {
  return laMucDieuHuongDangHoatDong(tab, route)
}
</script>

<template>
  <nav
    class="pt-thanh-dieu-huong-hoi-vien"
    aria-label="Điều hướng hồ sơ hội viên"
  >
    <RouterLink
      v-for="tab in cacTabSanSang"
      :key="tab.tenTuyenDuong"
      class="pt-thanh-dieu-huong-hoi-vien__muc"
      :class="{ 'pt-thanh-dieu-huong-hoi-vien__muc--dang-chon': dangHoatDong(tab) }"
      :to="taoLienKet(tab)"
      :aria-current="dangHoatDong(tab) ? 'page' : undefined"
    >
      {{ tab.nhan }}
    </RouterLink>
  </nav>
</template>
