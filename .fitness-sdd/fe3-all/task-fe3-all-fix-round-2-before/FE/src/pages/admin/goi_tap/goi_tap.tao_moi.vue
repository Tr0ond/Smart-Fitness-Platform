<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import BoSuaQuyenLoiGoiTap from '../../../components/danh_muc/bo_sua_quyen_loi_goi_tap.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const dangLuu = ref(false)
const loi = ref(null)
const form = reactive({ code: '', name: '', price: 0, duration_days: 30, description: '', status: 'DANG_BAN', benefits: { gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 } })
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
/**
 * Tạo package và để Backend ghi nhận quyền lợi cho các kỳ mua mới.
 * Đầu vào là metadata và benefits đã được component kiểm soát.
 * Kết quả điều hướng sau khi Backend xác nhận; lỗi field vẫn hiển thị tại form.
 * Side effect là một POST duy nhất, khóa submit để tránh double-submit.
 */
async function xuLyLuu() {
  loi.value = null
  dangLuu.value = true
  const result = await store.taoGoiTap({ ...form, benefits: { ...form.benefits } })
  loi.value = store.goiTap.loiMutation
  dangLuu.value = false
  if (result?.id) await router.push({ name: 'adminChiTietGoiTap', params: { id: result.id } })
}
function xuLyHuy() { void router.push({ name: 'adminGoiTap' }) }
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Tạo gói tập"
  >
    <TieuDeTrang
      tieu-de="Tạo mới gói tập"
      mo-ta="Khai báo dữ liệu theo hợp đồng Admin Package."
    />
    <form @submit.prevent="xuLyLuu">
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="goi-tap-code"
          nhan="Mã gói"
          bat-buoc
          :loi="layLoi('code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="form.code"
              name="code"
              maxlength="120"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="goi-tap-name"
          nhan="Tên gói"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="form.name"
              name="name"
              maxlength="120"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="goi-tap-price"
          nhan="Giá"
          bat-buoc
          :loi="layLoi('price')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model.number="form.price"
              name="price"
              type="number"
              min="0"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="goi-tap-duration"
          nhan="Thời hạn (ngày)"
          bat-buoc
          :loi="layLoi('duration_days')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model.number="form.duration_days"
              name="duration_days"
              type="number"
              min="1"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="goi-tap-status"
          nhan="Trạng thái"
          :loi="layLoi('status')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <select
              :id="id"
              v-model="form.status"
              name="status"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
              <option value="DANG_BAN">
                Đang bán
              </option><option value="NGUNG_BAN">
                Ngừng bán
              </option>
            </select>
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="goi-tap-description"
          nhan="Mô tả"
          :loi="layLoi('description')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <textarea
              :id="id"
              v-model="form.description"
              name="description"
              rows="4"
              maxlength="2000"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            />
          </template>
        </TruongBieuMau>
      </div>
      <BoSuaQuyenLoiGoiTap
        v-model="form.benefits"
        :dang-luu="dangLuu"
        :loi="loi"
      />
      <p
        v-if="loi?.message"
        class="trang-danh-muc__loi"
        role="alert"
      >
        {{ loi.message }}
      </p>
      <div class="trang-danh-muc__hanh-dong">
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
          :aria-busy="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Tạo gói tập' }}
        </button>
      </div>
    </form>
  </section>
</template>
