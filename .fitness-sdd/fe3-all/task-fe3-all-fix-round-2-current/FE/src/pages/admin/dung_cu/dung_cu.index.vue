<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const store = useDanhMucStore()
const { dungCu } = storeToRefs(store)
const dangLuu = ref(false)
const dangDoiTrangThai = ref(false)
const itemCanDoiTrangThai = ref(null)
const yDinhTrangThai = ref(null)
const trangThaiTruocDo = ref(null)
const giaiDoanDoiSoat = ref('CHO_XAC_NHAN')
const loiDoiSoat = ref(null)
const loi = ref(null)
const dangChinhSua = ref(false)
const form = reactive({ id: null, code: '', name: '', description: '', status: 'HOAT_DONG' })
function xuLyDatLaiForm() { Object.assign(form, { id: null, code: '', name: '', description: '', status: 'HOAT_DONG' }); dangChinhSua.value = false; loi.value = null }
function xuLyChonItem(item) { Object.assign(form, { id: item.id, code: item.code, name: item.name, description: Object.prototype.hasOwnProperty.call(item, 'description') ? item.description : '', status: item.status }); dangChinhSua.value = true; loi.value = null }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
/**
 * Mục đích: lưu metadata dụng cụ qua đúng mutation được phép.
 * Đầu vào: form tạo mới hoặc form sửa hiện tại; status của bản ghi cũ chỉ đổi ở dialog nhạy cảm.
 * Xử lý: chặn double-submit, dựng payload theo create/edit rồi gọi store một lần.
 * Kết quả: reset form và GET danh sách fresh sau thành công; lỗi 422 giữ nguyên form.
 * Side effect: tạo hoặc PATCH metadata; refresh dùng boQuaCache để không đọc cache cũ.
 * Quy tắc/Contract: code bất biến, không DELETE và trạng thái existing không nằm trong metadata PATCH.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  dangLuu.value = true; loi.value = null
  const payload = dangChinhSua.value
    ? { name: form.name, description: form.description }
    : { code: form.code, name: form.name, description: form.description, status: form.status }
  const result = dangChinhSua.value ? await store.capNhatDungCu(form.id, payload) : await store.taoDungCu(payload)
  loi.value = store.dungCu.loiMutation
  dangLuu.value = false
  if (result) { xuLyDatLaiForm(); await store.taiDanhSachDungCu({ boQuaCache: true }) }
}
function xuLyMoDoiTrangThai(item) {
  loiDoiSoat.value = null
  giaiDoanDoiSoat.value = 'CHO_XAC_NHAN'
  itemCanDoiTrangThai.value = item
  trangThaiTruocDo.value = item?.status ?? null
  yDinhTrangThai.value = item?.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG'
}
function datLaiDoiSoat() {
  itemCanDoiTrangThai.value = null
  loiDoiSoat.value = null
  giaiDoanDoiSoat.value = 'CHO_XAC_NHAN'
}
function xuLyDongDoiTrangThai() { if (!dangDoiTrangThai.value) datLaiDoiSoat() }
function xuLyTaiLai() { void store.taiDanhSachDungCu() }
/**
 * Mục đích: đối soát status dụng cụ sau outcome unknown.
 * Đầu vào: item, trạng thái trước và intended đã chụp khi mở dialog.
 * Xử lý: GET bỏ qua cache rồi đối chiếu intended/previous với bản ghi authoritative.
 * Kết quả: intended đóng dialog; previous chuyển sang retry explicit; trạng thái khác vẫn unresolved.
 * Side effect: chỉ đọc, không gửi lại PATCH và không để lỗi store cũ điều khiển dialog mới.
 * Quy tắc/Contract: thiết bị không bị hard-delete; Backend là authority của lifecycle.
 */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !itemCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  giaiDoanDoiSoat.value = 'DANG_DOI_SOAT'
  loiDoiSoat.value = null
  await store.taiDanhSachDungCu({ boQuaCache: true })
  const current = dungCu.value.danhSach.find((item) => item.id === itemCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (dungCu.value.loi) {
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = dungCu.value.loi
    return
  }
  if (current?.status === yDinhTrangThai.value) { datLaiDoiSoat(); return }
  if (current?.status === trangThaiTruocDo.value) { giaiDoanDoiSoat.value = 'CO_THE_THU_LAI'; return }
  giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
  loiDoiSoat.value = { message: 'Chưa xác định được trạng thái hiện tại của dụng cụ.' }
}
/**
 * Mục đích: xác nhận một transition status dụng cụ.
 * Đầu vào: item đã chụp và intended status; lần nhấn sau previous là retry có chủ ý.
 * Xử lý: PATCH status rồi GET bỏ qua cache để kiểm chứng kết quả.
 * Kết quả: chỉ đóng khi GET trả intended; 4xx/unknown/mismatch giữ dialog và lỗi.
 * Side effect: tối đa một PATCH cho mỗi lần nhấn, không retry mù.
 * Quy tắc/Contract: status nhạy cảm phải qua HopThoaiXacNhan và giữ quan hệ Q12.
 */
async function xuLyXacNhanDoiTrangThai() {
  const item = itemCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (giaiDoanDoiSoat.value === 'CHUA_XAC_DINH') { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  giaiDoanDoiSoat.value = 'DANG_XU_LY'
  loiDoiSoat.value = null
  await store.capNhatDungCu(item.id, { status: yDinhTrangThai.value })
  if (dungCu.value.loiMutation) {
    dangDoiTrangThai.value = false
    giaiDoanDoiSoat.value = dungCu.value.loiMutation.outcomeUnknown ? 'CHUA_XAC_DINH' : 'THAT_BAI'
    loiDoiSoat.value = dungCu.value.loiMutation
    return
  }
  await store.taiDanhSachDungCu({ boQuaCache: true })
  if (dungCu.value.loi) {
    dangDoiTrangThai.value = false
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = dungCu.value.loi
    return
  }
  const current = dungCu.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) datLaiDoiSoat()
  else {
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = { message: 'Trạng thái sau khi cập nhật chưa khớp dữ liệu authoritative.' }
  }
}
onMounted(() => { void store.taiDanhSachDungCu() })
</script>

<template>
  <section
    class="trang-danh-muc"
    aria-label="Danh sách dụng cụ"
    :aria-busy="dungCu.dangTai || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Dụng cụ"
      mo-ta="Quản lý danh mục dụng cụ; mã đã tạo không thể thay đổi."
    >
      <template #hanhDong>
        <button
          class="nut nut--phu"
          type="button"
          @click="xuLyDatLaiForm"
        >
          Thêm dụng cụ
        </button>
      </template>
    </TieuDeTrang>
    <div class="trang-danh-muc__khung-hai-cot">
      <form
        class="trang-danh-muc__form"
        @submit.prevent="xuLyLuu"
      >
        <h2>{{ dangChinhSua ? 'Sửa dụng cụ' : 'Thêm dụng cụ' }}</h2>
        <TruongBieuMau
          id="dung-cu-code"
          nhan="Mã dụng cụ"
          :bat-buoc="!dangChinhSua"
          :loi="layLoi('code')"
        >
          <template #default="{ id, ariaDescribedby, ariaInvalid }">
            <input
              :id="id"
              v-model="form.code"
              :readonly="dangChinhSua"
              :aria-readonly="dangChinhSua"
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
              required
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="dung-cu-name"
          nhan="Tên dụng cụ"
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
        </TruongBieuMau>
        <TruongBieuMau
          id="dung-cu-description"
          nhan="Mô tả"
        >
          <template #default="{ id }">
            <textarea
              :id="id"
              v-model="form.description"
              rows="3"
              maxlength="2000"
            />
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="dung-cu-status"
          nhan="Trạng thái"
        >
          <template #default="{ id }">
            <select
              :id="id"
              v-model="form.status"
              :disabled="dangChinhSua || dangLuu"
              :aria-readonly="dangChinhSua"
            >
              <option value="HOAT_DONG">
                Hoạt động
              </option><option value="NGUNG_SU_DUNG">
                Ngừng sử dụng
              </option>
            </select>
          </template>
        </TruongBieuMau>
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
            @click="xuLyDatLaiForm"
          >
            Hủy
          </button><button
            class="nut nut--chinh"
            type="submit"
            :disabled="dangLuu"
          >
            {{ dangLuu ? 'Đang lưu…' : 'Lưu dụng cụ' }}
          </button>
        </div>
      </form>
      <div>
        <TrangThaiTaiDuLieu
          v-if="dungCu.dangTai && !dungCu.daTaiLanDau"
          nhan="Đang tải dụng cụ…"
        />
        <TrangThaiLoi
          v-else-if="dungCu.loi"
          :thong-bao="dungCu.loi.message"
          :co-the-thu-lai="true"
          :dang-thu-lai="dungCu.dangTai"
          @thu-lai="xuLyTaiLai"
        />
        <TrangThaiTrong
          v-else-if="dungCu.daTaiLanDau && dungCu.danhSach.length === 0"
          tieu-de="Chưa có dụng cụ"
        />
        <table
          v-else
          class="bang-du-lieu"
          aria-label="Danh sách dụng cụ"
        >
          <caption>Danh sách dụng cụ</caption><thead>
            <tr>
              <th scope="col">
                Mã
              </th><th scope="col">
                Tên
              </th><th scope="col">
                Trạng thái
              </th><th scope="col">
                Thao tác
              </th>
            </tr>
          </thead><tbody>
            <tr
              v-for="item in dungCu.danhSach"
              :key="item.id"
            >
              <td>{{ item.code }}</td><td>{{ item.name }}</td><td>
                <HuyHieuTrangThai
                  :trang-thai="item.status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao'"
                  :nhan="item.status === 'HOAT_DONG' ? 'Hoạt động' : 'Ngừng sử dụng'"
                />
              </td><td>
                <button
                  class="nut nut--phu"
                  type="button"
                  @click="xuLyChonItem(item)"
                >
                  Sửa
                </button><button
                  class="nut nut--nguy-hiem"
                  type="button"
                  @click="xuLyMoDoiTrangThai(item)"
                >
                  {{ item.status === 'HOAT_DONG' ? 'Ngừng sử dụng' : 'Mở lại' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <HopThoaiXacNhan
      :hien-thi="Boolean(itemCanDoiTrangThai)"
      :tieu-de="giaiDoanDoiSoat === 'CHUA_XAC_DINH' ? 'Không xác định được kết quả' : 'Cập nhật trạng thái dụng cụ?'"
      mo-ta="Không xóa bản ghi; quan hệ bài tập hiện hữu được Backend bảo toàn."
      :nhan-xac-nhan="giaiDoanDoiSoat === 'CHUA_XAC_DINH' ? 'Đối soát trạng thái' : (giaiDoanDoiSoat === 'CO_THE_THU_LAI' ? 'Thử lại cập nhật' : 'Xác nhận')"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="xuLyDongDoiTrangThai"
    >
      <p
        v-if="loiDoiSoat"
        role="alert"
      >
        {{ loiDoiSoat.message }}
      </p><p
        v-if="giaiDoanDoiSoat === 'CHUA_XAC_DINH'"
        role="alert"
      >
        Không thể xác định kết quả. Đối soát chỉ tải trạng thái hiện tại và không gửi lại yêu cầu.
      </p><p
        v-else-if="giaiDoanDoiSoat === 'CO_THE_THU_LAI'"
        role="status"
      >
        Backend vẫn ghi nhận trạng thái cũ. Bạn có thể chủ động thử lại một lần nữa.
      </p><p
        v-if="dungCu.loi"
        role="alert"
      >
        {{ dungCu.loi.message }}
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
