<script setup>
import { computed, reactive, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({ equipment_ids: [], muscle_groups: [] }) },
  equipment: { type: Array, default: () => [] },
  dungCu: { type: Array, default: null },
  muscleGroups: { type: Array, default: () => [] },
  nhomCo: { type: Array, default: null },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'capNhat'])
const duLieu = reactive({ equipment_ids: [], muscle_groups: [] })
const danhSachDungCu = computed(() => props.dungCu ?? props.equipment)
const danhSachNhomCo = computed(() => props.nhomCo ?? props.muscleGroups)

function dongBo(value) {
  duLieu.equipment_ids = Array.isArray(value?.equipment_ids) ? [...value.equipment_ids] : []
  duLieu.muscle_groups = Array.isArray(value?.muscle_groups)
    ? value.muscle_groups.map((item) => ({ id: item.id, role: item.role }))
    : []
}

watch(() => props.modelValue, dongBo, { deep: true, immediate: true })

function idLaSo(value) { return Number(value) }
function nhomCoDangChon(id) { return duLieu.muscle_groups.some((item) => Number(item.id) === Number(id)) }
function layVaiTro(id) { return duLieu.muscle_groups.find((item) => Number(item.id) === Number(id))?.role ?? 'CHINH' }
function layQuanHe(id) { return duLieu.muscle_groups.find((item) => Number(item.id) === Number(id)) }
function laNhomCoKhongHoatDong(group) { return group.status !== 'HOAT_DONG' }
function laQuanHeBatBien(group) { return laNhomCoKhongHoatDong(group) && nhomCoDangChon(group.id) }

/**
 * Purpose: emit a complete replacement payload for the Exercise relation set.
 * Input: the current equipment ids and muscle-group id/role pairs.
 * Process: normalize ids while preserving every existing inactive M061 pivot.
 * Result: consumers receive the authoritative replacement shape.
 * Side effect/rule: no relation is deleted client-side; Backend remains final authority.
 */
function capNhat() {
  const value = {
    ...(props.modelValue ?? {}),
    equipment_ids: [...duLieu.equipment_ids].map(idLaSo),
    muscle_groups: duLieu.muscle_groups.map((item) => ({ id: Number(item.id), role: item.role })),
  }
  emit('update:modelValue', value)
  emit('capNhat', value)
}

function xuLyDungCu(event) {
  duLieu.equipment_ids = Array.from(event.target.selectedOptions).map((option) => Number(option.value))
  capNhat()
}

function xuLyNhomCo(event, group) {
  const id = Number(group.id)
  if (laQuanHeBatBien(group) || laNhomCoKhongHoatDong(group)) {
    dongBo(props.modelValue)
    return
  }
  if (event.target.checked && !nhomCoDangChon(id)) duLieu.muscle_groups.push({ id, role: 'CHINH' })
  if (!event.target.checked) duLieu.muscle_groups = duLieu.muscle_groups.filter((item) => Number(item.id) !== id)
  capNhat()
}

function xuLyVaiTro(event, group) {
  const selected = layQuanHe(group.id)
  if (!selected || laQuanHeBatBien(group)) {
    dongBo(props.modelValue)
    return
  }
  if (selected) selected.role = event.target.value
  capNhat()
}
</script>

<template>
  <fieldset class="danh-muc-quan-he">
    <legend>Quan hệ bài tập</legend>
    <p class="danh-muc-quan-he__goi-y">
      Nhiều dụng cụ là điều kiện AND: bài tập cần tất cả dụng cụ đã chọn.
    </p>
    <label for="bai-tap-equipment">Dụng cụ</label>
    <select
      id="bai-tap-equipment"
      multiple
      :disabled="props.disabled"
      @change="xuLyDungCu"
    >
      <option
        v-for="item in danhSachDungCu"
        :key="item.id"
        :value="item.id"
        :selected="duLieu.equipment_ids.includes(Number(item.id))"
      >
        {{ item.code }} - {{ item.name }}
      </option>
    </select>
    <span class="danh-muc-quan-he__nhan">Nhóm cơ</span>
    <div class="danh-muc-quan-he__nhom-co">
      <label
        v-for="group in danhSachNhomCo"
        :key="group.id"
        :class="{ 'danh-muc-quan-he__khong-hoat-dong': group.status !== 'HOAT_DONG' && !nhomCoDangChon(group.id) }"
      >
        <input
          type="checkbox"
          :checked="nhomCoDangChon(group.id)"
          :disabled="props.disabled || laNhomCoKhongHoatDong(group)"
          @change="xuLyNhomCo($event, group)"
        >
        {{ group.name }} <small v-if="laNhomCoKhongHoatDong(group)">(ngừng sử dụng)</small>
        <select
          v-if="nhomCoDangChon(group.id)"
          :value="layVaiTro(group.id)"
          :disabled="props.disabled || laQuanHeBatBien(group)"
          @change="xuLyVaiTro($event, group)"
        >
          <option value="CHINH">Chính</option>
          <option value="PHU">Phụ</option>
        </select>
      </label>
    </div>
  </fieldset>
</template>
