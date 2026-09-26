<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/PT/thanh_dieu_huong_hoi_vien.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useHoiVienPtStore()
const { chiTietHoiVien, lichSuBuoiHuanLuyen, dangTaiBuoiHuanLuyen, loiBuoiHuanLuyen,
  dangHoanTatBuoiHuanLuyen, loiHoanTatBuoiHuanLuyen, thaoTacBuoiHuanLuyenDangCho,
  ketQuaBuoiHuanLuyen } = storeToRefs(store)
const ghiChu = ref('')
const moHopThoaiXacNhan = ref(false)
const memberIdDaXacNhan = ref(null)
const assignmentIdDaXacNhan = ref(null)
const tenHoiVienDaXacNhan = ref('')
const loiAssignmentDaThayDoi = ref(false)
const theHeTrang = ref(0)

const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const assignmentId = computed(() => {
  const assignment = chiTietHoiVien.value?.assignment
  if (assignment?.is_current === false) return null
  const id = assignment?.id
  return Number.isSafeInteger(Number(id)) && Number(id) > 0 ? Number(id) : null
})
const tenHoiVien = computed(() => chiTietHoiVien.value?.member?.name
  ?? chiTietHoiVien.value?.member?.full_name
  ?? `Hội viên #${memberId.value ?? '—'}`)
const moTaXacNhan = computed(() => `${tenHoiVienDaXacNhan.value} · Assignment #${assignmentIdDaXacNhan.value ?? '—'}. Khi xác nhận, Backend sẽ ghi nhận buổi hoàn tất và trừ một lượt PT trực tiếp từ kỳ đủ điều kiện; lần dùng hợp lệ đầu tiên có thể kích hoạt kỳ đầu. Không thể hoàn tác.`)
const danhSachTheoHoiVien = computed(() => lichSuBuoiHuanLuyen.value
  .filter((muc) => String(muc?.member_id) === String(memberId.value)))
const outcomeUnknown = computed(() => thaoTacBuoiHuanLuyenDangCho.value?.outcomeUnknown === true)
const coTheThuLai = computed(() => loiBuoiHuanLuyen.value?.isNetworkError === true
  || (Number.isInteger(loiBuoiHuanLuyen.value?.httpStatus) && loiBuoiHuanLuyen.value.httpStatus >= 500))
const thongBaoGhiChu = computed(() => loiHoanTatBuoiHuanLuyen.value?.fieldErrors?.notes?.[0] ?? '')

function dinhDangNgay(giaTri) {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(giaTri))
}

function laContextHienTai(id, generation) {
  return generation === theHeTrang.value
    && String(memberId.value) === String(id)
    && store.hoiVienDaChonId === Number(id)
}

/** Nap profile assignment truoc history; mot generation moi moi duoc phep render response. */
async function taiTrang() {
  const id = memberId.value
  const generation = ++theHeTrang.value
  ghiChu.value = ''
  moHopThoaiXacNhan.value = false
  memberIdDaXacNhan.value = null
  assignmentIdDaXacNhan.value = null
  tenHoiVienDaXacNhan.value = ''
  loiAssignmentDaThayDoi.value = false
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }

  store.chonHoiVien(id)
  const detail = await store.taiChiTietHoiVien(id)
  if (generation !== theHeTrang.value || String(memberId.value) !== String(id)) return
  if (!detail) {
    if ([403, 404].includes(store.loiChiTietHoiVien?.httpStatus)) {
      await router.replace({ name: 'ptHoiVien' })
    }
    return
  }
  if (!assignmentId.value) {
    store.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
    return
  }

  await store.taiLichSuBuoiHuanLuyen(id)
  if (generation === theHeTrang.value
    && String(memberId.value) === String(id)
    && [403, 404].includes(store.loiBuoiHuanLuyen?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

/** POST chi nhan assignment tu profile; retry store refetch history roi gui cung body/key. */
async function xacNhanHoanTat({ retry = false } = {}) {
  if (dangHoanTatBuoiHuanLuyen.value || assignmentId.value === null) return
  const id = memberId.value
  const generation = theHeTrang.value
  const assignmentHienTai = assignmentId.value
  const note = ghiChu.value === '' ? undefined : ghiChu.value
  const ketQua = await store.hoanTatBuoiHuanLuyen(id, assignmentHienTai, note, { retry })
  if (generation !== theHeTrang.value || String(memberId.value) !== String(id)) return
  if ([403, 404].includes(loiHoanTatBuoiHuanLuyen.value?.httpStatus)) {
    if (store.hoiVienDaChonId === Number(id)) store.xuLyMatPhamVi(id, loiHoanTatBuoiHuanLuyen.value)
    dongXacNhanHoanTat()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  if (ketQua?.scopeLost) {
    dongXacNhanHoanTat()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  if (store.hoiVienDaChonId !== Number(id)) return
  if (ketQuaBuoiHuanLuyen.value) ghiChu.value = ''
  dongXacNhanHoanTat()
}

function moXacNhanHoanTat() {
  if (assignmentId.value !== null
    && store.hoiVienDaChonId === Number(memberId.value)
    && !dangHoanTatBuoiHuanLuyen.value
    && !outcomeUnknown.value) {
    memberIdDaXacNhan.value = String(memberId.value)
    assignmentIdDaXacNhan.value = assignmentId.value
    tenHoiVienDaXacNhan.value = tenHoiVien.value
    loiAssignmentDaThayDoi.value = false
    moHopThoaiXacNhan.value = true
  }
}

function dongXacNhanHoanTat() {
  if (dangHoanTatBuoiHuanLuyen.value) return
  moHopThoaiXacNhan.value = false
  memberIdDaXacNhan.value = null
  assignmentIdDaXacNhan.value = null
  tenHoiVienDaXacNhan.value = ''
}

async function xacNhanSauKiemTra() {
  if (dangHoanTatBuoiHuanLuyen.value) return
  const contextConHopLe = String(memberId.value) === memberIdDaXacNhan.value
    && assignmentId.value === assignmentIdDaXacNhan.value
    && store.hoiVienDaChonId === Number(memberIdDaXacNhan.value)
  if (!contextConHopLe) {
    loiAssignmentDaThayDoi.value = true
    dongXacNhanHoanTat()
    return
  }
  await xacNhanHoanTat()
}

watch(() => route.params.id, () => { void taiTrang() }, { flush: 'sync', immediate: true })

onBeforeUnmount(() => {
  theHeTrang.value += 1
  moHopThoaiXacNhan.value = false
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-buoi-huan-luyen"
    aria-label="Buổi huấn luyện trực tiếp"
    :aria-busy="dangTaiBuoiHuanLuyen || dangHoanTatBuoiHuanLuyen"
  >
    <TieuDeTrang
      tieu-de="Buổi huấn luyện trực tiếp"
      mo-ta="PT xác nhận buổi đã hoàn tất trong assignment hiện tại. Backend kiểm tra kỳ hạn và lượt hợp lệ."
    />
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <section class="pt-card" aria-labelledby="pt-buoi-tao-tieu-de">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">PT DIRECT SESSION</p>
          <h2 id="pt-buoi-tao-tieu-de">Xác nhận buổi tập</h2>
        </div>
        <span class="pt-badge">Assignment #{{ assignmentId ?? '—' }}</span>
      </div>
      <p>
        Mỗi lần xác nhận chỉ gửi assignment hiện tại. Hệ thống tự kiểm tra quyền lợi đúng kỳ và có thể kích hoạt kỳ đầu hợp lệ.
      </p>
      <p v-if="loiAssignmentDaThayDoi" class="pt-thong-bao pt-thong-bao--canh-bao" role="alert">
        Hội viên hoặc assignment đã thay đổi. Không có buổi nào được gửi; hãy tải lại ngữ cảnh rồi xác nhận lại.
      </p>
      <form class="pt-form" @submit.prevent="moXacNhanHoanTat">
        <label class="pt-field" for="pt-buoi-ghi-chu">
          <span class="pt-field__nhan">Ghi chú buổi tập <span class="pt-field__phu">(không bắt buộc)</span></span>
          <textarea
            id="pt-buoi-ghi-chu"
            v-model="ghiChu"
            name="notes"
            maxlength="1000"
            rows="3"
            :disabled="dangHoanTatBuoiHuanLuyen || outcomeUnknown"
            :aria-invalid="Boolean(thongBaoGhiChu)"
            aria-describedby="pt-buoi-ghi-chu-huong-dan pt-buoi-ghi-chu-dem"
          />
          <small id="pt-buoi-ghi-chu-huong-dan">Chỉ gửi nội dung ghi chú; thông tin hội viên, kỳ hạn và số lượt do Backend xác định.</small>
          <small id="pt-buoi-ghi-chu-dem">{{ ghiChu.length }}/1000</small>
          <small v-if="thongBaoGhiChu" class="pt-field__loi" role="alert">{{ thongBaoGhiChu }}</small>
        </label>
        <p v-if="loiHoanTatBuoiHuanLuyen && !outcomeUnknown" class="pt-thong-bao pt-thong-bao--loi" role="alert">
          {{ layThongBaoLoiApi(loiHoanTatBuoiHuanLuyen, 'Không thể xác nhận buổi tập.') }}
        </p>
        <p v-if="outcomeUnknown" class="pt-thong-bao pt-thong-bao--canh-bao" role="status">
          Chưa xác định được kết quả. Hệ thống đã thử tải lại lịch sử để đối chiếu; do danh sách không có khóa thao tác và chỉ hiển thị 100 bản ghi, kết quả chưa thể kết luận.
          Thử lại sẽ gửi nguyên assignment, ghi chú và khóa ban đầu.
        </p>
        <p v-if="ketQuaBuoiHuanLuyen" class="pt-thong-bao pt-thong-bao--thanh-cong" role="status">
          {{ ketQuaBuoiHuanLuyen.replayed
            ? 'Backend xác nhận thao tác trước đã hoàn tất; không tạo thêm lượt buổi tập.'
            : 'Buổi tập đã được xác nhận hoàn tất.' }}
        </p>
        <div class="pt-form__hanh-dong">
          <button
            v-if="outcomeUnknown"
            class="nut nut--phu"
            type="button"
            :disabled="dangHoanTatBuoiHuanLuyen"
            :aria-busy="dangHoanTatBuoiHuanLuyen"
            @click="xacNhanHoanTat({ retry: true })"
          >
            {{ dangHoanTatBuoiHuanLuyen ? 'Đang đối chiếu…' : 'Tải lại lịch sử và thử lại an toàn' }}
          </button>
          <button
            v-else
            class="nut nut--chinh"
            type="submit"
            :disabled="assignmentId === null || dangHoanTatBuoiHuanLuyen"
            :aria-busy="dangHoanTatBuoiHuanLuyen"
          >
            {{ dangHoanTatBuoiHuanLuyen ? 'Đang xác nhận…' : 'Xác nhận hoàn tất buổi tập' }}
          </button>
        </div>
      </form>
    </section>

    <HopThoaiXacNhan
      :hien-thi="moHopThoaiXacNhan"
      :dang-xu-ly="dangHoanTatBuoiHuanLuyen"
      tieu-de="Xác nhận hoàn tất buổi tập?"
      nhan-xac-nhan="Xác nhận hoàn tất"
      mang-nguy-hiem
      :mo-ta="moTaXacNhan"
      @xac-nhan="xacNhanSauKiemTra"
      @huy="dongXacNhanHoanTat"
      @dong="dongXacNhanHoanTat"
    />

    <section class="pt-card" aria-labelledby="pt-buoi-lich-su-tieu-de">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">RECENT HISTORY</p>
          <h2 id="pt-buoi-lich-su-tieu-de">Buổi tập đã xác nhận</h2>
        </div>
        <span class="pt-badge">Tối đa 100 bản ghi gần nhất</span>
      </div>
      <p>
        API trả tối đa 100 buổi gần nhất của huấn luyện viên trên mọi hội viên; danh sách bên dưới được lọc theo hội viên này.
        Danh sách trống không chứng minh hội viên chưa từng có buổi tập.
      </p>
      <TrangThaiTaiDuLieu v-if="dangTaiBuoiHuanLuyen && lichSuBuoiHuanLuyen.length === 0" nhan="Đang tải lịch sử buổi tập…" />
      <TrangThaiLoi
        v-if="loiBuoiHuanLuyen"
        :thong-bao="layThongBaoLoiApi(loiBuoiHuanLuyen, 'Không thể tải lịch sử buổi tập.')"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTaiBuoiHuanLuyen"
        @thu-lai="taiTrang"
      />
      <TrangThaiTrong
        v-if="!dangTaiBuoiHuanLuyen && !loiBuoiHuanLuyen && danhSachTheoHoiVien.length === 0"
        tieu-de="Không có buổi nào trong 100 bản ghi gần nhất"
        mo-ta="Đây không phải xác nhận rằng hội viên chưa từng hoàn thành buổi tập trước đây."
      />
      <ol v-else-if="danhSachTheoHoiVien.length" class="pt-danh-sach">
        <li v-for="muc in danhSachTheoHoiVien" :key="muc.history_id" class="pt-card pt-buoi-lich-su__muc">
          <div class="pt-card__dau">
            <strong>Buổi #{{ muc.history_id }}</strong>
            <span class="pt-badge">{{ muc.status }}</span>
          </div>
          <p>Hoàn tất: {{ dinhDangNgay(muc.completed_at) }}</p>
          <p v-if="muc.notes">{{ muc.notes }}</p>
        </li>
      </ol>
    </section>
  </section>
</template>
