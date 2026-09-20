<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute(); const router = useRouter(); const store = useDanhMucStore(); const { baiTap, giaoAnMau } = storeToRefs(store); const routeId = Number(route.params.id); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', goal: '', level: '', sessions_per_week: 1, description: '', status: 'HOAT_DONG', days: [] })
function nap(item) { if (item) Object.assign(form, { ...item, days: (item.days ?? []).map((day) => ({ ...day, exercises: (day.exercises ?? []).map((exercise) => ({ ...exercise })) })) }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietGiaoAnMau(routeId)) }
function xuLyTaiLai() { void tai() }
/** PATCH metadata/status only; the immutable days tree changes through a new revision. */
async function xuLyLuu() { dangLuu.value = true; loi.value = null; await store.capNhatGiaoAnMau(routeId, { name: form.name, goal: form.goal, level: form.level, sessions_per_week: form.sessions_per_week, description: form.description, status: form.status }); loi.value = store.giaoAnMau.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
function xuLyQuayLai() { void router.push({ name: 'adminGiaoAnMau' }) }
function xuLyTaoPhienBan() { void router.push({ name: 'adminTaoPhienBanGiaoAnMau', params: { id: routeId } }) }
onMounted(() => { void Promise.all([tai(), store.taiDanhSachBaiTap({ status: 'HOAT_DONG' })]) })
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Chi tiết giáo án mẫu"
    :aria-busy="giaoAnMau.dangTaiChiTiet || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Chi tiết giáo án mẫu"
      mo-ta="PATCH chỉ sửa metadata/status; nội dung days chỉ được thay bằng phiên bản mới."
    /><TrangThaiTaiDuLieu
      v-if="giaoAnMau.dangTaiChiTiet && !giaoAnMau.chiTiet"
      nhan="Đang tải chi tiết giáo án mẫu…"
    /><TrangThaiLoi
      v-else-if="giaoAnMau.loiChiTiet"
      :thong-bao="giaoAnMau.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="giaoAnMau.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    /><form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="giao-an-detail-code"
          nhan="Mã giáo án"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              :value="form.code"
              readonly
              aria-readonly="true"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-name"
          nhan="Tên giáo án"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.name"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-goal"
          nhan="Mục tiêu"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.goal"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-level"
          nhan="Cấp độ"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.level"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-sessions"
          nhan="Số buổi mỗi tuần"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.sessions_per_week"
              type="number"
              min="1"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-status"
          nhan="Trạng thái"
        >
          <template #default="{ id: idTruong }">
            <select
              :id="idTruong"
              v-model="form.status"
            >
              <option value="HOAT_DONG">
                Hoạt động
              </option><option value="NGUNG_SU_DUNG">
                Ngừng sử dụng
              </option>
            </select>
          </template>
        </TruongBieuMau><TruongBieuMau
          id="giao-an-detail-description"
          nhan="Mô tả"
        >
          <template #default="{ id: idTruong }">
            <textarea
              :id="idTruong"
              v-model="form.description"
              rows="3"
              maxlength="3000"
            />
          </template>
        </TruongBieuMau>
      </div><p
        class="trang-danh-muc__canh-bao"
        role="note"
      >
        Days chỉ đọc trong trang chi tiết. Muốn thay đổi nội dung, hãy tạo phiên bản mới với expected_content_version.
      </p><CayGiaoAn
        v-model="form.days"
        :exercise-options="baiTap.danhSach"
        disabled
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
          @click="xuLyQuayLai"
        >
          Quay lại
        </button><button
          class="nut nut--phu"
          type="button"
          :disabled="dangLuu || form.status !== 'HOAT_DONG'"
          @click="xuLyTaoPhienBan"
        >
          Tạo phiên bản
        </button><button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Lưu metadata' }}
        </button>
      </div>
    </form>
  </section>
</template>
