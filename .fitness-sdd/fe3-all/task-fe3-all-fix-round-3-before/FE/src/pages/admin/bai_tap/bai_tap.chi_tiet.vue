<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import BoChonQuanHeBaiTap from '../../../components/danh_muc/bo_chon_quan_he_bai_tap.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute()
const router = useRouter()
const store = useDanhMucStore()
const { dungCu, nhomCo, baiTap } = storeToRefs(store)
const routeId = Number(route.params.id)
const dangLuu = ref(false)
const loi = ref(null)
const loiTaiLai = ref(null)
const daCoDuLieu = ref(false)
const dangDoiTrangThai = ref(false)
const trangThaiCanDoi = ref(null)
const yDinhTrangThai = ref(null)
const giaiDoanTrangThai = ref('CHO_XAC_NHAN')
const loiTrangThai = ref(null)
const form = reactive({ code: '', name: '', difficulty: '', instructions: '', image_path: null, video_path: null, metadata: {}, status: 'HOAT_DONG', equipment_ids: [], muscle_groups: [] })
function nap(item) {
  if (!item) return
  Object.assign(form, {
    ...item,
    instructions: item.instructions ?? '',
    image_path: Object.prototype.hasOwnProperty.call(item, 'image_path') ? item.image_path : null,
    video_path: Object.prototype.hasOwnProperty.call(item, 'video_path') ? item.video_path : null,
    equipment_ids: (item.equipment ?? []).map((value) => Number(value.id)),
    muscle_groups: (item.muscle_groups ?? []).map((value) => ({ id: Number(value.id), role: value.role })),
    metadata: { ...(item.metadata ?? {}) },
  })
}
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() {
  loiTaiLai.value = null
  const result = await store.taiChiTietBaiTap(routeId)
  if (result) {
    nap(result)
    daCoDuLieu.value = true
  } else loiTaiLai.value = store.baiTap.loiChiTiet
  return result
}
function xuLyTaiLai() { void tai() }
/**
 * Mục đích: cập nhật metadata và quan hệ Exercise theo replacement contract.
 * Đầu vào: form hiện tại với pivot M061 đã khóa và echo đúng id/role.
 * Xử lý: loại status khỏi metadata PATCH, khóa submit, gửi một request rồi GET fresh.
 * Kết quả: hiển thị DTO authoritative; lỗi immutable/422 giữ nguyên form để sửa.
 * Side effect: một PATCH và một GET, không xóa quan hệ và không retry mù.
 * Quy tắc/Contract: Q11 dùng AND; status existing chỉ thay qua dialog xác nhận riêng.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  dangLuu.value = true
  loi.value = null
  await store.capNhatBaiTap(routeId, {
    name: form.name,
    difficulty: form.difficulty,
    instructions: form.instructions,
    image_path: form.image_path,
    video_path: form.video_path,
    metadata: form.metadata,
    equipment_ids: form.equipment_ids,
    muscle_groups: form.muscle_groups,
  })
  loi.value = store.baiTap.loiMutation
  dangLuu.value = false
  if (!loi.value) {
    const refreshed = await tai()
    if (!refreshed) loi.value = loiTaiLai.value
  }
}
function xuLyCapNhatQuanHe(value) {
  Object.assign(form, {
    equipment_ids: Array.isArray(value?.equipment_ids) ? [...value.equipment_ids] : [],
    muscle_groups: Array.isArray(value?.muscle_groups) ? value.muscle_groups.map((item) => ({ ...item })) : [],
  })
}
function xuLyQuayLai() { void router.push({ name: 'adminBaiTap' }) }
function xuLyMoDoiTrangThai() {
  loiTrangThai.value = null
  giaiDoanTrangThai.value = 'CHO_XAC_NHAN'
  trangThaiCanDoi.value = form.status
  yDinhTrangThai.value = form.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG'
}
function datLaiDoiTrangThai() {
  trangThaiCanDoi.value = null
  yDinhTrangThai.value = null
  loiTrangThai.value = null
  giaiDoanTrangThai.value = 'CHO_XAC_NHAN'
}

/**
 * Mục đích: đối soát status Exercise khi PATCH chưa rõ kết quả.
 * Đầu vào: status trước và intended đã chụp khi mở dialog.
 * Xử lý: GET detail authoritative, không gửi metadata hay PATCH tự động.
 * Kết quả: intended đóng, previous cho retry explicit, trạng thái khác giữ dialog.
 * Side effect: chỉ đọc và giữ mọi nullable field/quan hệ; không làm mất M061 echo.
 * Quy tắc/Contract: sensitive status cần xác nhận; Backend là authority.
 */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !trangThaiCanDoi.value) return
  dangDoiTrangThai.value = true
  giaiDoanTrangThai.value = 'DANG_DOI_SOAT'
  loiTrangThai.value = null
  const latest = await tai()
  dangDoiTrangThai.value = false
  if (!latest) {
    giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
    loiTrangThai.value = loiTaiLai.value ?? { message: 'Chưa xác định được trạng thái bài tập.' }
    return
  }
  if (form.status === yDinhTrangThai.value) { datLaiDoiTrangThai(); return }
  if (form.status === trangThaiCanDoi.value) { giaiDoanTrangThai.value = 'CO_THE_THU_LAI'; return }
  giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
  loiTrangThai.value = { message: 'Chưa xác định được trạng thái hiện tại của bài tập.' }
}

/**
 * Mục đích: xác nhận transition status Exercise từ entry point detail.
 * Đầu vào: intended status; click ở trạng thái chưa rõ chuyển thành GET-only reconcile.
 * Xử lý: PATCH chỉ status, sau đó GET detail và so sánh response.
 * Kết quả: đóng khi response khớp intended; 4xx/unknown/mismatch vẫn hiện dialog.
 * Side effect: không gửi name/relations cùng transition và không retry mù.
 * Quy tắc/Contract: status không nằm trong metadata update, Q11/M061 không bị thay đổi.
 */
async function xuLyXacNhanDoiTrangThai() {
  if (dangDoiTrangThai.value || !trangThaiCanDoi.value) return
  if (giaiDoanTrangThai.value === 'CHUA_XAC_DINH') { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  giaiDoanTrangThai.value = 'DANG_XU_LY'
  loiTrangThai.value = null
  await store.capNhatBaiTap(routeId, { status: yDinhTrangThai.value })
  if (baiTap.value.loiMutation) {
    dangDoiTrangThai.value = false
    giaiDoanTrangThai.value = baiTap.value.loiMutation.outcomeUnknown ? 'CHUA_XAC_DINH' : 'THAT_BAI'
    loiTrangThai.value = baiTap.value.loiMutation
    return
  }
  const latest = await tai()
  dangDoiTrangThai.value = false
  if (!latest) {
    giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
    loiTrangThai.value = loiTaiLai.value ?? { message: 'Đã gửi cập nhật nhưng chưa tải được trạng thái xác nhận.' }
    return
  }
  if (form.status === yDinhTrangThai.value) datLaiDoiTrangThai()
  else {
    giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
    loiTrangThai.value = { message: 'Trạng thái sau khi cập nhật chưa khớp dữ liệu authoritative.' }
  }
}
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
      v-else-if="baiTap.loiChiTiet && !daCoDuLieu"
      :thong-bao="baiTap.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="baiTap.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    /><form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <TrangThaiLoi
        v-if="loiTaiLai"
        :thong-bao="loiTaiLai.message"
        :co-the-thu-lai="true"
        :dang-thu-lai="baiTap.dangTaiChiTiet"
        @thu-lai="xuLyTaiLai"
      />
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
          bat-buoc
          :loi="layLoi('instructions')"
        >
          <template #default="{ id: idTruong, ariaDescribedby, ariaInvalid }">
            <textarea
              :id="idTruong"
              v-model="form.instructions"
              rows="4"
              maxlength="5000"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            />
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-status"
          nhan="Trạng thái"
        >
          <template #default="{ id: idTruong }">
            <select
              :id="idTruong"
              :value="form.status"
              disabled
              aria-readonly="true"
            >
              <option value="HOAT_DONG">
                Hoạt động
              </option><option value="NGUNG_SU_DUNG">
                Ngừng sử dụng
              </option>
            </select>
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-image"
          nhan="Đường dẫn hình ảnh"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.image_path"
              maxlength="2000"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="bai-tap-detail-video"
          nhan="Đường dẫn video"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.video_path"
              maxlength="2000"
            >
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
          class="nut nut--nguy-hiem"
          type="button"
          :disabled="dangLuu || dangDoiTrangThai"
          data-testid="bai-tap-status-action"
          @click="xuLyMoDoiTrangThai"
        >
          {{ form.status === 'HOAT_DONG' ? 'Ngừng sử dụng' : 'Mở lại' }}
        </button><button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Lưu bài tập' }}
        </button>
      </div>
    </form>
    <HopThoaiXacNhan
      :hien-thi="Boolean(trangThaiCanDoi)"
      :tieu-de="giaiDoanTrangThai === 'CHUA_XAC_DINH' ? 'Không xác định được kết quả' : 'Cập nhật trạng thái bài tập?'"
      mo-ta="Thay đổi trạng thái là thao tác nhạy cảm; các quan hệ bài tập hiện hữu vẫn được giữ."
      :nhan-xac-nhan="giaiDoanTrangThai === 'CHUA_XAC_DINH' ? 'Đối soát trạng thái' : (giaiDoanTrangThai === 'CO_THE_THU_LAI' ? 'Thử lại cập nhật' : 'Xác nhận')"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="datLaiDoiTrangThai"
    >
      <p
        v-if="loiTrangThai"
        role="alert"
      >
        {{ loiTrangThai.message }}
      </p>
      <p
        v-if="giaiDoanTrangThai === 'CHUA_XAC_DINH'"
        role="alert"
      >
        Không thể xác định kết quả; đối soát chỉ tải trạng thái hiện tại và không gửi lại yêu cầu.
      </p>
      <p
        v-else-if="giaiDoanTrangThai === 'CO_THE_THU_LAI'"
        role="status"
      >
        Backend vẫn ghi nhận trạng thái cũ. Bạn có thể chủ động thử lại.
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
