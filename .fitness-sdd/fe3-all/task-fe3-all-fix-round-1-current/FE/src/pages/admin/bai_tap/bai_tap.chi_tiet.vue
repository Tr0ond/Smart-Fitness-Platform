<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import BoChonQuanHeBaiTap from '../../../components/danh_muc/bo_chon_quan_he_bai_tap.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute(); const router = useRouter(); const store = useDanhMucStore(); const { dungCu, nhomCo, baiTap } = storeToRefs(store)
const routeId = Number(route.params.id); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', difficulty: '', instructions: '', image_path: '', video_path: '', metadata: {}, status: 'HOAT_DONG', equipment_ids: [], muscle_groups: [] })
function nap(item) { if (!item) return; Object.assign(form, { ...item, equipment_ids: (item.equipment ?? []).map((value) => Number(value.id)), muscle_groups: (item.muscle_groups ?? []).map((value) => ({ id: Number(value.id), role: value.role })), metadata: { ...(item.metadata ?? {}) } }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietBaiTap(routeId)) }
function xuLyTaiLai() { void tai() }
/**
 * Cập nhật metadata và quan hệ Exercise theo replacement contract.
 * Đầu vào là form hiện tại, trong đó các pivot M061 đã được selector khóa và echo.
 * Kết quả là refresh authoritative sau thành công; lỗi immutable được giữ trong flow.
 * Side effect là một PATCH, không xóa quan hệ và không tự retry.
 */
async function xuLyLuu() { dangLuu.value = true; loi.value = null; await store.capNhatBaiTap(routeId, { name: form.name, difficulty: form.difficulty, instructions: form.instructions, image_path: form.image_path, video_path: form.video_path, metadata: form.metadata, status: form.status, equipment_ids: form.equipment_ids, muscle_groups: form.muscle_groups }); loi.value = store.baiTap.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
function xuLyCapNhatQuanHe(value) {
  Object.assign(form, {
    equipment_ids: Array.isArray(value?.equipment_ids) ? [...value.equipment_ids] : [],
    muscle_groups: Array.isArray(value?.muscle_groups) ? value.muscle_groups.map((item) => ({ ...item })) : [],
  })
}
function xuLyQuayLai() { void router.push({ name: 'adminBaiTap' }) }
onMounted(() => { void Promise.all([tai(), store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Chi tiết bài tập"
    :aria-busy="baiTap.dangTaiChiTiet || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Chi tiết bài tập"
      mo-ta="Cập nhật metadata và quan hệ; mã bài tập là bất biến."
    /><TrangThaiTaiDuLieu
      v-if="baiTap.dangTaiChiTiet && !baiTap.chiTiet"
      nhan="Đang tải chi tiết bài tập…"
    /><TrangThaiLoi
      v-else-if="baiTap.loiChiTiet"
      :thong-bao="baiTap.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="baiTap.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    /><form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="bai-tap-detail-code"
          nhan="Mã bài tập"
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
          id="bai-tap-detail-name"
          nhan="Tên bài tập"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id: idTruong, ariaDescribedby, ariaInvalid }">
            <input
              :id="idTruong"
              v-model="form.name"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-difficulty"
          nhan="Độ khó"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.difficulty"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-instructions"
          nhan="Hướng dẫn"
        >
          <template #default="{ id: idTruong }">
            <textarea
              :id="idTruong"
              v-model="form.instructions"
              rows="4"
              maxlength="5000"
            />
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-status"
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
        </TruongBieuMau>
      </div><p
        class="trang-danh-muc__canh-bao"
        role="note"
      >
        Quan hệ máy dụng cụ tuân theo ngữ nghĩa AND. Trạng thái bài tập do Backend quyết định.
      </p><BoChonQuanHeBaiTap
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
          @click="xuLyQuayLai"
        >
          Quay lại
        </button><button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Lưu bài tập' }}
        </button>
      </div>
    </form>
  </section>
</template>
