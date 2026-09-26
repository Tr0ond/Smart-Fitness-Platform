<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  proposal: { type: Object, default: null },
  mo: { type: Boolean, default: false },
})
const emit = defineEmits(['dong'])
const khungXemTruoc = ref(null)
const nutDong = ref(null)
const dangMo = computed(() => props.mo && Boolean(props.proposal))
let phanTuTraFocus = null

const BO_CHON_CO_THE_FOCUS = [
  'a[href]',
  'button:not([disabled]):not([tabindex="-1"])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

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

function luuPhanTuTraFocus() {
  const phanTuHienTai = typeof document === 'undefined' ? null : document.activeElement
  phanTuTraFocus = phanTuHienTai instanceof HTMLElement && phanTuHienTai !== document.body
    ? phanTuHienTai
    : null
}

function focusVaoXemTruoc() {
  nextTick(() => nutDong.value?.focus({ preventScroll: true }))
}

function traFocusVeTrigger() {
  const trigger = phanTuTraFocus
  phanTuTraFocus = null
  nextTick(() => {
    if (trigger instanceof HTMLElement && trigger.isConnected) {
      trigger.focus({ preventScroll: true })
    }
  })
}

function giuFocusTrongXemTruoc(event) {
  if (!dangMo.value || event.key !== 'Tab') return
  const khung = khungXemTruoc.value
  const danhSachFocus = khung instanceof HTMLElement
    ? Array.from(khung.querySelectorAll(BO_CHON_CO_THE_FOCUS))
      .filter((phanTu) => phanTu instanceof HTMLElement && !phanTu.hasAttribute('disabled'))
    : []

  if (danhSachFocus.length === 0) {
    event.preventDefault()
    khung?.focus({ preventScroll: true })
    return
  }

  const dau = danhSachFocus[0]
  const cuoi = danhSachFocus[danhSachFocus.length - 1]
  const focusNgoai = !khung?.contains(document.activeElement)
  if (event.shiftKey && (document.activeElement === dau || focusNgoai)) {
    event.preventDefault()
    cuoi.focus({ preventScroll: true })
  } else if (!event.shiftKey && (document.activeElement === cuoi || focusNgoai)) {
    event.preventDefault()
    dau.focus({ preventScroll: true })
  }
}

function xuLyPhimXemTruoc(event) {
  if (!dangMo.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    emit('dong')
    return
  }
  giuFocusTrongXemTruoc(event)
}

watch(dangMo, (mo, daMo) => {
  if (mo && !daMo) {
    luuPhanTuTraFocus()
    focusVaoXemTruoc()
  } else if (!mo && daMo) {
    traFocusVeTrigger()
  }
}, { flush: 'post' })

onMounted(() => {
  window.addEventListener('keydown', xuLyPhimXemTruoc)
  if (dangMo.value) {
    luuPhanTuTraFocus()
    focusVaoXemTruoc()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', xuLyPhimXemTruoc)
  if (dangMo.value) traFocusVeTrigger()
})
</script>

<template>
  <Teleport to="body">
    <div v-if="dangMo" class="pt-de-xuat-phu">
      <button class="pt-de-xuat-phu__nen" type="button" tabindex="-1" aria-label="Đóng xem trước đề xuất" @click="emit('dong')" />
      <section
        ref="khungXemTruoc"
        class="pt-de-xuat-phu__khung"
        role="dialog"
        tabindex="-1"
        aria-modal="true"
        :aria-labelledby="`pt-de-xuat-tieu-de-${proposal.id}`"
      >
        <header class="pt-de-xuat-phu__dau">
          <div>
            <p class="pt-kicker">BẢN XEM TRƯỚC · ĐỀ XUẤT PT</p>
            <h2 :id="`pt-de-xuat-tieu-de-${proposal.id}`">{{ proposal.title }}</h2>
          </div>
          <button ref="nutDong" class="nut nut--phu" type="button" @click="emit('dong')">Đóng</button>
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
                  <span v-if="exercise.rest_seconds !== null && exercise.rest_seconds !== undefined">
                    · {{ exercise.rest_seconds }} giây nghỉ
                  </span>
                  <span v-if="typeof exercise.notes === 'string' && exercise.notes.trim() !== ''">
                    · {{ exercise.notes }}
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
