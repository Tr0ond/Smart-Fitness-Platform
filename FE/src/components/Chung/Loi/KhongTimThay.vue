<template>
  <section
    class="man-hinh-loi"
    aria-labelledby="tieu-de-khong-tim-thay"
  >
    <h1 id="tieu-de-khong-tim-thay">
      Không tìm thấy trang
    </h1>
    <p>Địa chỉ bạn truy cập không tồn tại hoặc đã được thay đổi.</p>
    <p>
      <RouterLink :to="{ name: 'chonVaiTro' }">
        Quay lại cổng Web
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
