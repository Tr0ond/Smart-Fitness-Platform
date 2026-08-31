<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
  nhan: {
    type: String,
    required: true,
  },
  batBuoc: {
    type: Boolean,
    default: false,
  },
  troGiup: {
    type: String,
    default: '',
  },
  loi: {
    type: String,
    default: '',
  },
})

const idTroGiup = computed(() => `${props.id}-tro-giup`)
const idLoi = computed(() => `${props.id}-loi`)
const ariaDescribedby = computed(() => [
  props.troGiup ? idTroGiup.value : '',
  props.loi ? idLoi.value : '',
].filter(Boolean).join(' ') || undefined)
const ariaInvalid = computed(() => (props.loi ? 'true' : undefined))
</script>

<template>
  <div
    class="truong-bieu-mau"
    :class="{ 'truong-bieu-mau--co-loi': Boolean(props.loi) }"
  >
    <label
      class="truong-bieu-mau__nhan"
      :for="props.id"
    >
      {{ props.nhan }}
      <span
        v-if="props.batBuoc"
        class="truong-bieu-mau__bat-buoc"
        aria-hidden="true"
      >
        *
      </span>
    </label>
    <div class="truong-bieu-mau__dieu-khien">
      <slot
        :id="props.id"
        :aria-describedby="ariaDescribedby"
        :aria-invalid="ariaInvalid"
      />
    </div>
    <p
      v-if="props.troGiup"
      :id="idTroGiup"
      class="truong-bieu-mau__tro-giup"
    >
      {{ props.troGiup }}
    </p>
    <p
      v-if="props.loi"
      :id="idLoi"
      class="truong-bieu-mau__loi"
      role="alert"
    >
      {{ props.loi }}
    </p>
  </div>
</template>
