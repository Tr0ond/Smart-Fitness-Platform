<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import BoSuaQuyenLoiGoiTap from '../../../components/danh_muc/bo_sua_quyen_loi_goi_tap.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute()
const router = useRouter()
const store = useDanhMucStore()
const form = reactive({ code: '', name: '', price: 0, duration_days: 1, description: '', status: 'NGUNG_BAN', benefits: {} })
const dangLuu = ref(false)
const dangLuuQuyenLoi = ref(false)
const loi = ref(null)
const routeId = Number(route.params.id)
const configurationVersion = ref(null)
const loiTaiLai = ref(null)
const daCoDuLieu = ref(false)
const dangDoiTrangThai = ref(false)
const trangThaiCanDoi = ref(null)
const yDinhTrangThai = ref(null)
const giaiDoanTrangThai = ref('CHO_XAC_NHAN')
const loiTrangThai = ref(null)
function nap(duLieu) {
  if (duLieu) {
    Object.assign(form, {
      ...duLieu,
      description: Object.prototype.hasOwnProperty.call(duLieu, 'description') ? duLieu.description : '',
      benefits: { ...(duLieu.benefits ?? {}) },
    })
    if (Number.isSafeInteger(duLieu.configuration_version)) configurationVersion.value = duLieu.configuration_version
  }
}
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() {
  loiTaiLai.value = null
  const result = await store.taiChiTietGoiTap(routeId)
  if (result) {
    nap(result)
    daCoDuLieu.value = true
  }
  else loiTaiLai.value = store.goiTap.loiChiTiet
  return result
}
function xuLyTaiLai() { void tai() }
/**
 * Mục đích: cập nhật metadata gói tập sau khi form detail hợp lệ.
 * Đầu vào: name, integer price/duration và nullable description; status existing không gửi ở flow này.
 * Xử lý: khóa submit, gọi PATCH metadata rồi GET detail để nhận version authoritative.
 * Kết quả: form và configuration_version hiển thị giá trị Backend xác nhận; lỗi giữ nguyên dữ liệu nhập.
 * Side effect: một PATCH và một GET, không tự tính hoặc tăng version, không sửa snapshot cũ.
 * Quy tắc/Contract: Q01 snapshot; status transition phải qua confirmation từ list entry point.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  loi.value = null
  if (!Number.isSafeInteger(form.price) || form.price < 1 || form.price > 999999999999999) {
    loi.value = { message: 'Dữ liệu gói tập không hợp lệ.', fieldErrors: { price: ['Giá phải là số nguyên từ 1 đến 999999999999999.'] } }
    return
  }
  if (!Number.isSafeInteger(form.duration_days) || form.duration_days < 1 || form.duration_days > 65535) {
    loi.value = { message: 'Dữ liệu gói tập không hợp lệ.', fieldErrors: { duration_days: ['Thời hạn phải là số nguyên từ 1 đến 65535 ngày.'] } }
    return
  }
  dangLuu.value = true
  await store.capNhatGoiTap(routeId, { name: form.name, price: form.price, duration_days: form.duration_days, description: form.description })
  loi.value = store.goiTap.loiMutation
  dangLuu.value = false
  if (!loi.value) {
    const refreshed = await tai()
    if (!refreshed) loi.value = loiTaiLai.value
  }
}
/**
 * Mục đích: thay toàn bộ quyền lợi cho lượt mua mới theo Q01.
 * Đầu vào: benefits đã được editor normalize (AI off=0, unlimited=null).
 * Xử lý: khóa editor, PUT một lần rồi GET detail để refresh configuration_version.
 * Kết quả: benefits và version chỉ hiển thị giá trị authoritative sau refetch.
 * Side effect: không đổi snapshot các kỳ đã mua và không retry mù khi lỗi mạng.
 * Quy tắc/Contract: Backend kiểm tra ít nhất một quyền lợi hiệu lực.
 */
async function xuLyLuuQuyenLoi(value) {
  if (dangLuuQuyenLoi.value) return
  loi.value = null
  dangLuuQuyenLoi.value = true
  await store.thayTheQuyenLoiGoiTap(routeId, value)
  loi.value = store.goiTap.loiMutation
  dangLuuQuyenLoi.value = false
  if (!loi.value) {
    const refreshed = await tai()
    if (!refreshed) loi.value = loiTaiLai.value
  }
}
function xuLyQuayLai() { void router.push({ name: 'adminGoiTap' }) }

function xuLyMoDoiTrangThai() {
  loiTrangThai.value = null
  giaiDoanTrangThai.value = 'CHO_XAC_NHAN'
  trangThaiCanDoi.value = form.status
  yDinhTrangThai.value = form.status === 'DANG_BAN' ? 'NGUNG_BAN' : 'DANG_BAN'
}

function datLaiDoiTrangThai() {
  trangThaiCanDoi.value = null
  yDinhTrangThai.value = null
  loiTrangThai.value = null
  giaiDoanTrangThai.value = 'CHO_XAC_NHAN'
}

/**
 * Mục đích: đối soát transition status package được khởi tạo từ detail.
 * Đầu vào: status trước và intended đã chụp khi mở confirmation.
 * Xử lý: GET detail authoritative rồi phân biệt intended, previous và trạng thái khác.
 * Kết quả: intended đóng dialog; previous cho retry explicit; trường hợp khác unresolved.
 * Side effect: chỉ đọc, không phát sinh PATCH tự động và giữ configuration_version đã xác nhận.
 * Quy tắc/Contract: status nhạy cảm qua dialog; Q01 snapshot kỳ cũ do Backend bảo toàn.
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
    loiTrangThai.value = loiTaiLai.value ?? { message: 'Chưa xác định được trạng thái gói tập.' }
    return
  }
  if (form.status === yDinhTrangThai.value) { datLaiDoiTrangThai(); return }
  if (form.status === trangThaiCanDoi.value) { giaiDoanTrangThai.value = 'CO_THE_THU_LAI'; return }
  giaiDoanTrangThai.value = 'CHUA_XAC_DINH'
  loiTrangThai.value = { message: 'Chưa xác định được trạng thái hiện tại của gói tập.' }
}

/**
 * Mục đích: xác nhận transition status package tại detail.
 * Đầu vào: intended status; trạng thái chưa rõ chuyển sang GET-only reconcile.
 * Xử lý: PATCH chỉ status rồi GET lại detail, không gửi metadata hoặc benefits.
 * Kết quả: đóng khi GET khớp intended; lỗi 4xx/unknown/mismatch vẫn hiển thị.
 * Side effect: không retry mù và không tăng configuration_version ở client.
 * Quy tắc/Contract: Q01 snapshot giữ nguyên; Backend là authority của status/version.
 */
async function xuLyXacNhanDoiTrangThai() {
  if (dangDoiTrangThai.value || !trangThaiCanDoi.value) return
  if (giaiDoanTrangThai.value === 'CHUA_XAC_DINH') { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  giaiDoanTrangThai.value = 'DANG_XU_LY'
  loiTrangThai.value = null
  await store.capNhatGoiTap(routeId, { status: yDinhTrangThai.value })
  if (store.goiTap.loiMutation) {
    dangDoiTrangThai.value = false
    giaiDoanTrangThai.value = store.goiTap.loiMutation.outcomeUnknown ? 'CHUA_XAC_DINH' : 'THAT_BAI'
    loiTrangThai.value = store.goiTap.loiMutation
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
onMounted(tai)
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Chi tiết gói tập"
    :aria-busy="store.goiTap.dangTaiChiTiet || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Chi tiết gói tập"
      mo-ta="Chỉnh sửa metadata và quyền lợi theo hợp đồng Backend."
    />
    <TrangThaiTaiDuLieu
      v-if="store.goiTap.dangTaiChiTiet && !store.goiTap.chiTiet"
      nhan="Đang tải chi tiết gói tập…"
    />
    <TrangThaiLoi
      v-else-if="store.goiTap.loiChiTiet && !daCoDuLieu"
      :thong-bao="store.goiTap.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="store.goiTap.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    />
    <form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <TrangThaiLoi
        v-if="loiTaiLai"
        :thong-bao="loiTaiLai.message"
        :co-the-thu-lai="true"
        :dang-thu-lai="store.goiTap.dangTaiChiTiet"
        @thu-lai="xuLyTaiLai"
      />
      <p
        v-if="configurationVersion !== null"
        class="trang-danh-muc__thong-tin"
        data-testid="configuration-version"
        role="status"
      >
        Phiên bản cấu hình: {{ configurationVersion }}
      </p>
      <p
        class="trang-danh-muc__canh-bao"
        role="note"
      >
        Quyền lợi kỳ đã mua được đóng băng bằng snapshot; thay đổi tại đây không sửa quyền lợi của kỳ cũ.
      </p>
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="goi-tap-detail-code"
          nhan="Mã gói"
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
          id="goi-tap-detail-name"
          nhan="Tên gói"
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
          id="goi-tap-detail-price"
          nhan="Giá"
          bat-buoc
          :loi="layLoi('price')"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.price"
              type="number"
              min="1"
              max="999999999999999"
              step="1"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-duration"
          nhan="Thời hạn (ngày)"
          bat-buoc
          :loi="layLoi('duration_days')"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.duration_days"
              type="number"
              min="1"
              max="65535"
              step="1"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-status"
          nhan="Trạng thái"
        >
          <template #default="{ id: idTruong }">
            <select
              :id="idTruong"
              :value="form.status"
              disabled
              aria-readonly="true"
            >
              <option value="DANG_BAN">
                Đang bán
              </option><option value="NGUNG_BAN">
                Ngừng bán
              </option>
            </select>
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-description"
          nhan="Mô tả"
        >
          <template #default="{ id: idTruong }">
            <textarea
              :id="idTruong"
              v-model="form.description"
              rows="4"
              maxlength="2000"
            />
          </template>
        </TruongBieuMau>
      </div>
      <BoSuaQuyenLoiGoiTap
        :quyen-loi="form.benefits"
        :dang-luu="dangLuuQuyenLoi"
        :loi="loi"
        @luu="xuLyLuuQuyenLoi"
      />
      <p
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
          :disabled="dangLuu || dangLuuQuyenLoi || dangDoiTrangThai"
          data-testid="goi-tap-status-action"
          @click="xuLyMoDoiTrangThai"
        >
          {{ form.status === 'DANG_BAN' ? 'Ngừng bán' : 'Mở bán' }}
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
      :tieu-de="giaiDoanTrangThai === 'CHUA_XAC_DINH' ? 'Không xác định được kết quả' : 'Cập nhật trạng thái gói tập?'"
      mo-ta="Backend giữ snapshot quyền lợi của các kỳ đã mua; transition status cần được xác nhận."
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
