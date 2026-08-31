<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <section aria-labelledby="tieu-de-khong-co-quyen">
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
