<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute(); const router = useRouter(); const store = useDanhMucStore(); const { baiTap, giaoAnMau } = storeToRefs(store); const routeId = Number(route.params.id); const dangLuu = ref(false); const loi = ref(null); const loiTaiLai = ref(null); const daCoDuLieu = ref(false); const dangDoiTrangThai = ref(false); const trangThaiCanDoi = ref(null); const yDinhTrangThai = ref(null); const giaiDoanTrangThai = ref('CHO_XAC_NHAN'); const loiTrangThai = ref(null)
const form = reactive({ code: '', name: '', goal: '', level: '', sessions_per_week: 1, description: '', status: 'HOAT_DONG', days: [] })
function nap(item) {
  if (item) Object.assign(form, {
    ...item,
    description: Object.prototype.hasOwnProperty.call(item, 'description') ? item.description : '',
    days: (item.days ?? []).map((day) => ({
      ...day,
      exercises: (day.exercises ?? []).map((exercise) => ({
        ...exercise,
        notes: Object.prototype.hasOwnProperty.call(exercise, 'notes') ? exercise.notes : null,
      })),
    })),
  })
}
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() {
  loiTaiLai.value = null
  const result = await store.taiChiTietGiaoAnMau(routeId)
  if (result) { nap(result); daCoDuLieu.value = true }
  else loiTaiLai.value = store.giaoAnMau.loiChiTiet
  return result
}
function xuLyTaiLai() { void tai() }
/**
 * Mục đích: cập nhật metadata giáo án mẫu từ detail form.
 * Đầu vào: name/goal/level/sessions và nullable description; status existing là read-only.
 * Xử lý: khóa double-submit, PATCH allow-list không có days rồi GET lại authoritative.
 * Kết quả: form nhận DTO mới; lỗi 422 giữ dữ liệu để sửa, refresh lỗi vẫn hiển thị.
 * Side effect: một PATCH và một GET; không sửa cây lịch sử hoặc tự tạo revision.
 * Quy tắc/Contract: days chỉ đổi qua copy-on-write revision, Backend giữ content_version.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  dangLuu.value = true
  loi.value = null
  await store.capNhatGiaoAnMau(routeId, { name: form.name, goal: form.goal, level: form.level, sessions_per_week: form.sessions_per_week, description: form.description })
  loi.value = store.giaoAnMau.loiMutation
  dangLuu.value = false
  if (!loi.value) {
    const refreshed = await tai()
    if (!refreshed) loi.value = loiTaiLai.value
  }
}
function xuLyQuayLai() { void router.push({ name: 'adminGiaoAnMau' }) }
function xuLyTaoPhienBan() { void router.push({ name: 'adminTaoPhienBanGiaoAnMau', params: { id: routeId } }) }

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
 * Mục đích: đối soát status template sau khi transition chưa rõ kết quả.
 * Đầu vào: status trước và intended đã chụp từ detail confirmation.
 * Xử lý: GET detail authoritative, không PATCH lại trong lượt đối soát.
 * Kết quả: intended đóng dialog; previous cho retry explicit; trạng thái khác unresolved.
 * Side effect: giữ cây days/content_version và dữ liệu nullable đã nạp.
 * Quy tắc/Contract: status nhạy cảm qua dialog; days chỉ thay bằng copy-on-write revision.
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
    loiTrangThai.value = loiTaiLai.value ?? { message: 'Chưa xác định được trạng thái giáo án mẫu.' }
    return
  }
  if (form.status === yDinhTrangThai.value) { datLaiDoiTrangThai(); return }
  if (form.status === trangThaiCanDoi.value) { giaiDoanTrangThai.value = 'CO_THE_THU_LAI'; return }
  giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
  loiTrangThai.value = { message: 'Chưa xác định được trạng thái hiện tại của giáo án mẫu.' }
}

/**
 * Mục đích: xác nhận transition status template từ detail.
 * Đầu vào: intended status; khi chưa rõ, nút chuyển sang GET-only reconcile.
 * Xử lý: PATCH chỉ status rồi GET lại detail để xác minh.
 * Kết quả: đóng khi authoritative status khớp; 4xx/unknown/mismatch giữ dialog.
 * Side effect: không gửi days và không retry mù; revision/history vẫn bất biến.
 * Quy tắc/Contract: Backend là authority của lifecycle và content_version.
 */
async function xuLyXacNhanDoiTrangThai() {
  if (dangDoiTrangThai.value || !trangThaiCanDoi.value) return
  if (giaiDoanTrangThai.value === 'CHUA_XAC_DINH') { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  giaiDoanTrangThai.value = 'DANG_XU_LY'
  loiTrangThai.value = null
  await store.capNhatGiaoAnMau(routeId, { status: yDinhTrangThai.value })
  if (giaoAnMau.value.loiMutation) {
    dangDoiTrangThai.value = false
    giaiDoanTrangThai.value = giaoAnMau.value.loiMutation.outcomeUnknown ? 'CHUA_XAC_DINH' : 'THAT_BAI'
    loiTrangThai.value = giaoAnMau.value.loiMutation
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
      v-else-if="giaoAnMau.loiChiTiet && !daCoDuLieu"
      :thong-bao="giaoAnMau.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="giaoAnMau.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    /><form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <TrangThaiLoi
        v-if="loiTaiLai"
        :thong-bao="loiTaiLai.message"
        :co-the-thu-lai="true"
        :dang-thu-lai="giaoAnMau.dangTaiChiTiet"
        @thu-lai="xuLyTaiLai"
      />
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
              max="7"
              step="1"
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
          class="nut nut--nguy-hiem"
          type="button"
          :disabled="dangLuu || dangDoiTrangThai"
          data-testid="giao-an-status-action"
          @click="xuLyMoDoiTrangThai"
        >
          {{ form.status === 'HOAT_DONG' ? 'Ngừng sử dụng' : 'Mở lại' }}
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
    <HopThoaiXacNhan
      :hien-thi="Boolean(trangThaiCanDoi)"
      :tieu-de="giaiDoanTrangThai === 'CHUA_XAC_DINH' ? 'Không xác định được kết quả' : 'Cập nhật trạng thái giáo án?'"
      mo-ta="Bản ghi và cây phiên bản được giữ để bảo toàn lịch sử; transition status cần được xác nhận."
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
