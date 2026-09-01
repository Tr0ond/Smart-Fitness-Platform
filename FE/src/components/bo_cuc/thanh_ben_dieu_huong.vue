<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const props = defineProps({
  idThanhBen: {
    type: String,
    default: 'thanh-ben-dieu-huong',
  },
  mucDieuHuong: {
    type: Array,
    default: () => [],
  },
  dangMo: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['dong', 'chon'])
const route = useRoute()
const router = useRouter()

function layDuongDanHopLe(muc) {
  if (muc === null || typeof muc !== 'object' || muc.to === undefined || muc.to === null) {
    return null
  }

  try {
    const ketQua = router.resolve(muc.to)
    return ketQua.matched.length > 0 ? ketQua : null
  } catch {
    return null
  }
}

const mucDieuHuongHopLe = computed(() => props.mucDieuHuong.filter((muc) => {
  const ketQua = layDuongDanHopLe(muc)
  return ketQua !== null && typeof muc.nhan === 'string' && muc.nhan.trim() !== ''
}))

function laMucDangChon(muc) {
  const ketQua = layDuongDanHopLe(muc)
  return ketQua !== null && ketQua.fullPath === route.fullPath
}

/**
 * Đóng drawer sau khi người dùng chọn một mục điều hướng hợp lệ.
 *
 * Đầu vào: mục điều hướng đã được RouterLink kiểm tra route.
 * Cách hoạt động: phát sự kiện để shell đóng drawer; RouterLink vẫn chịu trách nhiệm điều hướng.
 * Kết quả: menu mobile không che nội dung sau khi route đổi.
 * Side effect: chỉ phát sự kiện UI, không gọi API và không thay đổi quyền actor.
 * Auth/UX: không tự tạo link hoặc menu theo role; chỉ dùng mục được layout truyền vào.
 */
function xuLyChonMuc(muc) {
  emit('chon', muc)
}
</script>

<template>
  <aside
    :id="props.idThanhBen"
    class="thanh-ben-dieu-huong"
    :class="{ 'thanh-ben-dieu-huong--dang-mo': props.dangMo }"
    aria-label="Điều hướng khu vực"
  >
    <div class="thanh-ben-dieu-huong__dau">
      <div class="thanh-ben-dieu-huong__thuong-hieu">
        <span
          class="thanh-ben-dieu-huong__dau-hieu"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 32 32"
            fill="none"
          >
            <path
              d="M7 6v20M25 6v20M4 11h6M22 11h6M4 21h6M22 21h6"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
            <path
              d="M12 16h8"
              stroke="currentColor"
              stroke-width="2.4"
              stroke-linecap="round"
            />
          </svg>
        </span>
        <span class="thanh-ben-dieu-huong__tieu-de">SMART FITNESS</span>
      </div>
      <p class="thanh-ben-dieu-huong__nhan">
        OPERATIONS / 01
      </p>
      <button
        class="thanh-ben-dieu-huong__nut-dong"
        type="button"
        aria-label="Đóng menu điều hướng"
        @click="emit('dong')"
      >
        <svg
          viewBox="0 0 20 20"
          fill="none"
          aria-hidden="true"
        >
          <path
            d="m5 5 10 10M15 5 5 15"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linecap="round"
          />
        </svg>
        Đóng menu
      </button>
    </div>

    <nav
      class="thanh-ben-dieu-huong__nav"
      aria-label="Menu khu vực"
    >
      <ul
        v-if="mucDieuHuongHopLe.length > 0"
        class="thanh-ben-dieu-huong__danh-sach"
      >
        <li
          v-for="muc in mucDieuHuongHopLe"
          :key="muc.id ?? muc.ten ?? muc.nhan"
        >
          <RouterLink
            :to="muc.to"
            class="thanh-ben-dieu-huong__lien-ket"
            :class="{ 'thanh-ben-dieu-huong__lien-ket--dang-chon': laMucDangChon(muc) }"
            :aria-current="laMucDangChon(muc) ? 'page' : undefined"
            @click="xuLyChonMuc(muc)"
          >
            {{ muc.nhan }}
          </RouterLink>
        </li>
      </ul>
      <p
        v-else
        class="thanh-ben-dieu-huong__rong"
      >
        Chưa có mục điều hướng. Khu vực nghiệp vụ đang được hoàn thiện.
      </p>
    </nav>
  </aside>
</template>
