<script setup>
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
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
const { keHoachTap, dangTaiKeHoachTap, loiKeHoachTap } = storeToRefs(store)

const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const plan = computed(() => keHoachTap.value?.plan ?? null)
const days = computed(() => plan.value?.current_version?.days ?? [])
const futureSchedule = computed(() => keHoachTap.value?.future_schedule ?? [])
const coTheThuLai = computed(() => loiKeHoachTap.value?.isNetworkError === true
  || (Number.isInteger(loiKeHoachTap.value?.httpStatus) && loiKeHoachTap.value.httpStatus >= 500))

function dinhDang(giaTri, macDinh = 'Chưa cập nhật') {
  return giaTri === null || giaTri === undefined || giaTri === '' ? macDinh : giaTri
}

async function taiKeHoach() {
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  store.chonHoiVien(memberId.value)
  await store.taiKeHoachTapHoiVien(memberId.value)
  if ([403, 404].includes(loiKeHoachTap.value?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

watch(() => route.params.id, () => {
  void taiKeHoach()
})

onMounted(() => {
  void taiKeHoach()
})

onBeforeUnmount(() => {
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-ke-hoach"
    aria-label="Kế hoạch tập chính thức"
    :aria-busy="dangTaiKeHoachTap"
  >
    <TieuDeTrang
      tieu-de="Kế hoạch tập chính thức"
      mo-ta="Chỉ đọc phiên bản đã được áp dụng; đề xuất chưa xác nhận không hiển thị như kế hoạch."
    />
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <TrangThaiTaiDuLieu
      v-if="dangTaiKeHoachTap && !keHoachTap"
      nhan="Đang tải kế hoạch tập…"
    />
    <TrangThaiLoi
      v-if="loiKeHoachTap"
      :thong-bao="layThongBaoLoiApi(loiKeHoachTap, 'Không thể tải kế hoạch tập chính thức.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiKeHoachTap"
      @thu-lai="taiKeHoach"
    />
    <TrangThaiTrong
      v-if="!dangTaiKeHoachTap && !loiKeHoachTap && !plan"
      tieu-de="Chưa có kế hoạch tập chính thức"
      mo-ta="Hội viên chưa có phiên bản kế hoạch đang được áp dụng."
    />

    <template v-if="plan">
      <section class="pt-card" aria-labelledby="pt-ke-hoach-tieu-de">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">OFFICIAL PLAN</p>
            <h2 id="pt-ke-hoach-tieu-de">{{ dinhDang(plan.name, 'Kế hoạch hiện tại') }}</h2>
          </div>
          <span class="pt-badge">{{ dinhDang(plan.status, 'ĐANG SỬ DỤNG') }}</span>
        </div>
        <dl class="pt-thuoc-tinh">
          <div>
            <dt>Phiên bản</dt>
            <dd>{{ plan.current_version?.number ?? '—' }}</dd>
          </div>
          <div>
            <dt>Áp dụng từ</dt>
            <dd>{{ dinhDang(plan.current_version?.effective_from) }}</dd>
          </div>
          <div>
            <dt>Mục tiêu</dt>
            <dd>{{ dinhDang(plan.current_version?.goal) }}</dd>
          </div>
          <div>
            <dt>Mẫu nguồn</dt>
            <dd>{{ dinhDang(plan.current_version?.template_name_snapshot) }}</dd>
          </div>
        </dl>
      </section>

      <section class="pt-card" aria-labelledby="pt-ke-hoach-ngay-tap">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">CURRENT VERSION</p>
            <h2 id="pt-ke-hoach-ngay-tap">Các ngày tập</h2>
          </div>
          <p class="pt-card__phu-de">Read-only snapshot</p>
        </div>
        <TrangThaiTrong
          v-if="days.length === 0"
          tieu-de="Phiên bản chưa có ngày tập"
          mo-ta="Không tự tạo hoặc suy diễn nội dung còn thiếu."
        />
        <div v-else class="pt-ke-hoach-ngay">
          <article v-for="day in days" :key="day.id" class="pt-ke-hoach-ngay__muc">
            <div class="pt-ke-hoach-ngay__dau">
              <div>
                <h3>{{ dinhDang(day.name, `Ngày ${day.order}`) }}</h3>
                <p>{{ day.estimated_minutes ?? '—' }} phút · Thứ {{ day.weekday ?? '—' }}</p>
              </div>
              <span>{{ day.exercises?.length ?? 0 }} bài tập</span>
            </div>
            <ol v-if="day.exercises?.length" class="pt-ke-hoach-bai-tap">
              <li v-for="exercise in day.exercises" :key="exercise.id">
                <strong>{{ dinhDang(exercise.name, `Bài tập #${exercise.exercise_id}`) }}</strong>
                <span>{{ exercise.target_sets ?? '—' }} hiệp · {{ exercise.min_reps ?? '—' }}–{{ exercise.max_reps ?? '—' }} lần</span>
              </li>
            </ol>
          </article>
        </div>
      </section>

      <section class="pt-card" aria-labelledby="pt-ke-hoach-lich-tuong-lai">
        <div class="pt-card__dau">
          <div>
            <p class="pt-kicker">SCHEDULE</p>
            <h2 id="pt-ke-hoach-lich-tuong-lai">Lịch tập sắp tới</h2>
          </div>
          <p class="pt-card__phu-de">
            {{ keHoachTap.schedule_window?.from ?? '—' }} — {{ keHoachTap.schedule_window?.to ?? '—' }}
          </p>
        </div>
        <TrangThaiTrong
          v-if="futureSchedule.length === 0"
          tieu-de="Chưa có lịch tập sắp tới"
          mo-ta="Không có lịch tập trong khoảng ngày hiển thị."
        />
        <ul v-else class="pt-lich-tap">
          <li v-for="item in futureSchedule" :key="item.id">
            <strong>{{ dinhDang(item.name, 'Buổi tập') }}</strong>
            <span>{{ dinhDang(item.scheduled_date ?? item.date) }}</span>
            <span>{{ dinhDang(item.status, 'Đã xếp lịch') }}</span>
          </li>
        </ul>
      </section>
    </template>
  </section>
</template>
