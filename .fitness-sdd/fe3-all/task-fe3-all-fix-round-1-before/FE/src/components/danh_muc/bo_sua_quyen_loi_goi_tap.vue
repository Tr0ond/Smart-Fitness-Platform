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

function phatThayDoi() {
  emit('update:modelValue', { ...duLieu })
}

function xuLyLuu() {
  phatThayDoi()
  emit('luu', { ...duLieu })
}
</script>

<template>
  <fieldset class="danh-muc-quyen-loi">
    <legend>Quyen loi goi tap</legend>
    <p class="danh-muc-quyen-loi__goi-y">Quyen loi duoc luu theo snapshot cua goi tai thoi diem mua.</p>
    <label><input v-model="duLieu.gym_access" type="checkbox" @change="phatThayDoi"> Quyen vao phong gym</label>
    <label><input v-model="duLieu.fitness_assistant" type="checkbox" @change="phatThayDoi"> Tro ly fitness</label>
    <label for="fitness-assistant-limit">Luot tro ly</label>
    <input
      id="fitness-assistant-limit"
      v-model.number="duLieu.fitness_assistant_limit"
      type="number"
      min="0"
      :disabled="!duLieu.fitness_assistant || props.dangLuu"
      @change="phatThayDoi"
    >
    <label><input v-model="duLieu.trainer_chat" type="checkbox" @change="phatThayDoi"> Chat voi PT</label>
    <label for="direct-trainer-sessions">So buoi PT truc tiep</label>
    <input
      id="direct-trainer-sessions"
      v-model.number="duLieu.direct_trainer_sessions"
      type="number"
      min="0"
      :disabled="props.dangLuu"
      @change="phatThayDoi"
    >
    <p class="danh-muc-quyen-loi__canh-bao" role="note">
      Thay doi quyen loi chi ap dung cho luot cap moi; Backend giu snapshot cua cac ky da mua.
    </p>
    <p v-if="props.loi?.fieldErrors?.benefits" class="truong-bieu-mau__loi" role="alert">
      {{ props.loi.fieldErrors.benefits[0] }}
    </p>
    <button class="nut nut--chinh" type="button" :disabled="props.dangLuu" @click="xuLyLuu">
      {{ props.dangLuu ? 'Dang luu...' : 'Luu quyen loi' }}
    </button>
  </fieldset>
</template>
