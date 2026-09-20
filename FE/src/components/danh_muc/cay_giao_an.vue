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

function laGiaTriBaiTap(value) {
  return value !== undefined && value !== null && value !== ''
}

function coCungMaBaiTap(first, second) {
  return String(first) === String(second)
}

/**
 * Giữ lại lựa chọn bài tập đã lưu khi bài tập không còn trong danh sách hoạt động.
 * Input: dòng bài tập từ DTO và các bài tập hoạt động do Backend cấp cho selector.
 * Process: chỉ thêm một lựa chọn lịch sử cho dòng đã có exercise_id, đánh dấu lựa chọn đó bị khóa.
 * Output: danh sách hiển thị; side effect: không thay đổi cây hoặc tự thay thế bài tập.
 */
function layDanhSachBaiTapChoDong(item) {
  const danhSachHoatDong = Array.isArray(danhSachBaiTap.value) ? danhSachBaiTap.value : []
  if (!laGiaTriBaiTap(item?.exercise_id)) return danhSachHoatDong
  if (danhSachHoatDong.some((exercise) => coCungMaBaiTap(exercise?.id, item.exercise_id))) return danhSachHoatDong

  return [
    ...danhSachHoatDong,
    {
      id: item.exercise_id,
      name: item.exercise_name || `Bài tập #${item.exercise_id}`,
      laLuaChonLichSu: true,
    },
  ]
}

/**
 * Emit a copy of the workout-template tree after a user edit.
 * The tree is copied so revision callers can submit a stable COW payload.
 */
function xuLyPhatThayDoi() {
  const value = danhSachNgay.value.map((day) => ({
    ...day,
    exercises: Array.isArray(day.exercises) ? day.exercises.map((item) => ({
      ...item,
      notes: item.notes === '' ? null : item.notes,
    })) : [],
  }))
  emit('update:modelValue', value)
  emit('update:days', value)
  emit('thayDoi', value)
}

function xuLyThemNgay() {
  const order = danhSachNgay.value.length + 1
  danhSachNgay.value.push({ order, name: `Ngày ${order}`, estimated_minutes: 45, exercises: [] })
  xuLyPhatThayDoi()
}

function xuLyXoaNgay(index) {
  danhSachNgay.value.splice(index, 1)
  danhSachNgay.value.forEach((day, dayIndex) => { day.order = dayIndex + 1 })
  xuLyPhatThayDoi()
}

function xuLyThemBaiTap(day) {
  const order = (day.exercises?.length ?? 0) + 1
  day.exercises ??= []
  day.exercises.push({ exercise_id: '', order, target_sets: 3, min_reps: 8, max_reps: 12, rest_seconds: 60, notes: null })
  xuLyPhatThayDoi()
}

function xuLyChonBaiTap(item) {
  if (danhSachBaiTap.value.some((exercise) => coCungMaBaiTap(exercise?.id, item.exercise_id))) {
    delete item.exercise_name
  }
  xuLyPhatThayDoi()
}

function xuLyThayDoiGhiChu(item, event) {
  item.notes = event.target.value === '' ? null : event.target.value
  xuLyPhatThayDoi()
}

function xuLyXoaBaiTap(day, index) {
  day.exercises.splice(index, 1)
  day.exercises.forEach((item, itemIndex) => { item.order = itemIndex + 1 })
  xuLyPhatThayDoi()
}
</script>

<template>
  <section
    class="cay-giao-an"
    aria-label="Cây ngày tập và bài tập"
  >
    <div class="cay-giao-an__dau">
      <h2>Nội dung giáo án</h2>
      <button
        class="nut nut--phu"
        type="button"
        :disabled="props.disabled"
        @click="xuLyThemNgay"
      >
        Thêm ngày tập
      </button>
    </div>
    <article
      v-for="(day, dayIndex) in danhSachNgay"
      :key="day.id ?? day.order ?? dayIndex"
      class="cay-giao-an__ngay"
    >
      <header>
        <label :for="`giao-an-day-name-${dayIndex}`">Ngày {{ day.order }} - tên</label>
        <input
          :id="`giao-an-day-name-${dayIndex}`"
          v-model="day.name"
          :disabled="props.disabled"
          @change="xuLyPhatThayDoi"
        >
        <label :for="`giao-an-day-minutes-${dayIndex}`">Phút dự kiến</label>
        <input
          :id="`giao-an-day-minutes-${dayIndex}`"
          v-model.number="day.estimated_minutes"
          type="number"
          min="1"
          :disabled="props.disabled"
          @change="xuLyPhatThayDoi"
        >
        <button
          class="nut nut--nguy-hiem"
          type="button"
          :disabled="props.disabled"
          @click="xuLyXoaNgay(dayIndex)"
        >
          Xóa ngày
        </button>
      </header>
      <ol>
        <li
          v-for="(item, itemIndex) in day.exercises"
          :key="item.id ?? dayIndex + '-' + itemIndex"
        >
          <label :for="`giao-an-exercise-${dayIndex}-${itemIndex}`">Bài tập</label>
          <select
            :id="`giao-an-exercise-${dayIndex}-${itemIndex}`"
            v-model="item.exercise_id"
            :disabled="props.disabled"
            @change="xuLyChonBaiTap(item)"
          >
            <option value="">
              Chọn bài tập
            </option>
            <option
              v-for="exercise in layDanhSachBaiTapChoDong(item)"
              :key="exercise.id"
              :value="exercise.id"
              :disabled="exercise.laLuaChonLichSu"
            >
              {{ exercise.name }}{{ exercise.laLuaChonLichSu ? ' (ngừng sử dụng)' : '' }}
            </option>
          </select>
          <label :for="`giao-an-sets-${dayIndex}-${itemIndex}`">Số hiệp</label>
          <input
            :id="`giao-an-sets-${dayIndex}-${itemIndex}`"
            v-model.number="item.target_sets"
            type="number"
            min="1"
            :disabled="props.disabled"
            @change="xuLyPhatThayDoi"
          >
          <label :for="`giao-an-min-${dayIndex}-${itemIndex}`">Số lần tối thiểu</label>
          <input
            :id="`giao-an-min-${dayIndex}-${itemIndex}`"
            v-model.number="item.min_reps"
            type="number"
            min="1"
            :disabled="props.disabled"
            @change="xuLyPhatThayDoi"
          >
          <label :for="`giao-an-max-${dayIndex}-${itemIndex}`">Số lần tối đa</label>
          <input
            :id="`giao-an-max-${dayIndex}-${itemIndex}`"
            v-model.number="item.max_reps"
            type="number"
            min="1"
            :disabled="props.disabled"
            @change="xuLyPhatThayDoi"
          >
          <label :for="`giao-an-rest-${dayIndex}-${itemIndex}`">Thời gian nghỉ (giây)</label>
          <input
            :id="`giao-an-rest-${dayIndex}-${itemIndex}`"
            v-model.number="item.rest_seconds"
            type="number"
            min="0"
            max="65535"
            step="1"
            required
            :disabled="props.disabled"
            @change="xuLyPhatThayDoi"
          >
          <label :for="`giao-an-notes-${dayIndex}-${itemIndex}`">Ghi chú bài tập</label>
          <textarea
            :id="`giao-an-notes-${dayIndex}-${itemIndex}`"
            :value="item.notes ?? ''"
            maxlength="1000"
            :disabled="props.disabled"
            @input="xuLyThayDoiGhiChu(item, $event)"
          />
          <button
            class="nut nut--phu"
            type="button"
            :disabled="props.disabled"
            @click="xuLyXoaBaiTap(day, itemIndex)"
          >
            Xóa bài tập
          </button>
        </li>
      </ol>
      <button
        class="nut nut--phu"
        type="button"
        :disabled="props.disabled"
        @click="xuLyThemBaiTap(day)"
      >
        Thêm bài tập
      </button>
    </article>
    <p
      v-if="danhSachNgay.length === 0"
      class="cay-giao-an__rong"
    >
      Chưa có ngày tập. Hãy thêm ngày đầu tiên.
    </p>
  </section>
</template>
