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
  if (event.target.checked && !nhomCoDangChon(id)) duLieu.muscle_groups.push({ id, role: 'CHINH' })
  if (!event.target.checked) duLieu.muscle_groups = duLieu.muscle_groups.filter((item) => Number(item.id) !== id)
  capNhat()
}

function xuLyVaiTro(event, group) {
  const selected = duLieu.muscle_groups.find((item) => Number(item.id) === Number(group.id))
  if (selected) selected.role = event.target.value
  capNhat()
}
</script>

<template>
  <fieldset class="danh-muc-quan-he">
    <legend>Quan he bai tap</legend>
    <p class="danh-muc-quan-he__goi-y">Nhieu dung cu la dieu kien AND: bai tap can tat ca dung cu da chon.</p>
    <label for="bai-tap-equipment">Dung cu</label>
    <select id="bai-tap-equipment" multiple :disabled="props.disabled" @change="xuLyDungCu">
      <option v-for="item in danhSachDungCu" :key="item.id" :value="item.id" :selected="duLieu.equipment_ids.includes(Number(item.id))">
        {{ item.code }} - {{ item.name }}
      </option>
    </select>
    <span class="danh-muc-quan-he__nhan">Nhom co</span>
    <div class="danh-muc-quan-he__nhom-co">
      <label v-for="group in danhSachNhomCo" :key="group.id" :class="{ 'danh-muc-quan-he__khong-hoat-dong': group.status !== 'HOAT_DONG' && !nhomCoDangChon(group.id) }">
        <input
          type="checkbox"
          :checked="nhomCoDangChon(group.id)"
          :disabled="props.disabled || (group.status !== 'HOAT_DONG' && !nhomCoDangChon(group.id))"
          @change="xuLyNhomCo($event, group)"
        >
        {{ group.name }} <small v-if="group.status !== 'HOAT_DONG'">(ngung su dung)</small>
        <select v-if="nhomCoDangChon(group.id)" :value="layVaiTro(group.id)" :disabled="props.disabled" @change="xuLyVaiTro($event, group)">
          <option value="CHINH">Chinh</option>
          <option value="PHU">Phu</option>
        </select>
      </label>
    </div>
  </fieldset>
</template>
