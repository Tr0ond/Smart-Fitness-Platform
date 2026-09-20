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
const { nhomCo } = storeToRefs(store)
const dangLuu = ref(false)
const dangDoiTrangThai = ref(false)
const itemCanDoiTrangThai = ref(null)
const yDinhTrangThai = ref(null)
const trangThaiTruocDo = ref(null)
const loi = ref(null)
const dangChinhSua = ref(false)
const form = reactive({ id: null, code: '', name: '', description: '', status: 'HOAT_DONG' })
function xuLyDatLaiForm() { Object.assign(form, { id: null, code: '', name: '', description: '', status: 'HOAT_DONG' }); dangChinhSua.value = false; loi.value = null }
function xuLyChonItem(item) { Object.assign(form, { id: item.id, code: item.code, name: item.name, description: item.description ?? '', status: item.status }); dangChinhSua.value = true; loi.value = null }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
/** Save a muscle-group record while retaining M061 relation history. */
async function xuLyLuu() {
  dangLuu.value = true; loi.value = null
  const payload = dangChinhSua.value ? { name: form.name, description: form.description, status: form.status } : { code: form.code, name: form.name, description: form.description, status: form.status }
  const result = dangChinhSua.value ? await store.capNhatNhomCo(form.id, payload) : await store.taoNhomCo(payload)
  loi.value = store.nhomCo.loiMutation
  dangLuu.value = false
  if (result) { xuLyDatLaiForm(); await store.taiDanhSachNhomCo({ boQuaCache: true }) }
}
function xuLyMoDoiTrangThai(item) { itemCanDoiTrangThai.value = item; trangThaiTruocDo.value = item?.status ?? null; yDinhTrangThai.value = item?.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG' }
function xuLyDongDoiTrangThai() { if (!dangDoiTrangThai.value) itemCanDoiTrangThai.value = null }
function xuLyTaiLai() { void store.taiDanhSachNhomCo() }
/** Reconcile uncertain M061 status using a cache-bypassing authoritative GET. */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !itemCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.taiDanhSachNhomCo({ boQuaCache: true })
  const current = nhomCo.value.danhSach.find((item) => item.id === itemCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
}
/** Confirm one M061 status change; failure and uncertainty remain visible. */
async function xuLyXacNhanDoiTrangThai() {
  const item = itemCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (nhomCo.value.loiMutation?.outcomeUnknown) { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  await store.capNhatNhomCo(item.id, { status: yDinhTrangThai.value })
  if (nhomCo.value.loiMutation) { dangDoiTrangThai.value = false; return }
  await store.taiDanhSachNhomCo({ boQuaCache: true })
  if (nhomCo.value.loi) { dangDoiTrangThai.value = false; return }
  const current = nhomCo.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
}
onMounted(() => { void store.taiDanhSachNhomCo() })
</script>

<template>
  <section
    class="trang-danh-muc"
    aria-label="Danh sách nhóm cơ"
    :aria-busy="nhomCo.dangTai || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Nhóm cơ"
      mo-ta="Hiển thị cả nhóm cơ ngừng sử dụng để bảo toàn quan hệ bài tập hiện hữu."
    >
      <template #hanhDong>
        <button
          class="nut nut--phu"
          type="button"
          @click="xuLyDatLaiForm"
        >
          Thêm nhóm cơ
        </button>
      </template>
    </TieuDeTrang>
    <div class="trang-danh-muc__khung-hai-cot">
      <form
        class="trang-danh-muc__form"
        @submit.prevent="xuLyLuu"
      >
        <h2>{{ dangChinhSua ? 'Sửa nhóm cơ' : 'Thêm nhóm cơ' }}</h2>
        <TruongBieuMau
          id="nhom-co-code"
          nhan="Mã nhóm cơ"
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
          id="nhom-co-name"
          nhan="Tên nhóm cơ"
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
          id="nhom-co-description"
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
          id="nhom-co-status"
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
        <p
          class="trang-danh-muc__canh-bao"
          role="note"
        >
          Khi ngừng sử dụng, quan hệ hiện hữu phải được giữ và Backend sẽ từ chối quan hệ mới nếu không hợp lệ.
        </p>
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
            {{ dangLuu ? 'Đang lưu…' : 'Lưu nhóm cơ' }}
          </button>
        </div>
      </form>
      <div>
        <TrangThaiTaiDuLieu
          v-if="nhomCo.dangTai && !nhomCo.daTaiLanDau"
          nhan="Đang tải nhóm cơ…"
        />
        <TrangThaiLoi
          v-else-if="nhomCo.loi"
          :thong-bao="nhomCo.loi.message"
          :co-the-thu-lai="true"
          :dang-thu-lai="nhomCo.dangTai"
          @thu-lai="xuLyTaiLai"
        />
        <TrangThaiTrong
          v-else-if="nhomCo.daTaiLanDau && nhomCo.danhSach.length === 0"
          tieu-de="Chưa có nhóm cơ"
        />
        <table
          v-else
          class="bang-du-lieu"
          aria-label="Danh sách nhóm cơ"
        >
          <caption>Danh sách nhóm cơ</caption><thead>
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
              v-for="item in nhomCo.danhSach"
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
      :tieu-de="nhomCo.loiMutation?.outcomeUnknown ? 'Không xác định được kết quả' : 'Cập nhật trạng thái nhóm cơ?'"
      mo-ta="Bản ghi không bị xóa; các quan hệ bài tập hiện hữu vẫn được Backend bảo toàn."
      :nhan-xac-nhan="nhomCo.loiMutation?.outcomeUnknown ? 'Đối soát trạng thái' : 'Xác nhận'"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="xuLyDongDoiTrangThai"
    >
      <p
        v-if="nhomCo.loiMutation"
        role="alert"
      >
        {{ nhomCo.loiMutation.message }}
      </p><p
        v-if="nhomCo.loiMutation?.outcomeUnknown"
        role="alert"
      >
        Không thể xác định kết quả. Đối soát chỉ tải trạng thái hiện tại và không gửi lại yêu cầu.
      </p><p
        v-if="nhomCo.loi"
        role="alert"
      >
        {{ nhomCo.loi.message }}
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
