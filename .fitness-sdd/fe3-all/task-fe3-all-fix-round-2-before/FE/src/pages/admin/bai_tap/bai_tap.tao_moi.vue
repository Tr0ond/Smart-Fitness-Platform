<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BoChonQuanHeBaiTap from '../../../components/danh_muc/bo_chon_quan_he_bai_tap.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { dungCu, nhomCo } = storeToRefs(store)
const dangLuu = ref(false)
const loi = ref(null)
const form = reactive({ code: '', name: '', difficulty: '', instructions: '', image_path: '', video_path: '', metadata: {}, status: 'HOAT_DONG', equipment_ids: [], muscle_groups: [] })
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
/**
 * Tạo Exercise mới cùng toàn bộ quan hệ equipment và muscle group.
 * Đầu vào là form đã được kiểm soát bởi native validation và selector quan hệ.
 * Kết quả là chuyển tới detail khi Backend xác nhận tạo thành công; lỗi 422 vẫn ở form.
 * Side effect là một POST duy nhất, nút submit bị khóa khi đang chờ; Q11 giữ đủ mảng equipment_ids.
 */
async function xuLyLuu() {
  loi.value = null; dangLuu.value = true
  const result = await store.taoBaiTap({ ...form, equipment_ids: [...form.equipment_ids], muscle_groups: form.muscle_groups.map((item) => ({ ...item })) })
  loi.value = store.baiTap.loiMutation; dangLuu.value = false
  if (result?.id) await router.push({ name: 'adminChiTietBaiTap', params: { id: result.id } })
}
function xuLyCapNhatQuanHe(value) {
  Object.assign(form, {
    equipment_ids: Array.isArray(value?.equipment_ids) ? [...value.equipment_ids] : [],
    muscle_groups: Array.isArray(value?.muscle_groups) ? value.muscle_groups.map((item) => ({ ...item })) : [],
  })
}
function xuLyHuy() { void router.push({ name: 'adminBaiTap' }) }
onMounted(() => { void Promise.all([store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Tạo bài tập"
  >
    <TieuDeTrang
      tieu-de="Tạo mới bài tập"
      mo-ta="Khai báo quan hệ bắt buộc theo hợp đồng danh mục bài tập."
    /><form @submit.prevent="xuLyLuu">
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="bai-tap-create-code"
          nhan="Mã bài tập"
          bat-buoc
          :loi="layLoi('code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="form.code"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-create-name"
          nhan="Tên bài tập"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="form.name"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-create-difficulty"
          nhan="Độ khó"
          bat-buoc
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model="form.difficulty"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-create-instructions"
          nhan="Hướng dẫn"
        >
          <template #default="{ id }">
            <textarea
              :id="id"
              v-model="form.instructions"
              rows="4"
              maxlength="5000"
            />
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-create-status"
          nhan="Trạng thái"
        >
          <template #default="{ id }">
            <select
              :id="id"
              v-model="form.status"
            >
              <option value="HOAT_DONG">
                Hoạt động
              </option><option value="NGUNG_SU_DUNG">
                Ngừng sử dụng
              </option>
            </select>
          </template>
        </TruongBieuMau>
      </div><BoChonQuanHeBaiTap
        :model-value="form"
        :dung-cu="dungCu.danhSach"
        :nhom-co="nhomCo.danhSach"
        :disabled="dangLuu"
        @update:model-value="xuLyCapNhatQuanHe"
      /><p
        v-if="loi?.message"
        class="trang-danh-muc__loi"
        role="alert"
      >
        {{ loi.message }}
      </p><div class="trang-danh-muc__hanh-dong">
        <button
          class="nut nut--phu"
          type="button"
          :disabled="dangLuu"
          @click="xuLyHuy"
        >
          Hủy
        </button><button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Tạo bài tập' }}
        </button>
      </div>
    </form>
  </section>
</template>
