<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { goiTap } = storeToRefs(store)
const goiTapCanDoiTrangThai = ref(null)
const dangDoiTrangThai = ref(false)
const yDinhTrangThai = ref(null)
const trangThaiTruocDo = ref(null)
const giaiDoanDoiSoat = ref('CHO_XAC_NHAN')
const loiDoiSoat = ref(null)

const nhanTrangThai = computed(() => ({ DANG_BAN: 'Đang bán', NGUNG_BAN: 'Ngừng bán' }))
const kieuTrangThai = (status) => status === 'DANG_BAN' ? 'thanh_cong' : 'canh_bao'

function layNhan(status) { return nhanTrangThai.value[status] ?? status ?? 'Không xác định' }
function xuLyMoTrangTao() { void router.push({ name: 'adminTaoGoiTap' }) }
function xuLyMoChiTiet(item) {
  if (item?.id) void router.push({ name: 'adminChiTietGoiTap', params: { id: item.id } })
}
function xuLyMoDoiTrangThai(item) {
  loiDoiSoat.value = null
  giaiDoanDoiSoat.value = 'CHO_XAC_NHAN'
  goiTapCanDoiTrangThai.value = item
  trangThaiTruocDo.value = item?.status ?? null
  yDinhTrangThai.value = item?.status === 'DANG_BAN' ? 'NGUNG_BAN' : 'DANG_BAN'
}
function xuLyDongDoiTrangThai() {
  if (dangDoiTrangThai.value) return
  goiTapCanDoiTrangThai.value = null
  loiDoiSoat.value = null
  giaiDoanDoiSoat.value = 'CHO_XAC_NHAN'
}
function xuLyTaiLai() { void store.taiDanhSachGoiTap() }

function datLaiDoiSoat() {
  goiTapCanDoiTrangThai.value = null
  loiDoiSoat.value = null
  giaiDoanDoiSoat.value = 'CHO_XAC_NHAN'
}

/**
 * Mục đích: đối soát mutation trạng thái gói tập khi kết quả mạng chưa rõ.
 * Đầu vào: item hiện tại cùng trạng thái trước đó và trạng thái dự định trong dialog.
 * Xử lý: thực hiện đúng một GET rồi so sánh trạng thái authoritative với hai mốc đã chụp.
 * Kết quả: đóng khi đã đạt trạng thái đích; cho phép retry riêng nếu vẫn ở trạng thái cũ.
 * Side effect: không gửi PATCH trong lúc đối soát và luôn giữ dialog khi thiếu dữ liệu.
 * Quy tắc/Contract: Backend là authority; Q01 snapshot kỳ đã mua không bị suy diễn từ UI.
 */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !goiTapCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  giaiDoanDoiSoat.value = 'DANG_DOI_SOAT'
  loiDoiSoat.value = null
  await store.taiDanhSachGoiTap()
  const current = goiTap.value.danhSach.find((item) => item.id === goiTapCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (goiTap.value.loi) {
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = goiTap.value.loi
    return
  }
  if (current?.status === yDinhTrangThai.value) {
    datLaiDoiSoat()
    return
  }
  if (current?.status === trangThaiTruocDo.value) {
    giaiDoanDoiSoat.value = 'CO_THE_THU_LAI'
    return
  }
  giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
  loiDoiSoat.value = { message: 'Chưa xác định được trạng thái hiện tại của gói tập.' }
}
/**
 * Mục đích: xác nhận một chuyển trạng thái gói tập từ dialog nhạy cảm.
 * Đầu vào: item đã chụp cùng intended status; lần nhấn lại sau đối soát là retry có chủ ý.
 * Xử lý: PATCH tối đa một lần cho mỗi lần xác nhận rồi GET authoritative để kiểm chứng.
 * Kết quả: đóng khi GET trả intended; lỗi thường và unknown vẫn để dialog hiển thị.
 * Side effect: không retry tự động; store chỉ invalidate danh sách sau mutation thành công.
 * Quy tắc/Contract: status là sensitive action, Q01 snapshot cũ vẫn do Backend giữ.
 */
async function xuLyXacNhanDoiTrangThai() {
  const item = goiTapCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (giaiDoanDoiSoat.value === 'CHUA_XAC_DINH') {
    await xuLyDoiSoatTrangThai()
    return
  }
  dangDoiTrangThai.value = true
  giaiDoanDoiSoat.value = 'DANG_XU_LY'
  loiDoiSoat.value = null
  await store.capNhatGoiTap(item.id, { status: yDinhTrangThai.value })
  if (goiTap.value.loiMutation) {
    dangDoiTrangThai.value = false
    giaiDoanDoiSoat.value = goiTap.value.loiMutation.outcomeUnknown ? 'CHUA_XAC_DINH' : 'THAT_BAI'
    loiDoiSoat.value = goiTap.value.loiMutation
    return
  }
  await store.taiDanhSachGoiTap()
  if (goiTap.value.loi) {
    dangDoiTrangThai.value = false
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = goiTap.value.loi
    return
  }
  const current = goiTap.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) datLaiDoiSoat()
  else {
    giaiDoanDoiSoat.value = 'CHUA_XAC_DINH'
    loiDoiSoat.value = { message: 'Trạng thái sau khi cập nhật chưa khớp dữ liệu authoritative.' }
  }
}
onMounted(() => { void store.taiDanhSachGoiTap() })
</script>

<template>
  <section
    class="trang-danh-muc"
    aria-label="Danh sách gói tập"
    :aria-busy="goiTap.dangTai"
  >
    <TieuDeTrang
      tieu-de="Gói tập"
      mo-ta="Quản lý gói tập và quyền lợi theo dữ liệu chính thức từ Backend."
    >
      <template #hanhDong>
        <button
          class="nut nut--chinh"
          type="button"
          @click="xuLyMoTrangTao"
        >
          Tạo gói tập
        </button>
      </template>
    </TieuDeTrang>
    <TrangThaiTaiDuLieu
      v-if="goiTap.dangTai && !goiTap.daTaiLanDau"
      nhan="Đang tải gói tập…"
    />
    <TrangThaiLoi
      v-else-if="goiTap.loi"
      :thong-bao="goiTap.loi.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="goiTap.dangTai"
      @thu-lai="xuLyTaiLai"
    />
    <TrangThaiTrong
      v-else-if="goiTap.daTaiLanDau && goiTap.danhSach.length === 0"
      tieu-de="Chưa có gói tập"
      mo-ta="Tạo gói tập đầu tiên để cấp quyền cho hội viên."
    />
    <BangDuLieu
      v-else
      :cot="[]"
      :hang="goiTap.danhSach"
      tieu-de="Danh sách gói tập"
      thong-bao-trong="Chưa có gói tập."
    >
      <template #tieuDeCot>
        <tr>
          <th scope="col">
            Mã
          </th><th scope="col">
            Tên
          </th><th scope="col">
            Giá
          </th><th scope="col">
            Thời hạn
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
          <td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.price }} {{ item.currency ?? '' }}</td><td>{{ item.duration_days }} ngày</td><td>
            <HuyHieuTrangThai
              :trang-thai="kieuTrangThai(item.status)"
              :nhan="layNhan(item.status)"
            />
          </td><td>
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
              {{ item.status === 'DANG_BAN' ? 'Ngừng bán' : 'Mở bán' }}
            </button>
          </td>
        </tr>
      </template>
    </BangDuLieu>
    <HopThoaiXacNhan
      :hien-thi="Boolean(goiTapCanDoiTrangThai)"
      :tieu-de="giaiDoanDoiSoat === 'CHUA_XAC_DINH' ? 'Không xác định được kết quả' : (goiTapCanDoiTrangThai?.status === 'DANG_BAN' ? 'Ngừng bán gói tập?' : 'Mở bán gói tập?')"
      mo-ta="Backend giữ snapshot quyền lợi của các kỳ đã mua. Nếu kết quả chưa rõ, hãy đối soát trạng thái hiện tại."
      :nhan-xac-nhan="giaiDoanDoiSoat === 'CHUA_XAC_DINH' ? 'Đối soát trạng thái' : (giaiDoanDoiSoat === 'CO_THE_THU_LAI' ? 'Thử lại cập nhật' : 'Cập nhật trạng thái')"
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
      </p>
      <p
        v-if="giaiDoanDoiSoat === 'CHUA_XAC_DINH'"
        role="alert"
      >
        Không thể xác định kết quả cập nhật. Đối soát chỉ tải dữ liệu hiện tại và không gửi lại yêu cầu.
      </p>
      <p
        v-else-if="giaiDoanDoiSoat === 'CO_THE_THU_LAI'"
        role="status"
      >
        Backend vẫn ghi nhận trạng thái cũ. Bạn có thể chủ động thử lại một lần nữa.
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
