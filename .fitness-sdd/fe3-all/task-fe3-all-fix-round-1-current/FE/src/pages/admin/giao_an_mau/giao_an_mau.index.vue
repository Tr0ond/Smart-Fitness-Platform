<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { giaoAnMau } = storeToRefs(store)
const dangDoiTrangThai = ref(false)
const itemCanDoiTrangThai = ref(null)
const yDinhTrangThai = ref(null)
const trangThaiTruocDo = ref(null)

const nhanTrangThai = computed(() => ({ HOAT_DONG: 'Hoạt động', NGUNG_SU_DUNG: 'Ngừng sử dụng' }))

function layNhan(status) {
  return nhanTrangThai.value[status] ?? 'Không xác định'
}

function xuLyMoTaoMoi() {
  void router.push({ name: 'adminTaoGiaoAnMau' })
}

function xuLyMoChiTiet(item) {
  if (item?.id) void router.push({ name: 'adminChiTietGiaoAnMau', params: { id: item.id } })
}

function xuLyMoDoiTrangThai(item) {
  itemCanDoiTrangThai.value = item
  trangThaiTruocDo.value = item?.status ?? null
  yDinhTrangThai.value = item?.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG'
}

function xuLyDongDoiTrangThai() {
  if (!dangDoiTrangThai.value) itemCanDoiTrangThai.value = null
}

function xuLyTaiLai() {
  void store.taiDanhSachGiaoAnMau()
}

/** Reconcile an uncertain template status by GET only; never retry the PATCH. */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !itemCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.taiDanhSachGiaoAnMau()
  const current = giaoAnMau.value.danhSach.find((item) => item.id === itemCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
}

/**
 * Confirm a template status change and close only after authoritative refresh.
 * Input is the selected row; failures stay in the accessible dialog.
 */
async function xuLyXacNhanDoiTrangThai() {
  const item = itemCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (giaoAnMau.value.loiMutation?.outcomeUnknown) {
    await xuLyDoiSoatTrangThai()
    return
  }
  dangDoiTrangThai.value = true
  await store.capNhatGiaoAnMau(item.id, { status: yDinhTrangThai.value })
  if (giaoAnMau.value.loiMutation) {
    dangDoiTrangThai.value = false
    return
  }
  await store.taiDanhSachGiaoAnMau()
  if (giaoAnMau.value.loi) {
    dangDoiTrangThai.value = false
    return
  }
  const current = giaoAnMau.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) itemCanDoiTrangThai.value = null
  if (current?.status === trangThaiTruocDo.value) return
}

onMounted(() => {
  void store.taiDanhSachGiaoAnMau()
})
</script>

<template>
  <section
    class="trang-danh-muc"
    aria-label="Danh sách giáo án mẫu"
    :aria-busy="giaoAnMau.dangTai"
  >
    <TieuDeTrang
      tieu-de="Giáo án mẫu"
      mo-ta="Quản lý giáo án và phiên bản copy-on-write từ Backend."
    >
      <template #hanhDong>
        <button
          class="nut nut--chinh"
          type="button"
          @click="xuLyMoTaoMoi"
        >
          Tạo giáo án mẫu
        </button>
      </template>
    </TieuDeTrang>
    <TrangThaiTaiDuLieu
      v-if="giaoAnMau.dangTai && !giaoAnMau.daTaiLanDau"
      nhan="Đang tải giáo án mẫu…"
    />
    <TrangThaiLoi
      v-else-if="giaoAnMau.loi"
      :thong-bao="giaoAnMau.loi.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="giaoAnMau.dangTai"
      @thu-lai="xuLyTaiLai"
    />
    <TrangThaiTrong
      v-else-if="giaoAnMau.daTaiLanDau && giaoAnMau.danhSach.length === 0"
      tieu-de="Chưa có giáo án mẫu"
    />
    <BangDuLieu
      v-else
      :cot="[]"
      :hang="giaoAnMau.danhSach"
      tieu-de="Danh sách giáo án mẫu"
    >
      <template #tieuDeCot>
        <tr>
          <th scope="col">
            Mã
          </th><th scope="col">
            Tên
          </th><th scope="col">
            Mục tiêu
          </th><th scope="col">
            Cấp độ
          </th><th scope="col">
            Số buổi
          </th><th scope="col">
            Trạng thái
          </th><th scope="col">
            Thao tác
          </th>
        </tr>
      </template>
      <template #hang="{ hang }">
        <tr
          v-for="item in hang"
          :key="item.id"
        >
          <td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.goal }}</td><td>{{ item.level }}</td><td>{{ item.sessions_per_week }} ({{ item.day_count ?? 0 }} ngày)</td>
          <td>
            <HuyHieuTrangThai
              :trang-thai="item.status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao'"
              :nhan="layNhan(item.status)"
            />
          </td>
          <td>
            <button
              class="nut nut--lien-ket"
              type="button"
              @click="xuLyMoChiTiet(item)"
            >
              Chi tiết
            </button><button
              class="nut nut--phu"
              type="button"
              @click="xuLyMoDoiTrangThai(item)"
            >
              {{ item.status === 'HOAT_DONG' ? 'Ngừng sử dụng' : 'Mở lại' }}
            </button>
          </td>
        </tr>
      </template>
    </BangDuLieu>
    <HopThoaiXacNhan
      :hien-thi="Boolean(itemCanDoiTrangThai)"
      :tieu-de="giaoAnMau.loiMutation?.outcomeUnknown ? 'Không xác định được kết quả' : 'Cập nhật trạng thái giáo án?'"
      mo-ta="Bản ghi được giữ để bảo toàn lịch sử phiên bản. Khi kết quả chưa rõ, đối soát chỉ tải trạng thái hiện tại."
      :nhan-xac-nhan="giaoAnMau.loiMutation?.outcomeUnknown ? 'Đối soát trạng thái' : 'Xác nhận'"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="xuLyDongDoiTrangThai"
    >
      <p
        v-if="giaoAnMau.loiMutation"
        role="alert"
      >
        {{ giaoAnMau.loiMutation.message }}
      </p>
      <p
        v-if="giaoAnMau.loiMutation?.outcomeUnknown"
        role="alert"
      >
        Không thể xác định kết quả cập nhật. Không gửi lại yêu cầu tự động.
      </p>
      <p
        v-if="giaoAnMau.loi"
        role="alert"
      >
        {{ giaoAnMau.loi.message }}
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
