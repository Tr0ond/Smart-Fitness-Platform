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
const loi = ref(null)
const dangChinhSua = ref(false)
const form = reactive({ id: null, code: '', name: '', description: '', status: 'HOAT_DONG' })
function xuLyDatLaiForm() { Object.assign(form, { id: null, code: '', name: '', description: '', status: 'HOAT_DONG' }); dangChinhSua.value = false; loi.value = null }
function xuLyChonItem(item) { Object.assign(form, { id: item.id, code: item.code, name: item.name, description: item.description ?? '', status: item.status }); dangChinhSua.value = true; loi.value = null }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
/** Save equipment metadata with native validation and one guarded mutation. */
async function xuLyLuu() {
  dangLuu.value = true; loi.value = null
  const payload = dangChinhSua.value ? { name: form.name, description: form.description, status: form.status } : { code: form.code, name: form.name, description: form.description, status: form.status }
  const result = dangChinhSua.value ? await store.capNhatDungCu(form.id, payload) : await store.taoDungCu(payload)
  loi.value = store.dungCu.loiMutation
  dangLuu.value = false
  if (result) { xuLyDatLaiForm(); await store.taiDanhSachDungCu({ boQuaCache: true }) }
}
function xuLyMoDoiTrangThai(item) { itemCanDoiTrangThai.value = item; trangThaiTruocDo.value = item?.status ?? null; yDinhTrangThai.value = item?.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG' }
function xuLyDongDoiTrangThai() { if (!dangDoiTrangThai.value) itemCanDoiTrangThai.value = null }
function xuLyTaiLai() { void store.taiDanhSachDungCu() }
/**
 * Reconcile an uncertain equipment status using a cache-bypassing GET only.
 * The intended, previous, and current statuses are compared before any close.
 */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !itemCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.taiDanhSachDungCu({ boQuaCache: true })
  const current = dungCu.value.danhSach.find((item) => item.id === itemCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
}
/**
 * Confirm one equipment status mutation and close only after authoritative refresh.
 * Failed and outcome-unknown mutations remain in the dialog for an explicit action.
 */
async function xuLyXacNhanDoiTrangThai() {
  const item = itemCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (dungCu.value.loiMutation?.outcomeUnknown) { await xuLyDoiSoatTrangThai(); return }
  dangDoiTrangThai.value = true
  await store.capNhatDungCu(item.id, { status: yDinhTrangThai.value })
  if (dungCu.value.loiMutation) { dangDoiTrangThai.value = false; return }
  await store.taiDanhSachDungCu({ boQuaCache: true })
  if (dungCu.value.loi) { dangDoiTrangThai.value = false; return }
  const current = dungCu.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
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
      :tieu-de="dungCu.loiMutation?.outcomeUnknown ? 'Không xác định được kết quả' : 'Cập nhật trạng thái dụng cụ?'"
      mo-ta="Không xóa bản ghi; quan hệ bài tập hiện hữu được Backend bảo toàn."
      :nhan-xac-nhan="dungCu.loiMutation?.outcomeUnknown ? 'Đối soát trạng thái' : 'Xác nhận'"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="xuLyDongDoiTrangThai"
    >
      <p
        v-if="dungCu.loiMutation"
        role="alert"
      >
        {{ dungCu.loiMutation.message }}
      </p><p
        v-if="dungCu.loiMutation?.outcomeUnknown"
        role="alert"
      >
        Không thể xác định kết quả. Đối soát chỉ tải trạng thái hiện tại và không gửi lại yêu cầu.
      </p><p
        v-if="dungCu.loi"
        role="alert"
      >
        {{ dungCu.loi.message }}
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
