<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  days: { type: Array, default: null },
  baiTap: { type: Array, default: () => [] },
  exerciseOptions: { type: Array, default: null },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'update:days', 'thayDoi'])
const danhSachBaiTap = computed(() => props.exerciseOptions ?? props.baiTap)
const danhSachNgay = computed(() => props.days ?? props.modelValue)

function phatThayDoi() {
  const value = danhSachNgay.value.map((day) => ({
    ...day,
    exercises: Array.isArray(day.exercises) ? day.exercises.map((item) => ({ ...item })) : [],
  }))
  emit('update:modelValue', value)
  emit('update:days', value)
  emit('thayDoi', value)
}

function themNgay() {
  const order = danhSachNgay.value.length + 1
  danhSachNgay.value.push({ order, name: `Ngay ${order}`, estimated_minutes: 45, exercises: [] })
  phatThayDoi()
}

function xoaNgay(index) {
  danhSachNgay.value.splice(index, 1)
  danhSachNgay.value.forEach((day, dayIndex) => { day.order = dayIndex + 1 })
  phatThayDoi()
}

function themBaiTap(day) {
  const order = (day.exercises?.length ?? 0) + 1
  day.exercises ??= []
  day.exercises.push({ exercise_id: '', order, target_sets: 3, min_reps: 8, max_reps: 12, rest_seconds: 60, notes: '' })
  phatThayDoi()
}

function xoaBaiTap(day, index) {
  day.exercises.splice(index, 1)
  day.exercises.forEach((item, itemIndex) => { item.order = itemIndex + 1 })
  phatThayDoi()
}
</script>

<template>
  <section class="cay-giao-an" aria-label="Cay ngay tap va bai tap">
    <div class="cay-giao-an__dau">
      <h2>Noi dung giao an</h2>
      <button class="nut nut--phu" type="button" :disabled="props.disabled" @click="themNgay">Them ngay tap</button>
    </div>
    <article v-for="(day, dayIndex) in danhSachNgay" :key="day.id ?? day.order ?? dayIndex" class="cay-giao-an__ngay">
      <header>
        <label :for="`giao-an-day-name-${dayIndex}`">Ngay {{ day.order }} - ten</label>
        <input :id="`giao-an-day-name-${dayIndex}`" v-model="day.name" :disabled="props.disabled" @change="phatThayDoi">
        <label :for="`giao-an-day-minutes-${dayIndex}`">Phut du kien</label>
        <input :id="`giao-an-day-minutes-${dayIndex}`" v-model.number="day.estimated_minutes" type="number" min="1" :disabled="props.disabled" @change="phatThayDoi">
        <button class="nut nut--nguy-hiem" type="button" :disabled="props.disabled" @click="xoaNgay(dayIndex)">Xoa ngay</button>
      </header>
      <ol>
        <li v-for="(item, itemIndex) in day.exercises" :key="item.id ?? dayIndex + '-' + itemIndex">
          <label :for="`giao-an-exercise-${dayIndex}-${itemIndex}`">Bai tap</label>
          <select :id="`giao-an-exercise-${dayIndex}-${itemIndex}`" v-model="item.exercise_id" :disabled="props.disabled" @change="phatThayDoi">
            <option value="">Chon bai tap</option>
            <option v-for="exercise in danhSachBaiTap" :key="exercise.id" :value="exercise.id">{{ exercise.name }}</option>
          </select>
          <label :for="`giao-an-sets-${dayIndex}-${itemIndex}`">Sets</label>
          <input :id="`giao-an-sets-${dayIndex}-${itemIndex}`" v-model.number="item.target_sets" type="number" min="1" :disabled="props.disabled" @change="phatThayDoi">
          <label :for="`giao-an-min-${dayIndex}-${itemIndex}`">Reps toi thieu</label>
          <input :id="`giao-an-min-${dayIndex}-${itemIndex}`" v-model.number="item.min_reps" type="number" min="1" :disabled="props.disabled" @change="phatThayDoi">
          <label :for="`giao-an-max-${dayIndex}-${itemIndex}`">Reps toi da</label>
          <input :id="`giao-an-max-${dayIndex}-${itemIndex}`" v-model.number="item.max_reps" type="number" min="1" :disabled="props.disabled" @change="phatThayDoi">
          <button class="nut nut--phu" type="button" :disabled="props.disabled" @click="xoaBaiTap(day, itemIndex)">Xoa bai tap</button>
        </li>
      </ol>
      <button class="nut nut--phu" type="button" :disabled="props.disabled" @click="themBaiTap(day)">Them bai tap</button>
    </article>
    <p v-if="danhSachNgay.length === 0" class="cay-giao-an__rong">Chua co ngay tap. Hay them ngay dau tien.</p>
  </section>
</template>
