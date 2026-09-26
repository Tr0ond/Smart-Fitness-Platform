<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/pt/thanh_dieu_huong_hoi_vien.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useHoiVienPtStore()
const {
  chiTietHoiVien,
  dangTaiChiTietHoiVien,
  loiChiTietHoiVien,
} = storeToRefs(store)

const memberId = computed(() => {
  const giaTri = route.params.id
  return typeof giaTri === 'string' ? giaTri.trim() : giaTri
})
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const member = computed(() => chiTietHoiVien.value?.member ?? null)
const assignment = computed(() => chiTietHoiVien.value?.assignment ?? null)
const coTheThuLai = computed(() => loiChiTietHoiVien.value?.isNetworkError === true
  || (Number.isInteger(loiChiTietHoiVien.value?.httpStatus)
    && loiChiTietHoiVien.value.httpStatus >= 500))

function dinhDang(giaTri, macDinh = 'Chưa cập nhật') {
  if (typeof giaTri === 'string' && giaTri.trim() !== '') return giaTri
  if (typeof giaTri === 'number' && Number.isFinite(giaTri)) return String(giaTri)
  return macDinh
}

function dinhDangNgay(giaTri, macDinh = 'Chưa cập nhật') {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return macDinh
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(giaTri))
}

async function taiChiTiet() {
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  await store.chonHoiVien(memberId.value)
  await store.taiChiTietHoiVien(memberId.value)
  if ([403, 404].includes(loiChiTietHoiVien.value?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function thuLai() {
  await taiChiTiet()
}

watch(() => route.params.id, () => {
  void taiChiTiet()
})

onMounted(() => {
  void taiChiTiet()
})

onBeforeUnmount(() => {
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-chi-tiet"
    aria-label="Chi tiết hội viên PT"
    :aria-busy="dangTaiChiTietHoiVien"
  >
    <TieuDeTrang
      tieu-de="Chi tiết hội viên"
      mo-ta="Thông tin và assignment được đọc từ phạm vi PT hiện tại."
    >
      <template #hanhDong>
        <RouterLink class="nut nut--lien-ket" :to="{ name: 'ptHoiVien' }">
          Danh sách hội viên
        </RouterLink>
      </template>
    </TieuDeTrang>

    <ThanhDieuHuongHoiVien
      v-if="coIdHopLe"
      :member-id="memberId"
    />

    <TrangThaiTaiDuLieu
      v-if="dangTaiChiTietHoiVien && !chiTietHoiVien"
      nhan="Đang tải hồ sơ hội viên…"
    />
    <TrangThaiLoi
      v-if="loiChiTietHoiVien"
      :thong-bao="layThongBaoLoiApi(loiChiTietHoiVien, 'Không thể tải hồ sơ hội viên.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiChiTietHoiVien"
      @thu-lai="thuLai"
    />

    <section
      v-if="member"
      class="pt-card pt-hoi-vien-chi-tiet"
      aria-labelledby="pt-hoi-vien-chi-tiet-tieu-de"
    >
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">MEMBER #{{ member.id }}</p>
          <h2 id="pt-hoi-vien-chi-tiet-tieu-de">
            {{ dinhDang(member.name, `Hội viên #${member.id}`) }}
          </h2>
        </div>
        <span class="pt-badge">Đang trong phạm vi</span>
      </div>

      <dl class="pt-thuoc-tinh">
        <div>
          <dt>Mã hội viên</dt>
          <dd>{{ dinhDang(member.code) }}</dd>
        </div>
        <div>
          <dt>Mục tiêu tập luyện</dt>
          <dd>{{ dinhDang(member.training_goal) }}</dd>
        </div>
        <div>
          <dt>Kinh nghiệm tập luyện</dt>
          <dd>{{ dinhDang(member.training_experience) }}</dd>
        </div>
        <div>
          <dt>Số ngày tập mong muốn</dt>
          <dd>
            {{ member.desired_training_days === null
              ? 'Chưa cập nhật'
              : `${dinhDang(member.desired_training_days)} ngày/tuần` }}
          </dd>
        </div>
        <div>
          <dt>Thời lượng mỗi buổi</dt>
          <dd>
            {{ member.session_duration_minutes === null
              ? 'Chưa cập nhật'
              : `${dinhDang(member.session_duration_minutes)} phút` }}
          </dd>
        </div>
        <div>
          <dt>Phiên bản hồ sơ</dt>
          <dd>{{ dinhDang(member.profile_version) }}</dd>
        </div>
        <div>
          <dt>Cập nhật hồ sơ</dt>
          <dd>{{ dinhDangNgay(member.updated_at) }}</dd>
        </div>
        <div>
          <dt>Mã assignment</dt>
          <dd>{{ dinhDang(assignment.id) }}</dd>
        </div>
        <div>
          <dt>Bắt đầu assignment</dt>
          <dd>{{ dinhDangNgay(assignment.start_at) }}</dd>
        </div>
        <div>
          <dt>Kết thúc assignment</dt>
          <dd>{{ dinhDangNgay(assignment.end_at, 'Không giới hạn') }}</dd>
        </div>
      </dl>
    </section>
  </section>
</template>
