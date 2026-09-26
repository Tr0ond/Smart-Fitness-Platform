<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/pt/thanh_dieu_huong_hoi_vien.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useHoiVienPtStore()
const {
  lichSuTap,
  dangTaiLichSuTap,
  loiLichSuTap,
  chiTietPhien,
  dangTaiChiTietPhien,
  loiChiTietPhien,
} = storeToRefs(store)
const phienDangXem = ref(null)

const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const danhSachPhien = computed(() => lichSuTap.value?.items ?? [])
const coTheThuLai = computed(() => {
  const loi = loiLichSuTap.value ?? loiChiTietPhien.value
  return loi?.isNetworkError === true || (Number.isInteger(loi?.httpStatus) && loi.httpStatus >= 500)
})

function dinhDang(giaTri, macDinh = 'Chưa cập nhật') {
  return giaTri === null || giaTri === undefined || giaTri === '' ? macDinh : giaTri
}

function dinhDangNgay(giaTri) {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(giaTri))
}

function layTrangThai(phien) {
  return phien?.status === 'HOAN_THANH' ? 'Đã hoàn thành · bất biến' : dinhDang(phien?.status)
}

async function taiLichSu() {
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  store.chonHoiVien(memberId.value)
  await store.taiLichSuTapHoiVien(memberId.value)
  if ([403, 404].includes(loiLichSuTap.value?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function xemPhien(phien) {
  const id = Number(phien?.id)
  if (!Number.isSafeInteger(id) || id < 1) return
  phienDangXem.value = id
  await store.taiChiTietLichSuTapHoiVien(memberId.value, id)
  if ([403, 404].includes(loiChiTietPhien.value?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function taiThem() {
  await store.taiLichSuTapHoiVien(memberId.value, { append: true })
}

watch(() => route.params.id, () => {
  phienDangXem.value = null
  void taiLichSu()
})

onMounted(() => {
  void taiLichSu()
})

onBeforeUnmount(() => {
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-lich-su"
    aria-label="Lịch sử buổi tập của hội viên"
    :aria-busy="dangTaiLichSuTap || dangTaiChiTietPhien"
  >
    <TieuDeTrang
      tieu-de="Lịch sử tập"
      mo-ta="Các phiên tập được đọc từ snapshot bất biến; màn hình không có thao tác sửa hoặc xóa."
    />
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <TrangThaiTaiDuLieu
      v-if="dangTaiLichSuTap && danhSachPhien.length === 0"
      nhan="Đang tải lịch sử buổi tập…"
    />
    <TrangThaiLoi
      v-if="loiLichSuTap"
      :thong-bao="layThongBaoLoiApi(loiLichSuTap, 'Không thể tải lịch sử buổi tập.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiLichSuTap"
      @thu-lai="taiLichSu"
    />
    <TrangThaiTrong
      v-if="!dangTaiLichSuTap && !loiLichSuTap && danhSachPhien.length === 0"
      tieu-de="Chưa có phiên tập"
      mo-ta="Các phiên đã hoàn thành sẽ được lưu tại đây dưới dạng chỉ đọc."
    />

    <div v-if="danhSachPhien.length" class="pt-lich-su-layout">
      <section class="pt-card" aria-labelledby="pt-lich-su-danh-sach">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">IMMUTABLE SESSIONS</p>
            <h2 id="pt-lich-su-danh-sach">Các phiên tập</h2>
          </div>
          <span class="pt-badge">Chỉ đọc</span>
        </div>
        <ol class="pt-danh-sach-phien">
          <li v-for="phien in danhSachPhien" :key="phien.id">
            <button
              class="pt-danh-sach-phien__nut"
              type="button"
              :class="{ 'pt-danh-sach-phien__nut--dang-chon': phienDangXem === phien.id }"
              @click="xemPhien(phien)"
            >
              <span>
                <strong>{{ dinhDang(phien.name, `Phiên tập #${phien.id}`) }}</strong>
                <small>{{ dinhDangNgay(phien.ended_at ?? phien.started_at) }}</small>
              </span>
              <span>{{ layTrangThai(phien) }}</span>
            </button>
          </li>
        </ol>
        <button
          v-if="lichSuTap.nextCursor"
          class="nut nut--phu"
          type="button"
          :disabled="dangTaiLichSuTap"
          @click="taiThem"
        >
          {{ dangTaiLichSuTap ? 'Đang tải…' : 'Tải thêm phiên cũ hơn' }}
        </button>
      </section>

      <section class="pt-card" aria-labelledby="pt-lich-su-chi-tiet">
        <TrangThaiTaiDuLieu
          v-if="dangTaiChiTietPhien"
          nhan="Đang tải snapshot phiên tập…"
        />
        <TrangThaiLoi
          v-if="loiChiTietPhien"
          :thong-bao="layThongBaoLoiApi(loiChiTietPhien, 'Không thể tải chi tiết phiên tập.')"
          :co-the-thu-lai="coTheThuLai"
          :dang-thu-lai="dangTaiChiTietPhien"
          @thu-lai="xemPhien({ id: phienDangXem })"
        />
        <template v-if="chiTietPhien?.id === phienDangXem">
          <div class="pt-card__dau">
            <div>
              <p class="pt-kicker">SESSION SNAPSHOT #{{ chiTietPhien.id }}</p>
              <h2 id="pt-lich-su-chi-tiet">{{ dinhDang(chiTietPhien.name, 'Chi tiết phiên tập') }}</h2>
            </div>
            <span class="pt-badge">Không thể chỉnh sửa</span>
          </div>
          <dl class="pt-thuoc-tinh">
            <div>
              <dt>Trạng thái</dt>
              <dd>{{ layTrangThai(chiTietPhien) }}</dd>
            </div>
            <div>
              <dt>Bắt đầu</dt>
              <dd>{{ dinhDangNgay(chiTietPhien.started_at) }}</dd>
            </div>
            <div>
              <dt>Kết thúc</dt>
              <dd>{{ dinhDangNgay(chiTietPhien.ended_at) }}</dd>
            </div>
            <div>
              <dt>Revision</dt>
              <dd>{{ chiTietPhien.revision ?? '—' }}</dd>
            </div>
          </dl>
          <div class="pt-session-bai-tap">
            <h3>Bài tập trong snapshot</h3>
            <TrangThaiTrong
              v-if="!chiTietPhien.exercises?.length"
              tieu-de="Không có bài tập chi tiết"
            />
            <ul v-else>
              <li v-for="exercise in chiTietPhien.exercises" :key="exercise.id">
                <strong>{{ dinhDang(exercise.name, `Bài tập #${exercise.exercise_id}`) }}</strong>
                <span>{{ exercise.sets?.length ?? 0 }} hiệp đã ghi nhận</span>
              </li>
            </ul>
          </div>
        </template>
        <TrangThaiTrong
          v-else-if="!dangTaiChiTietPhien && !loiChiTietPhien"
          tieu-de="Chọn một phiên tập"
          mo-ta="Chọn một dòng để xem snapshot chỉ đọc."
        />
      </section>
    </div>
  </section>
</template>
