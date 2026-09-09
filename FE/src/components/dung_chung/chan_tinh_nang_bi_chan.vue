<script setup>
import { useId } from 'vue'

defineProps({
  tieuDe: {
    type: String,
    required: true,
  },
  moTa: {
    type: String,
    required: true,
  },
  maChan: {
    type: String,
    required: true,
  },
  hanhDongTiepTheo: {
    type: String,
    default: '',
  },
})

const idThanhPhan = useId()
const idTieuDe = `chan-tinh-nang-bi-chan-tieu-de-${idThanhPhan}`
const idMoTa = `chan-tinh-nang-bi-chan-mo-ta-${idThanhPhan}`
</script>

<template>
  <section
    class="chan-tinh-nang-bi-chan"
    role="status"
    aria-live="polite"
    :aria-labelledby="idTieuDe"
    :aria-describedby="idMoTa"
  >
    <div class="chan-tinh-nang-bi-chan__dau">
      <div>
        <p class="chan-tinh-nang-bi-chan__nhan-khu-vuc">
          TÍNH NĂNG CHƯA SẴN SÀNG
        </p>
        <h3 :id="idTieuDe">
          {{ tieuDe }}
        </h3>
      </div>
      <code class="chan-tinh-nang-bi-chan__ma">{{ maChan }}</code>
    </div>

    <p
      :id="idMoTa"
      class="chan-tinh-nang-bi-chan__mo-ta"
    >
      {{ moTa }}
    </p>

    <div
      v-if="hanhDongTiepTheo || $slots.hanhDongTiepTheo || $slots.default"
      class="chan-tinh-nang-bi-chan__hanh-dong"
    >
      <p v-if="hanhDongTiepTheo">
        <strong>Hướng xử lý tiếp theo:</strong> {{ hanhDongTiepTheo }}
      </p>
      <slot
        v-if="$slots.hanhDongTiepTheo"
        name="hanhDongTiepTheo"
      />
      <slot v-else-if="$slots.default" />
    </div>
  </section>
</template>
