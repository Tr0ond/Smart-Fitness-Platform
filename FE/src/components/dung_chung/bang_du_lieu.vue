<script setup>
import { computed } from 'vue'

const props = defineProps({
  cot: {
    type: Array,
    default: () => [],
  },
  hang: {
    type: Array,
    default: () => [],
  },
  tieuDe: {
    type: String,
    default: '',
  },
  dangTai: {
    type: Boolean,
    default: false,
  },
  thongBaoLoi: {
    type: String,
    default: '',
  },
  thongBaoTrong: {
    type: String,
    default: 'Không có dữ liệu phù hợp.',
  },
})

const coHang = computed(() => props.hang.length > 0)
</script>

<template>
  <section
    class="bang-du-lieu"
    :aria-busy="props.dangTai"
  >
    <div
      v-if="props.dangTai"
      class="bang-du-lieu__trang-thai"
      role="status"
      aria-live="polite"
    >
      Đang tải dữ liệu…
    </div>
    <div
      v-else-if="props.thongBaoLoi"
      class="bang-du-lieu__trang-thai bang-du-lieu__trang-thai--loi"
      role="alert"
    >
      {{ props.thongBaoLoi }}
    </div>
    <div
      v-else-if="!coHang"
      class="bang-du-lieu__trang-thai"
      role="status"
      aria-live="polite"
    >
      {{ props.thongBaoTrong }}
    </div>
    <div
      v-else
      class="bang-du-lieu__cuon"
    >
      <table>
        <caption v-if="props.tieuDe">
          {{ props.tieuDe }}
        </caption>
        <thead>
          <slot name="tieuDeCot">
            <tr>
              <th
                v-for="cotTrongBang in props.cot"
                :key="cotTrongBang.khoa"
                scope="col"
              >
                {{ cotTrongBang.nhan }}
              </th>
            </tr>
          </slot>
        </thead>
        <tbody>
          <slot
            v-if="$slots.hang"
            name="hang"
            :hang="props.hang"
          />
          <tr
            v-for="(hangTrongBang, chiSoHang) in props.hang"
            v-else
            :key="hangTrongBang.id ?? chiSoHang"
          >
            <td
              v-for="cotTrongBang in props.cot"
              :key="cotTrongBang.khoa"
            >
              {{ hangTrongBang[cotTrongBang.khoa] }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
