<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = useHoiVienPtStore()
const {
  danhSachHoiVien,
  dangTaiDanhSachHoiVien,
  loiDanhSachHoiVien,
  daTaiDanhSachHoiVien,
} = storeToRefs(store)

const coTheThuLai = computed(() => loiDanhSachHoiVien.value?.isNetworkError === true
  || (Number.isInteger(loiDanhSachHoiVien.value?.httpStatus)
    && loiDanhSachHoiVien.value.httpStatus >= 500))

function layMember(assignment) {
  return assignment?.member ?? assignment
}

function layId(assignment) {
  const member = layMember(assignment)
  return member?.id ?? assignment?.member_id ?? assignment?.id
}

function layTen(assignment) {
  const member = layMember(assignment)
  return member?.name ?? member?.full_name ?? `Hội viên #${layId(assignment) ?? '—'}`
}

function layEmail(assignment) {
  return layMember(assignment)?.email ?? 'Chưa cập nhật'
}

function layNgay(assignment) {
  const giaTri = assignment?.start_at ?? assignment?.startAt
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Đang phân công'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(giaTri))
}

function taoLienKet(assignment) {
  const id = layId(assignment)
  return id === undefined || id === null ? null : { name: 'ptChiTietHoiVien', params: { id: String(id) } }
}

async function taiDanhSach(force = false) {
  await store.taiDanhSachHoiVienDuocPhanCong({ force })
}

onMounted(() => {
  void taiDanhSach()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-hoi-vien"
    aria-label="Hội viên được phân công"
    :aria-busy="dangTaiDanhSachHoiVien"
  >
    <TieuDeTrang
      tieu-de="Hội viên được phân công"
      mo-ta="Danh sách chỉ gồm các hội viên đang nằm trong assignment PT hiện tại."
    />

    <TrangThaiTaiDuLieu
      v-if="dangTaiDanhSachHoiVien && !daTaiDanhSachHoiVien"
      nhan="Đang tải danh sách hội viên…"
    />
    <TrangThaiLoi
      v-if="loiDanhSachHoiVien"
      :thong-bao="layThongBaoLoiApi(loiDanhSachHoiVien, 'Không thể tải danh sách hội viên.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiDanhSachHoiVien"
      @thu-lai="taiDanhSach(true)"
    />

    <TrangThaiTrong
      v-if="daTaiDanhSachHoiVien && !loiDanhSachHoiVien && danhSachHoiVien.length === 0"
      tieu-de="Chưa có hội viên được phân công"
      mo-ta="Khi assignment hoạt động, hội viên sẽ xuất hiện tại đây."
    />

    <div
      v-if="danhSachHoiVien.length > 0"
      class="pt-danh-sach"
      aria-live="polite"
    >
      <article
        v-for="assignment in danhSachHoiVien"
        :key="`${layId(assignment)}-${assignment.id ?? ''}`"
        class="pt-card pt-hoi-vien-item"
      >
        <div>
          <p class="pt-kicker">ASSIGNED MEMBER</p>
          <h2>{{ layTen(assignment) }}</h2>
          <p>{{ layEmail(assignment) }}</p>
        </div>
        <dl class="pt-hoi-vien-item__thong-tin">
          <div>
            <dt>Bắt đầu phân công</dt>
            <dd>{{ layNgay(assignment) }}</dd>
          </div>
          <div>
            <dt>Trạng thái</dt>
            <dd>Đang quản lý</dd>
          </div>
        </dl>
        <RouterLink
          v-if="taoLienKet(assignment)"
          class="nut nut--lien-ket"
          :to="taoLienKet(assignment)"
          @click="store.chonHoiVien(layId(assignment))"
        >
          Mở hồ sơ
        </RouterLink>
      </article>
    </div>
  </section>
</template>
