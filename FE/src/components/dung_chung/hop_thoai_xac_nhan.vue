<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  hienThi: {
    type: Boolean,
    default: false,
  },
  tieuDe: {
    type: String,
    required: true,
  },
  moTa: {
    type: String,
    default: '',
  },
  nhanXacNhan: {
    type: String,
    default: 'Xác nhận',
  },
  nhanHuy: {
    type: String,
    default: 'Hủy',
  },
  mangNguyHiem: {
    type: Boolean,
    default: false,
  },
  dangXuLy: {
    type: Boolean,
    default: false,
  },
  idHopThoai: {
    type: String,
    default: 'hop-thoai-xac-nhan',
  },
})

const emit = defineEmits(['xacNhan', 'huy', 'dong'])
const khungHopThoai = ref(null)
const nutHuy = ref(null)
const idTieuDe = `${props.idHopThoai}-tieu-de`
const idMoTa = `${props.idHopThoai}-mo-ta`
let phanTuTraFocus = null

const BO_CHON_CO_THE_FOCUS = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

function datFocusCoBan() {
  nextTick(() => {
    const diemFocus = nutHuy.value?.disabled ? khungHopThoai.value : nutHuy.value
    diemFocus?.focus({ preventScroll: true })
  })
}

function luuPhanTuMoHopThoai() {
  phanTuTraFocus = typeof document !== 'undefined' && document.activeElement instanceof HTMLElement
    ? document.activeElement
    : null
}

function traLaiFocusChoTrigger() {
  const diemTraFocus = phanTuTraFocus
  phanTuTraFocus = null

  nextTick(() => {
    if (diemTraFocus instanceof HTMLElement && diemTraFocus.isConnected) {
      diemTraFocus.focus({ preventScroll: true })
    }
  })
}

function layDanhSachCoTheFocus() {
  return khungHopThoai.value instanceof HTMLElement
    ? Array.from(khungHopThoai.value.querySelectorAll(BO_CHON_CO_THE_FOCUS))
      .filter((phanTu) => phanTu instanceof HTMLElement)
    : []
}

/**
 * Giu focus ben trong dialog khi nguoi dung dung Tab hoac Shift+Tab.
 *
 * Dau vao: keyboard event trong luc dialog dang mo.
 * Cach hoat dong: vong focus tu phan tu cuoi ve dau va nguoc lai; neu khong con
 * control kha dung thi dat focus vao chinh dialog.
 * Ket qua: keyboard focus khong thoat ra noi dung nen.
 * Side effect: preventDefault va thay activeElement khi cham bien focus.
 * UI Rule: dialog modal phai focus trap va tra focus ve trigger khi dong.
 */
function giuFocusTrongHopThoai(event) {
  if (!props.hienThi || event.key !== 'Tab') {
    return
  }

  const danhSachFocus = layDanhSachCoTheFocus()

  if (danhSachFocus.length === 0) {
    event.preventDefault()
    khungHopThoai.value?.focus({ preventScroll: true })
    return
  }

  const phanTuDau = danhSachFocus[0]
  const phanTuCuoi = danhSachFocus[danhSachFocus.length - 1]
  const phanTuHienTai = document.activeElement
  const focusNamNgoai = !khungHopThoai.value?.contains(phanTuHienTai)

  if (event.shiftKey && (phanTuHienTai === phanTuDau || focusNamNgoai)) {
    event.preventDefault()
    phanTuCuoi.focus({ preventScroll: true })
  } else if (!event.shiftKey && (phanTuHienTai === phanTuCuoi || focusNamNgoai)) {
    event.preventDefault()
    phanTuDau.focus({ preventScroll: true })
  }
}

/**
 * Xac nhan tuong tac nguy hiem o lop presentation, khong thuc hien mutation.
 *
 * Dau vao: khong co; mutation pending va payload do caller quan ly.
 * Cach hoat dong: chan confirm lap trong luc dangXuLy, sau do phat event xacNhan.
 * Ket qua: page/store tu revalidate va goi Backend theo contract neu can.
 * Side effect: chi phat event va khong thay doi authorization/entitlement.
 * UI rule: dialog co role/aria-modal, nut native va text pending ro rang.
 */
function xuLyXacNhan() {
  if (!props.dangXuLy) {
    emit('xacNhan')
  }
}

function xuLyHuy() {
  if (!props.dangXuLy) {
    emit('huy')
    emit('dong')
  }
}

function xuLyPhimTat(event) {
  if (!props.hienThi) {
    return
  }

  if (event.key === 'Escape') {
    event.preventDefault()
    xuLyHuy()
    return
  }

  giuFocusTrongHopThoai(event)
}

watch(() => props.hienThi, (hienThi, daHienThi) => {
  if (hienThi) {
    luuPhanTuMoHopThoai()
    datFocusCoBan()
  } else if (daHienThi) {
    traLaiFocusChoTrigger()
  }
})

onMounted(() => {
  if (props.hienThi) {
    luuPhanTuMoHopThoai()
    datFocusCoBan()
  }

  window.addEventListener('keydown', xuLyPhimTat)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', xuLyPhimTat)

  if (props.hienThi) {
    traLaiFocusChoTrigger()
  }
})
</script>

<template>
  <div
    v-if="props.hienThi"
    ref="khungHopThoai"
    class="hop-thoai-xac-nhan"
    role="dialog"
    tabindex="-1"
    aria-modal="true"
    :aria-labelledby="idTieuDe"
    :aria-describedby="props.moTa ? idMoTa : undefined"
    :aria-busy="props.dangXuLy"
    @click.self="xuLyHuy"
  >
    <section
      class="hop-thoai-xac-nhan__hop"
      :class="{ 'hop-thoai-xac-nhan__hop--nguy-hiem': props.mangNguyHiem }"
    >
      <h2
        :id="idTieuDe"
        class="hop-thoai-xac-nhan__tieu-de"
      >
        {{ props.tieuDe }}
      </h2>
      <p
        v-if="props.moTa"
        :id="idMoTa"
        class="hop-thoai-xac-nhan__mo-ta"
      >
        {{ props.moTa }}
      </p>
      <slot />
      <div class="hop-thoai-xac-nhan__hanh-dong">
        <button
          ref="nutHuy"
          class="nut nut--phu"
          type="button"
          :disabled="props.dangXuLy"
          @click="xuLyHuy"
        >
          {{ props.nhanHuy }}
        </button>
        <button
          class="nut"
          :class="props.mangNguyHiem ? 'nut--nguy-hiem' : 'nut--chinh'"
          type="button"
          :disabled="props.dangXuLy"
          @click="xuLyXacNhan"
        >
          {{ props.dangXuLy ? 'Đang xử lý…' : props.nhanXacNhan }}
        </button>
      </div>
    </section>
  </div>
</template>
