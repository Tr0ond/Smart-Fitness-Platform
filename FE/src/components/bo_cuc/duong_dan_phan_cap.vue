<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

function laNhanDuongDanAnToan(nhan) {
  if (typeof nhan !== 'string') {
    return false
  }

  const nhanDaCat = nhan.trim()

  return nhanDaCat !== ''
    && !nhanDaCat.startsWith(':')
    && !/^\d+$/.test(nhanDaCat)
}

function laRouteNoiBoDaDangKy(tenTuyenDuong) {
  if (typeof tenTuyenDuong !== 'string' || tenTuyenDuong.trim() === '') {
    return false
  }

  try {
    if (typeof router.hasRoute === 'function') {
      return router.hasRoute(tenTuyenDuong)
    }

    if (typeof router.resolve === 'function') {
      const ketQua = router.resolve({ name: tenTuyenDuong })
      return ketQua?.name === tenTuyenDuong && ketQua.matched.length > 0
    }
  } catch {
    return false
  }

  return false
}

/**
 * Tao danh sach breadcrumb chi tu route metadata cua page hien tai.
 *
 * Dau vao: `route.meta.duongDanPhanCap` va route registry noi bo.
 * Cach hoat dong: loc label rong/raw param, chi cho phep named route da ton tai lam ancestor;
 * muc cuoi cung luon la context hien tai va khong duoc bien thanh lien ket.
 * Ket qua: danh sach label va ten route noi bo de template render nav accessible.
 * Side effect: chi doc route/registry, khong goi API, store, resource lookup hay authorization.
 * Security/UX: khong doc route.params, khong doan entity name va khong tao external href.
 */
function taoDuongDanPhanCap(metaDuongDan) {
  if (!Array.isArray(metaDuongDan)) {
    return []
  }

  const mucHopLe = metaDuongDan
    .filter((muc) => muc !== null
      && typeof muc === 'object'
      && laNhanDuongDanAnToan(muc.nhan))
    .map((muc) => ({
      nhan: muc.nhan.trim(),
      tenTuyenDuong: typeof muc.tenTuyenDuong === 'string' ? muc.tenTuyenDuong.trim() : '',
    }))

  return mucHopLe.map((muc, chiSo) => {
    const laMucHienTai = chiSo === mucHopLe.length - 1
    const coRouteAncestor = !laMucHienTai && laRouteNoiBoDaDangKy(muc.tenTuyenDuong)

    return {
      ...muc,
      laMucHienTai,
      tenTuyenDuong: coRouteAncestor ? muc.tenTuyenDuong : null,
    }
  })
}

const danhSachDuongDan = computed(() => taoDuongDanPhanCap(route?.meta?.duongDanPhanCap))
</script>

<template>
  <nav
    v-if="danhSachDuongDan.length > 0"
    class="duong-dan-phan-cap"
    aria-label="Đường dẫn"
  >
    <ol class="duong-dan-phan-cap__danh-sach">
      <li
        v-for="muc in danhSachDuongDan"
        :key="`${muc.nhan}-${muc.tenTuyenDuong ?? 'hien-tai'}`"
        class="duong-dan-phan-cap__muc"
      >
        <RouterLink
          v-if="muc.tenTuyenDuong && !muc.laMucHienTai"
          :to="{ name: muc.tenTuyenDuong }"
          class="duong-dan-phan-cap__lien-ket"
        >
          {{ muc.nhan }}
        </RouterLink>
        <span
          v-else
          :aria-current="muc.laMucHienTai ? 'page' : undefined"
          class="duong-dan-phan-cap__nhan"
        >
          {{ muc.nhan }}
        </span>
      </li>
    </ol>
  </nav>
</template>
