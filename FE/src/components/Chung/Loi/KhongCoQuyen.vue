<template>
  <section
    class="man-hinh-loi"
    aria-labelledby="tieu-de-khong-co-quyen"
  >
    <h1 id="tieu-de-khong-co-quyen">
      Bạn không có quyền truy cập
    </h1>
    <p>Vai trò hiện tại không được phép truy cập khu vực này.</p>
    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại chọn vai trò
      </RouterLink>
    </p>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>

<style scoped>
.man-hinh-loi {
  max-width: 42rem;
  margin: 0 auto;
  padding: 2rem 1rem;
}
</style>
