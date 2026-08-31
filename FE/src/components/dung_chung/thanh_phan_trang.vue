<script setup>
import { computed } from 'vue'

const props = defineProps({
  trangHienTai: {
    type: Number,
    default: 1,
  },
  tongSoTrang: {
    type: Number,
    default: 1,
  },
  dangTai: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['chuyenTrang'])

/**
 * Gioi han trang hien tai theo bien server pagination presentation.
 *
 * Dau vao: trangHienTai va tongSoTrang do caller map tu response Backend.
 * Cach hoat dong: coi gia tri khong hop le la trang 1 va khong cho vuot tong so trang.
 * Ket qua: trang hien thi on dinh, khong tao request hay quy uoc query moi.
 * Side effect: khong mutate props, URL, API hay du lieu hang.
 * UI rule: nut boundary disabled va text trang giup thao tac keyboard ro rang.
 */
function layTrangAnToan() {
  const tongSoTrang = Number.isInteger(props.tongSoTrang) && props.tongSoTrang > 0
    ? props.tongSoTrang
    : 1
  const trangHienTai = Number.isInteger(props.trangHienTai) && props.trangHienTai > 0
    ? props.trangHienTai
    : 1

  return {
    trangHienTai: Math.min(trangHienTai, tongSoTrang),
    tongSoTrang,
  }
}

const trangAnToan = computed(() => layTrangAnToan())
const coTheLui = computed(() => trangAnToan.value.trangHienTai > 1)
const coTheTien = computed(() => trangAnToan.value.trangHienTai < trangAnToan.value.tongSoTrang)

function xuLyChuyenTrang(trangMoi) {
  if (!props.dangTai && trangMoi >= 1 && trangMoi <= trangAnToan.value.tongSoTrang) {
    emit('chuyenTrang', trangMoi)
  }
}
</script>

<template>
  <nav
    class="thanh-phan-trang"
    aria-label="Phân trang"
  >
    <button
      class="nut nut--phu"
      type="button"
      :disabled="!coTheLui || props.dangTai"
      @click="xuLyChuyenTrang(trangAnToan.trangHienTai - 1)"
    >
      Trang trước
    </button>
    <span
      class="thanh-phan-trang__chi-so"
      aria-current="page"
    >
      Trang {{ trangAnToan.trangHienTai }} / {{ trangAnToan.tongSoTrang }}
    </span>
    <button
      class="nut nut--phu"
      type="button"
      :disabled="!coTheTien || props.dangTai"
      @click="xuLyChuyenTrang(trangAnToan.trangHienTai + 1)"
    >
      Trang sau
    </button>
  </nav>
</template>
