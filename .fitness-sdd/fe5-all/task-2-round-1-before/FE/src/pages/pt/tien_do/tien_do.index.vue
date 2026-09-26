<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/pt/thanh_dieu_huong_hoi_vien.vue'
import TheTienDo from '../../../components/pt/the_tien_do.vue'
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
  tienDo,
  keHoachTap,
  dangTaiTienDo,
  loiTienDo,
  dangTaiKeHoachTap,
  loiKeHoachTap,
} = storeToRefs(store)
const baiTapDangXem = ref('')

const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const tongQuan = computed(() => tienDo.value?.overview ?? null)
const chiSo = computed(() => tienDo.value?.body ?? null)
const ketQuaBaiTap = computed(() => {
  const id = Number(baiTapDangXem.value)
  return Number.isSafeInteger(id) && id > 0 ? tienDo.value?.exercises?.[id] ?? null : null
})
const danhSachBaiTap = computed(() => {
  const days = keHoachTap.value?.plan?.current_version?.days
  if (!Array.isArray(days)) return []
  const daGap = new Set()
  return days.flatMap((day) => Array.isArray(day?.exercises) ? day.exercises : [])
    .filter((exercise) => {
      const id = Number(exercise?.exercise_id)
      if (!Number.isSafeInteger(id) || id < 1 || daGap.has(id)) return false
      daGap.add(id)
      return true
    })
    .map((exercise) => ({ id: Number(exercise.exercise_id), name: exercise.name ?? `Bài tập #${exercise.exercise_id}` }))
})
const itemsChiSo = computed(() => Array.isArray(chiSo.value) ? chiSo.value : chiSo.value?.items ?? [])
const coTheThuLai = computed(() => loiTienDo.value?.isNetworkError === true
  || (Number.isInteger(loiTienDo.value?.httpStatus) && loiTienDo.value.httpStatus >= 500))

function layGiaTri(giaTri, macDinh = '—') {
  return giaTri === null || giaTri === undefined || giaTri === '' ? macDinh : giaTri
}

function dinhDangNgay(giaTri) {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(giaTri))
}

async function taiDuLieu() {
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  store.chonHoiVien(memberId.value)
  await Promise.all([
    store.taiTienDoHoiVien(memberId.value),
    store.taiChiSoCoThe(memberId.value),
    store.taiKeHoachTapHoiVien(memberId.value),
  ])
  if ([loiTienDo.value?.httpStatus, loiKeHoachTap.value?.httpStatus]
    .some((httpStatus) => [403, 404].includes(httpStatus))) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function taiTienDoBaiTap() {
  if (baiTapDangXem.value) {
    await store.taiTienDoBaiTap(memberId.value, baiTapDangXem.value)
  }
}

watch(() => route.params.id, () => {
  baiTapDangXem.value = ''
  void taiDuLieu()
})

onMounted(() => {
  void taiDuLieu()
})

onBeforeUnmount(() => {
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-tien-do"
    aria-label="Tiến độ tập luyện của hội viên"
    :aria-busy="dangTaiTienDo || dangTaiKeHoachTap"
  >
    <TieuDeTrang
      tieu-de="Tiến độ tập luyện"
      mo-ta="Các chỉ số và lịch sử được hiển thị nguyên trạng từ dữ liệu PT-scoped."
    />
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <TrangThaiTaiDuLieu
      v-if="(dangTaiTienDo || dangTaiKeHoachTap) && !tongQuan && !chiSo"
      nhan="Đang tải tiến độ hội viên…"
    />
    <TrangThaiLoi
      v-if="loiTienDo"
      :thong-bao="layThongBaoLoiApi(loiTienDo, 'Không thể tải tiến độ hội viên.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiTienDo"
      @thu-lai="taiDuLieu"
    />

    <template v-if="tongQuan || chiSo">
      <section class="pt-card" aria-labelledby="pt-tien-do-tong-quan">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">OVERVIEW</p>
            <h2 id="pt-tien-do-tong-quan">Tổng quan</h2>
          </div>
          <p v-if="tongQuan?.period" class="pt-card__phu-de">
            {{ tongQuan.period.from }} — {{ tongQuan.period.to }}
          </p>
        </div>
        <div class="pt-luoi-tien-do">
          <TheTienDo
            nhan="Buổi đã hoàn thành"
            :gia-tri="tongQuan?.completed_sessions_count ?? '—'"
          />
          <TheTienDo
            nhan="Ngày tập"
            :gia-tri="tongQuan?.training_frequency?.active_days_count ?? '—'"
          />
          <TheTienDo
            nhan="Tần suất / 7 ngày"
            :gia-tri="tongQuan?.training_frequency?.completed_sessions_per_7_days ?? '—'"
          />
          <TheTienDo
            nhan="Cân nặng gần nhất"
            :gia-tri="tongQuan?.latest_body_measurement?.weight_kg ?? '—'"
            don-vi="kg"
          />
        </div>
      </section>

      <section class="pt-card" aria-labelledby="pt-tien-do-co-the">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">BODY MEASUREMENTS</p>
            <h2 id="pt-tien-do-co-the">Chỉ số cơ thể</h2>
          </div>
          <p class="pt-card__phu-de">Read-only</p>
        </div>
        <TrangThaiTrong
          v-if="itemsChiSo.length === 0"
          tieu-de="Chưa có chỉ số cơ thể"
          mo-ta="Chưa có bản ghi trong phạm vi dữ liệu hiện tại."
        />
        <div v-else class="pt-bang-cuon">
          <table class="pt-bang">
            <caption class="pt-visually-hidden">Lịch sử chỉ số cơ thể</caption>
            <thead>
              <tr>
                <th scope="col">Thời điểm</th>
                <th scope="col">Cân nặng</th>
                <th scope="col">Chiều cao</th>
                <th scope="col">BMI</th>
                <th scope="col">Trạng thái dữ liệu</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in itemsChiSo" :key="item.id">
                <td>{{ dinhDangNgay(item.measured_at) }}</td>
                <td>{{ layGiaTri(item.weight_kg) }} kg</td>
                <td>{{ layGiaTri(item.height_cm) }} cm</td>
                <td>{{ layGiaTri(item.bmi) }}</td>
                <td>{{ item.bmi_status === 'available' ? 'Có dữ liệu' : 'Không khả dụng' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="pt-card" aria-labelledby="pt-tien-do-bai-tap">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">EXERCISE TREND</p>
            <h2 id="pt-tien-do-bai-tap">Tiến độ theo bài tập</h2>
          </div>
          <label class="pt-inline-field">
            <span>Bài tập trong kế hoạch chính thức</span>
            <select v-model="baiTapDangXem" @change="taiTienDoBaiTap">
              <option value="">Chọn bài tập</option>
              <option v-for="baiTap in danhSachBaiTap" :key="baiTap.id" :value="String(baiTap.id)">
                {{ baiTap.name }}
              </option>
            </select>
          </label>
        </div>
        <TrangThaiTrong
          v-if="baiTapDangXem && !ketQuaBaiTap && !dangTaiTienDo"
          tieu-de="Chưa có dữ liệu bài tập"
          mo-ta="Bài tập đã chọn chưa có bản ghi trong khoảng mặc định."
        />
        <TrangThaiTrong
          v-else-if="!baiTapDangXem"
          tieu-de="Chọn một bài tập"
          mo-ta="Danh sách bài tập lấy từ phiên bản kế hoạch chính thức hiện tại."
        />
        <div v-else-if="ketQuaBaiTap" class="pt-bai-tap-ket-qua">
          <p><strong>{{ ketQuaBaiTap.exercise?.name ?? 'Bài tập' }}</strong></p>
          <p>{{ ketQuaBaiTap.items?.length ?? 0 }} lần xuất hiện trong lịch sử hoàn thành.</p>
          <ul>
            <li v-for="item in (ketQuaBaiTap.items ?? [])" :key="`${item.session_id}-${item.completed_at}`">
              {{ dinhDangNgay(item.completed_at) }} · {{ item.sets_count ?? 0 }} hiệp
            </li>
          </ul>
        </div>
      </section>
    </template>
  </section>
</template>
