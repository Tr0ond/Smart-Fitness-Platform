<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  quyenLoi: { type: Object, default: null },
  dangLuu: { type: Boolean, default: false },
  loi: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'luu'])
const macDinh = () => ({
  gym_access: true,
  fitness_assistant: false,
  fitness_assistant_limit: 0,
  trainer_chat: false,
  direct_trainer_sessions: 0,
})
const duLieu = reactive(macDinh())

function dongBo(value) {
  Object.assign(duLieu, macDinh(), value ?? {})
}

watch(() => props.quyenLoi ?? props.modelValue, dongBo, { deep: true, immediate: true })

function xuLyPhatThayDoi() {
  emit('update:modelValue', { ...duLieu })
}

function xuLyLuu() {
  xuLyPhatThayDoi()
  emit('luu', { ...duLieu })
}
</script>

<template>
  <fieldset class="danh-muc-quyen-loi">
    <legend>Quyền lợi gói tập</legend>
    <p class="danh-muc-quyen-loi__goi-y">
      Quyền lợi được lưu theo snapshot của gói tại thời điểm mua.
    </p>
    <label><input
      v-model="duLieu.gym_access"
      type="checkbox"
      @change="xuLyPhatThayDoi"
    > Quyền vào phòng gym</label>
    <label><input
      v-model="duLieu.fitness_assistant"
      type="checkbox"
      @change="xuLyPhatThayDoi"
    > Trợ lý fitness</label>
    <label for="fitness-assistant-limit">Lượt trợ lý</label>
    <input
      id="fitness-assistant-limit"
      v-model.number="duLieu.fitness_assistant_limit"
      type="number"
      min="0"
      :disabled="!duLieu.fitness_assistant || props.dangLuu"
      @change="xuLyPhatThayDoi"
    >
    <label><input
      v-model="duLieu.trainer_chat"
      type="checkbox"
      @change="xuLyPhatThayDoi"
    > Chat với PT</label>
    <label for="direct-trainer-sessions">Số buổi PT trực tiếp</label>
    <input
      id="direct-trainer-sessions"
      v-model.number="duLieu.direct_trainer_sessions"
      type="number"
      min="0"
      :disabled="props.dangLuu"
      @change="xuLyPhatThayDoi"
    >
    <p
      class="danh-muc-quyen-loi__canh-bao"
      role="note"
    >
      Thay đổi quyền lợi chỉ áp dụng cho lượt cấp mới; Backend giữ snapshot của các kỳ đã mua.
    </p>
    <p
      v-if="props.loi?.fieldErrors?.benefits"
      class="truong-bieu-mau__loi"
      role="alert"
    >
      {{ props.loi.fieldErrors.benefits[0] }}
    </p>
    <button
      class="nut nut--chinh"
      type="button"
      :disabled="props.dangLuu"
      @click="xuLyLuu"
    >
      {{ props.dangLuu ? 'Đang lưu…' : 'Lưu quyền lợi' }}
    </button>
  </fieldset>
</template>
