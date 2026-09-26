<script setup>
import { computed } from 'vue'

const props = defineProps({
  proposal: { type: Object, default: null },
  mo: { type: Boolean, default: false },
})
const emit = defineEmits(['dong'])

const NHAN_TRANG_THAI = Object.freeze({
  CHO_XAC_NHAN: 'Chờ hội viên xác nhận',
  DA_TU_CHOI: 'Hội viên đã từ chối',
  HET_HAN: 'Đề xuất đã hết hạn',
  XUNG_DOT: 'Đề xuất có xung đột',
  DA_AP_DUNG: 'Hội viên đã áp dụng',
})
const ngayTap = computed(() => props.proposal?.content?.plan?.days
  ?? props.proposal?.content?.days
  ?? props.proposal?.content?.plan?.current_version?.days
  ?? [])

function dinhDangNgay(giaTri) {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(giaTri))
}
</script>

<template>
  <Teleport to="body">
    <div v-if="mo && proposal" class="pt-de-xuat-phu" @keydown.esc="emit('dong')">
      <button class="pt-de-xuat-phu__nen" type="button" aria-label="Đóng xem trước đề xuất" @click="emit('dong')" />
      <section
        class="pt-de-xuat-phu__khung"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`pt-de-xuat-tieu-de-${proposal.id}`"
      >
        <header class="pt-de-xuat-phu__dau">
          <div>
            <p class="pt-kicker">BẢN XEM TRƯỚC · ĐỀ XUẤT PT</p>
            <h2 :id="`pt-de-xuat-tieu-de-${proposal.id}`">{{ proposal.title }}</h2>
          </div>
          <button class="nut nut--phu" type="button" @click="emit('dong')">Đóng</button>
        </header>
        <dl class="pt-thuoc-tinh">
          <div><dt>Trạng thái</dt><dd>{{ NHAN_TRANG_THAI[proposal.status] ?? proposal.status }}</dd></div>
          <div><dt>Loại thay đổi</dt><dd>{{ proposal.change_type }}</dd></div>
          <div><dt>Áp dụng từ</dt><dd>{{ proposal.effective_from ?? 'Chưa cập nhật' }}</dd></div>
          <div><dt>Hết hạn</dt><dd>{{ dinhDangNgay(proposal.expires_at) }}</dd></div>
        </dl>
        <p>{{ proposal.explanation }}</p>
        <section class="pt-card" aria-label="Kế hoạch đề xuất">
          <h3>{{ proposal.content?.plan?.name ?? proposal.content?.name ?? 'Nội dung kế hoạch đề xuất' }}</h3>
          <p v-if="proposal.content?.plan?.goal ?? proposal.content?.goal">
            Mục tiêu: {{ proposal.content?.plan?.goal ?? proposal.content?.goal }}
          </p>
          <ol class="pt-de-xuat-phu__ngay">
            <li v-for="day in ngayTap" :key="day.id ?? day.order">
              <strong>{{ day.name }}</strong>
              <span>Thứ {{ day.weekday }} · {{ day.estimated_minutes }} phút</span>
              <ol>
                <li v-for="exercise in day.exercises ?? []" :key="exercise.id ?? exercise.order">
                  {{ exercise.name ?? `Bài tập #${exercise.exercise_id}` }} ·
                  {{ exercise.target_sets }} hiệp · {{ exercise.min_reps }}–{{ exercise.max_reps }} lần
                  <span v-if="exercise.target_weight_kg !== null && exercise.target_weight_kg !== undefined">
                    · {{ exercise.target_weight_kg }} kg
                  </span>
                </li>
              </ol>
            </li>
          </ol>
        </section>
        <p class="pt-thong-bao pt-thong-bao--canh-bao">
          Đây là đề xuất chờ quyết định của hội viên; kế hoạch chính thức chỉ thay đổi khi hội viên xác nhận trên ứng dụng di động.
        </p>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.pt-de-xuat-phu {
  position: fixed;
  z-index: 90;
  inset: 0;
  display: grid;
  justify-items: end;
  background: rgb(20 31 28 / 38%);
}

.pt-de-xuat-phu__nen {
  position: absolute;
  inset: 0;
  width: 100%;
  border: 0;
  background: transparent;
}

.pt-de-xuat-phu__khung {
  position: relative;
  width: min(620px, 100%);
  height: 100%;
  overflow-y: auto;
  padding: var(--khoang-6);
  color: var(--mau-chu);
  background: var(--mau-nen-trang);
  box-shadow: var(--mau-bong-nhe);
}

.pt-de-xuat-phu__dau {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--khoang-3);
  margin-bottom: var(--khoang-5);
}

.pt-de-xuat-phu__ngay {
  display: grid;
  gap: var(--khoang-4);
  padding-inline-start: var(--khoang-5);
}

.pt-de-xuat-phu__ngay > li {
  display: grid;
  gap: var(--khoang-2);
}

.pt-de-xuat-phu__ngay span {
  color: var(--mau-chu-phu);
}

@media (max-width: 640px) {
  .pt-de-xuat-phu__khung { padding: var(--khoang-4); }
}
</style>
