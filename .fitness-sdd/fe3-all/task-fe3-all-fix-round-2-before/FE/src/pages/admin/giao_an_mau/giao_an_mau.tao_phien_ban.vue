<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import HopThoaiXungDotGiaoAn from '../../../components/danh_muc/hop_thoai_xung_dot_giao_an.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { xuLyGiaoAnMauDaCu } from '../../../services/giao_an_mau.api.js'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute()
const router = useRouter()
const store = useDanhMucStore()
const { baiTap, giaoAnMau } = storeToRefs(store)
const routeId = Number(route.params.id)
const dangLuu = ref(false)
const hienXungDot = ref(false)
const loi = ref(null)
const loiDoiSoat = ref('')
const phienBanHienTai = ref(null)
const banNhapChoDoiSoat = ref(null)
const nenMoiNhat = ref(null)

const form = reactive({
  new_code: '',
  name: '',
  goal: '',
  level: '',
  sessions_per_week: 1,
  description: '',
  status: 'HOAT_DONG',
  expected_content_version: null,
  days: [],
})

function saoChepCay(days = []) {
  return days.map((day) => ({
    ...day,
    exercises: (day.exercises ?? []).map((exercise) => ({ ...exercise })),
  }))
}

function saoChep(value) {
  return JSON.parse(JSON.stringify(value))
}

function nap(item) {
  if (!item || banNhapChoDoiSoat.value) return
  Object.assign(form, {
    new_code: `${item.code ?? ''}-V2`,
    name: item.name ?? '',
    goal: item.goal ?? '',
    level: item.level ?? '',
    sessions_per_week: item.sessions_per_week ?? item.days?.length ?? 1,
    description: item.description ?? '',
    status: item.status ?? 'HOAT_DONG',
    expected_content_version: item.content_version,
    days: saoChepCay(item.days ?? []),
  })
  phienBanHienTai.value = item.content_version
}

function layLoi(field) {
  return loi.value?.fieldErrors?.[field]?.[0] ?? ''
}

async function tai() {
  if (banNhapChoDoiSoat.value) return taiNenMoiNhat()
  hienXungDot.value = false
  nap(await store.taiChiTietGiaoAnMau(routeId))
}

/**
 * Tải nền mới nhất vào vùng so sánh mà không ghi đè bản nháp.
 * Đầu vào là id giáo án hiện tại; xử lý chỉ thực hiện một GET authoritative.
 * Kết quả là snapshot nền mới hoặc lỗi đối soát hiển thị trong flow.
 * Side effect chỉ đọc và giữ nguyên mọi field/ngày/bài tập đã gửi.
 * Quy tắc: stale recovery không tự rebase và không tự gửi lại revision.
 */
async function taiNenMoiNhat() {
  loiDoiSoat.value = ''
  const latest = await store.taiChiTietGiaoAnMau(routeId)
  if (latest) {
    nenMoiNhat.value = saoChep(latest)
    phienBanHienTai.value = latest.content_version
    return latest
  }
  nenMoiNhat.value = null
  loiDoiSoat.value = giaoAnMau.value.loiChiTiet?.message ?? 'Không thể tải nền mới nhất để đối soát.'
  return null
}

/**
 * Gửi một snapshot bất biến của bản nháp revision.
 * Đầu vào là form reactive; xử lý chụp bản nháp trước khi POST.
 * Kết quả chỉ điều hướng khi tạo thành công; stale mở vùng so sánh.
 * Side effect là một mutation với expected_content_version ban đầu và không retry.
 * Quy tắc copy-on-write giữ giáo án cũ bất biến và giữ bản nháp khi 409.
 */
async function xuLyLuu() {
  if (dangLuu.value) return
  loi.value = null
  loiDoiSoat.value = ''
  dangLuu.value = true
  banNhapChoDoiSoat.value = saoChep({ ...form, days: saoChepCay(form.days) })
  const result = await store.taoPhienBanGiaoAnMau(routeId, {
    ...form,
    days: saoChepCay(form.days),
  })
  loi.value = store.giaoAnMau.loiMutation
  dangLuu.value = false
  const xungDot = xuLyGiaoAnMauDaCu(loi.value)
  if (xungDot.laXungDot) {
    hienXungDot.value = true
    await taiNenMoiNhat()
    return
  }
  if (result?.new_template_id) {
    await router.push({ name: 'adminChiTietGiaoAnMau', params: { id: result.new_template_id } })
  }
}

/**
 * Đối soát explicit sau khi nền mới đã hiển thị.
 * Đầu vào là snapshot nền mới nhất; xử lý chỉ cập nhật expected_content_version.
 * Kết quả đóng dialog nhưng giữ nguyên metadata, mã mới và toàn bộ cây bản nháp.
 * Side effect chỉ ở local form, không phát sinh POST từ thao tác đối soát.
 * Quy tắc yêu cầu người dùng chủ động trước khi lần gửi sau dùng phiên bản mới.
 */
async function xuLyDoiSoat() {
  if (giaoAnMau.value.dangTaiChiTiet) return
  const latest = nenMoiNhat.value ?? await taiNenMoiNhat()
  if (!latest?.content_version) return
  form.expected_content_version = latest.content_version
  phienBanHienTai.value = latest.content_version
  loi.value = null
  hienXungDot.value = false
}

function xuLyDongXungDot() {
  if (!giaoAnMau.value.dangTaiChiTiet) hienXungDot.value = false
}

function xuLyHuy() {
  void router.push({ name: 'adminChiTietGiaoAnMau', params: { id: routeId } })
}

const soSanhXungDot = computed(() => {
  if (!banNhapChoDoiSoat.value || !nenMoiNhat.value) return []
  const draft = banNhapChoDoiSoat.value
  const latest = nenMoiNhat.value
  const differences = []
  if (draft.expected_content_version !== latest.content_version) {
    differences.push(`Phiên bản đã gửi ${draft.expected_content_version} · nền mới nhất ${latest.content_version}`)
  }
  for (const field of ['name', 'goal', 'level', 'sessions_per_week', 'description', 'status']) {
    if (draft[field] !== latest[field]) differences.push(`Metadata ${field} đã thay đổi trên nền mới nhất.`)
  }
  if (JSON.stringify(draft.days) !== JSON.stringify(latest.days ?? [])) {
    differences.push(`Cây bài tập khác nhau (${draft.days.length} ngày trong bản nháp · ${(latest.days ?? []).length} ngày trên nền).`)
  }
  return differences
})

onMounted(() => {
  void Promise.all([tai(), store.taiDanhSachBaiTap({ status: 'HOAT_DONG' })])
})
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Tạo phiên bản giáo án mẫu"
    :aria-busy="giaoAnMau.dangTaiChiTiet || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Tạo phiên bản giáo án mẫu"
      mo-ta="Bản nháp được giữ nguyên khi Backend báo 409 để bạn đối soát với nền mới nhất."
    />
    <TrangThaiTaiDuLieu
      v-if="giaoAnMau.dangTaiChiTiet && !form.expected_content_version"
      nhan="Đang tải giáo án mẫu…"
    />
    <TrangThaiLoi
      v-if="giaoAnMau.loiChiTiet && !banNhapChoDoiSoat"
      :thong-bao="giaoAnMau.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="giaoAnMau.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    />
    <form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="giao-an-revision-code"
          nhan="Mã phiên bản mới"
          bat-buoc
          :loi="layLoi('new_code')"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.new_code"
              required
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="giao-an-revision-name"
          nhan="Tên giáo án"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.name"
              required
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="giao-an-revision-goal"
          nhan="Mục tiêu"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.goal"
              required
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="giao-an-revision-level"
          nhan="Cấp độ"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model="form.level"
              required
            >
          </template>
        </TruongBieuMau>
        <TruongBieuMau
          id="giao-an-revision-sessions"
          nhan="Số buổi mỗi tuần"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.sessions_per_week"
              type="number"
              min="1"
              required
            >
          </template>
        </TruongBieuMau>
      </div>
      <p
        class="trang-danh-muc__canh-bao"
        role="note"
      >
        Đang dùng content_version {{ form.expected_content_version ?? '…' }}. Mỗi phiên bản là bản sao mới; không sửa giáo án cũ.
      </p>
      <CayGiaoAn
        v-model="form.days"
        :exercise-options="baiTap.danhSach"
        :disabled="dangLuu"
      />
      <p
        v-if="loi?.message"
        class="trang-danh-muc__loi"
        role="alert"
      >
        {{ loi.message }}
      </p>
      <div class="trang-danh-muc__hanh-dong">
        <button
          class="nut nut--phu"
          type="button"
          :disabled="dangLuu"
          @click="xuLyHuy"
        >
          Hủy
        </button>
        <button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu || !form.expected_content_version"
        >
          {{ dangLuu ? 'Đang tạo…' : 'Tạo phiên bản' }}
        </button>
      </div>
    </form>
    <section
      v-if="banNhapChoDoiSoat && nenMoiNhat"
      class="trang-danh-muc__doi-soat"
      aria-labelledby="tieu-de-doi-soat-giao-an"
    >
      <h2 id="tieu-de-doi-soat-giao-an">
        So sánh bản nháp và nền mới nhất
      </h2>
      <p>Bản nháp gửi với content_version {{ banNhapChoDoiSoat.expected_content_version }}; nền hiện tại là {{ nenMoiNhat.content_version }}.</p>
      <ul v-if="soSanhXungDot.length > 0">
        <li
          v-for="difference in soSanhXungDot"
          :key="difference"
        >
          {{ difference }}
        </li>
      </ul>
      <p v-else>
        Chưa phát hiện khác biệt ngoài phiên bản nội dung.
      </p>
    </section>
    <p
      v-if="loiDoiSoat"
      class="trang-danh-muc__loi"
      role="alert"
    >
      {{ loiDoiSoat }}
    </p>
    <HopThoaiXungDotGiaoAn
      :hien-thi="hienXungDot"
      :dang-tai="giaoAnMau.dangTaiChiTiet"
      :phien-ban-hien-tai="phienBanHienTai"
      @dong="xuLyDongXungDot"
      @tai-lai="xuLyDoiSoat"
    />
  </section>
</template>
