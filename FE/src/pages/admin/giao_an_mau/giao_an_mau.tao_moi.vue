<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter(); const store = useDanhMucStore(); const { baiTap } = storeToRefs(store); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', goal: '', level: '', sessions_per_week: 1, description: '', status: 'HOAT_DONG', days: [{ order: 1, name: 'Ngày 1', estimated_minutes: 45, exercises: [] }] })
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
function taoLoiValidation(field, message) {
  loi.value = { message: 'Dữ liệu giáo án mẫu không hợp lệ.', fieldErrors: { [field]: [message] } }
}
/**
 * Mục đích: tạo giáo án mẫu cùng cây days đầy đủ.
 * Đầu vào: metadata và cây reactive do editor quản lý; description/notes có thể null theo DTO.
 * Xử lý: khóa double-submit, copy tree để tránh mutation ngoài ý muốn rồi gọi store một lần.
 * Kết quả: điều hướng tới detail khi Backend trả id; lỗi validation giữ nguyên form.
 * Side effect: một POST tạo metadata và phiên bản đầu; không tự retry hoặc hard-delete.
 * Quy tắc/Contract: sessions_per_week 1..7 phải khớp số days; bài tập chỉ lấy từ Backend.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  loi.value = null
  if (!Number.isSafeInteger(form.sessions_per_week) || form.sessions_per_week < 1 || form.sessions_per_week > 7) {
    taoLoiValidation('sessions_per_week', 'Số buổi phải là số nguyên từ 1 đến 7.')
    return
  }
  if (form.days.length !== form.sessions_per_week) {
    taoLoiValidation('sessions_per_week', 'Số buổi phải khớp số ngày trong cây giáo án.')
    return
  }
  dangLuu.value = true
  const result = await store.taoGiaoAnMau({ ...form, days: form.days.map((day) => ({ ...day, exercises: day.exercises.map((item) => ({ ...item })) })) })
  loi.value = store.giaoAnMau.loiMutation
  dangLuu.value = false
  if (result?.id) await router.push({ name: 'adminChiTietGiaoAnMau', params: { id: result.id } })
}
function xuLyHuy() { void router.push({ name: 'adminGiaoAnMau' }) }
onMounted(() => { void store.taiDanhSachBaiTap({ status: 'HOAT_DONG' }) })
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Tạo giáo án mẫu"
  >
    <TieuDeTrang
      tieu-de="Tạo mới giáo án mẫu"
      mo-ta="Tạo nội dung đầy đủ; Backend kiểm tra số buổi, thứ tự và bài tập đang hoạt động."
    /><form @submit.prevent="xuLyLuu">
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="giao-an-create-code"
          nhan="Mã giáo án"
          bat-buoc
          :loi="layLoi('code')"
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model="form.code"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-create-name"
          nhan="Tên giáo án"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model="form.name"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-create-goal"
          nhan="Mục tiêu"
          bat-buoc
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model="form.goal"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-create-level"
          nhan="Cấp độ"
          bat-buoc
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model="form.level"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-create-sessions"
          nhan="Số buổi mỗi tuần"
          bat-buoc
          :loi="layLoi('sessions_per_week')"
        >
          <template #default="{ id }">
            <input
              :id="id"
              v-model.number="form.sessions_per_week"
              type="number"
              min="1"
              max="7"
              step="1"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-create-description"
          nhan="Mô tả"
        >
          <template #default="{ id }">
            <textarea
              :id="id"
              v-model="form.description"
              rows="3"
              maxlength="3000"
            />
          </template>
        </TruongBieuMau>
      </div><CayGiaoAn
        v-model="form.days"
        :exercise-options="baiTap.danhSach"
        :disabled="dangLuu"
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
          {{ dangLuu ? 'Đang lưu…' : 'Tạo giáo án mẫu' }}
        </button>
      </div>
    </form>
  </section>
</template>
