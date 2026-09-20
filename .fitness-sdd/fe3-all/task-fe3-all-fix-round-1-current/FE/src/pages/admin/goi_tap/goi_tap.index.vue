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

const nhanTrangThai = computed(() => ({ DANG_BAN: 'Đang bán', NGUNG_BAN: 'Ngừng bán' }))
const kieuTrangThai = (status) => status === 'DANG_BAN' ? 'thanh_cong' : 'canh_bao'

function layNhan(status) { return nhanTrangThai.value[status] ?? status ?? 'Không xác định' }
function xuLyMoTrangTao() { void router.push({ name: 'adminTaoGoiTap' }) }
function xuLyMoChiTiet(item) {
  if (item?.id) void router.push({ name: 'adminChiTietGoiTap', params: { id: item.id } })
}
function xuLyMoDoiTrangThai(item) {
  goiTapCanDoiTrangThai.value = item
  trangThaiTruocDo.value = item?.status ?? null
  yDinhTrangThai.value = item?.status === 'DANG_BAN' ? 'NGUNG_BAN' : 'DANG_BAN'
}
function xuLyDongDoiTrangThai() { if (!dangDoiTrangThai.value) goiTapCanDoiTrangThai.value = null }
function xuLyTaiLai() { void store.taiDanhSachGoiTap() }
/**
 * Reconciles an unknown package status mutation with a fresh authoritative list.
 * Input is the selected item and intended status; process performs GET only.
 * Result closes only when the server reports the intended status.
 * Side effect never retries the PATCH and keeps unresolved uncertainty visible.
 * Rule: package ownership and Q01 snapshots remain Backend-authoritative.
 */
async function xuLyDoiSoatTrangThai() {
  if (dangDoiTrangThai.value || !goiTapCanDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.taiDanhSachGoiTap()
  const current = goiTap.value.danhSach.find((item) => item.id === goiTapCanDoiTrangThai.value.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) {
    goiTapCanDoiTrangThai.value = null
    return
  }
  if (current?.status === trangThaiTruocDo.value) return
}
/**
 * Confirms one package status mutation and refreshes authoritative state.
 * Input is the selected package; process sends one PATCH then one GET.
 * Result closes after confirmed PATCH plus successful refresh, while failures remain open.
 * Side effect is never a blind retry and preserves the Q01 snapshot warning.
 */
async function xuLyXacNhanDoiTrangThai() {
  const item = goiTapCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  if (goiTap.value.loiMutation?.outcomeUnknown) {
    await xuLyDoiSoatTrangThai()
    return
  }
  dangDoiTrangThai.value = true
  await store.capNhatGoiTap(item.id, { status: yDinhTrangThai.value })
  if (goiTap.value.loiMutation) {
    dangDoiTrangThai.value = false
    return
  }
  await store.taiDanhSachGoiTap()
  if (goiTap.value.loi) {
    dangDoiTrangThai.value = false
    return
  }
  const current = goiTap.value.danhSach.find((row) => row.id === item.id)
  dangDoiTrangThai.value = false
  if (current?.status === yDinhTrangThai.value) goiTapCanDoiTrangThai.value = null
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
      :tieu-de="goiTap.loiMutation?.outcomeUnknown ? 'Không xác định được kết quả' : (goiTapCanDoiTrangThai?.status === 'DANG_BAN' ? 'Ngừng bán gói tập?' : 'Mở bán gói tập?')"
      mo-ta="Backend giữ snapshot quyền lợi của các kỳ đã mua. Nếu kết quả chưa rõ, hãy đối soát trạng thái hiện tại."
      :nhan-xac-nhan="goiTap.loiMutation?.outcomeUnknown ? 'Đối soát trạng thái' : 'Cập nhật trạng thái'"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xuLyXacNhanDoiTrangThai"
      @huy="xuLyDongDoiTrangThai"
    >
      <p
        v-if="goiTap.loiMutation"
        role="alert"
      >
        {{ goiTap.loiMutation.message }}
      </p>
      <p
        v-if="goiTap.loiMutation?.outcomeUnknown"
        role="alert"
      >
        Không thể xác định kết quả cập nhật. Đối soát sẽ chỉ tải dữ liệu hiện tại và không gửi lại yêu cầu.
      </p>
    </HopThoaiXacNhan>
  </section>
</template>
