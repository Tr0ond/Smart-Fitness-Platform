<script setup>
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/PT/thanh_dieu_huong_hoi_vien.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import { useDeXuatStore } from '../../../stores/de_xuat.store.js'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const deXuatStore = useDeXuatStore()
const hoiVienStore = useHoiVienPtStore()
const { banNhap, dangTaoDeXuat, loiTaoDeXuat, thaoTacDangCho, ketQuaDeXuat } = storeToRefs(deXuatStore)
const { chiTietHoiVien, keHoachTap, dangTaiChiTietHoiVien, loiChiTietHoiVien, dangTaiKeHoachTap, loiKeHoachTap } = storeToRefs(hoiVienStore)
const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const assignmentId = computed(() => {
  const assignment = chiTietHoiVien.value?.assignment
  if (assignment?.is_current === false) return null
  const id = assignment?.id
  return Number.isSafeInteger(Number(id)) && Number(id) > 0 ? Number(id) : null
})
const keHoachChinhThuc = computed(() => keHoachTap.value?.plan ?? null)
const phienBanChinhThuc = computed(() => keHoachChinhThuc.value?.current_version ?? null)
const outcomeUnknown = computed(() => thaoTacDangCho.value?.outcomeUnknown === true)
const laXungDot = computed(() => loiTaoDeXuat.value?.httpStatus === 409)
const cacLoiTruong = computed(() => Object.entries(loiTaoDeXuat.value?.fieldErrors ?? {})
  .flatMap(([truong, loi]) => loi.map((thongBao) => ({ truong, thongBao }))))
const coTheThuLaiProfile = computed(() => loiChiTietHoiVien.value?.isNetworkError === true
  || (Number.isInteger(loiChiTietHoiVien.value?.httpStatus) && loiChiTietHoiVien.value.httpStatus >= 500))

function saoChepNgayTap(day, index) {
  return {
    order: Number(day.order ?? index + 1),
    weekday: Number(day.weekday ?? index + 2),
    name: String(day.name ?? `Ngày tập ${index + 1}`),
    estimated_minutes: Number(day.estimated_minutes ?? 60),
    exercises: (day.exercises ?? []).map((exercise, exerciseIndex) => ({
      exercise_id: Number(exercise.exercise_id ?? exercise.id) || '',
      order: Number(exercise.order ?? exerciseIndex + 1),
      target_sets: Number(exercise.target_sets ?? 3),
      min_reps: Number(exercise.min_reps ?? 8),
      max_reps: Number(exercise.max_reps ?? 12),
      target_weight_kg: exercise.target_weight_kg ?? null,
      rest_seconds: Number(exercise.rest_seconds ?? 90),
      notes: exercise.notes ?? null,
    })),
  }
}

function napDraftTuKeHoachNeuConMoi() {
  const plan = keHoachChinhThuc.value
  if (!plan || banNhap.value.title !== '' || banNhap.value.plan.name !== '') return
  const version = plan.current_version ?? {}
  const days = Array.isArray(version.days) && version.days.length > 0
    ? version.days.slice(0, 7).map(saoChepNgayTap)
    : banNhap.value.plan.days
  banNhap.value.change_type = 'DIEU_CHINH'
  banNhap.value.title = `Đề xuất điều chỉnh ${plan.name ?? 'kế hoạch hiện tại'}`
  banNhap.value.plan.name = String(plan.name ?? '')
  banNhap.value.plan.goal = String(version.goal ?? '')
  banNhap.value.plan.template_id = version.template_id ?? null
  banNhap.value.plan.days = days
}

/** Revalidate current assignment and read official Plan before preparing an adjustment. */
async function taiNguCanhDeXuat() {
  if (!coIdHopLe.value) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  const id = memberId.value
  hoiVienStore.chonHoiVien(id)
  deXuatStore.chonHoiVien(id)
  const profile = await hoiVienStore.taiChiTietHoiVien(id)
  if (String(memberId.value) !== String(id)) return
  if (!profile) {
    if ([403, 404].includes(hoiVienStore.loiChiTietHoiVien?.httpStatus)) {
      deXuatStore.xoaDuLieu()
      await router.replace({ name: 'ptHoiVien' })
    }
    return
  }
  if (!profile.assignment?.id || profile.assignment.is_current === false) {
    deXuatStore.xoaDuLieu()
    hoiVienStore.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  if (hoiVienStore.hoiVienDaChonId !== Number(id)) return

  await hoiVienStore.taiKeHoachTapHoiVien(id)
  if ([403, 404].includes(loiKeHoachTap.value?.httpStatus)) {
    deXuatStore.xoaDuLieu()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  if (hoiVienStore.hoiVienDaChonId !== Number(id)) return
  napDraftTuKeHoachNeuConMoi()
}

function themNgayTap() {
  if (banNhap.value.plan.days.length >= 7) return
  const order = banNhap.value.plan.days.length + 1
  banNhap.value.plan.days.push({
    order,
    weekday: Math.min(8, order + 1),
    name: `Ngày tập ${order}`,
    estimated_minutes: 60,
    exercises: [{
      exercise_id: '', order: 1, target_sets: 3, min_reps: 8, max_reps: 12,
      target_weight_kg: null, rest_seconds: 90, notes: null,
    }],
  })
}

function themBaiTap(day) {
  if (day.exercises.length >= 50) return
  day.exercises.push({
    exercise_id: '', order: day.exercises.length + 1, target_sets: 3, min_reps: 8,
    max_reps: 12, target_weight_kg: null, rest_seconds: 90, notes: null,
  })
}

function capNhatKhoiLuong(day, exercise, event) {
  const giaTri = event.target.value
  exercise.target_weight_kg = giaTri === '' ? null : Number(giaTri)
}

function capNhatTemplate(event) {
  const giaTri = event.target.value
  banNhap.value.plan.template_id = giaTri === '' ? null : Number(giaTri)
}

async function guiDeXuat() {
  if (dangTaoDeXuat.value || assignmentId.value === null) return
  const ketQua = await deXuatStore.taoDeXuatKeHoach()
  if (ketQua?.scopeLost) {
    hoiVienStore.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function thuLaiDeXuat() {
  const ketQua = await deXuatStore.thuLaiDeXuat()
  if (ketQua?.scopeLost) {
    hoiVienStore.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function taiLaiSauXungDot() {
  const id = memberId.value
  const profile = await hoiVienStore.taiChiTietHoiVien(id)
  if (!profile) {
    if ([403, 404].includes(loiChiTietHoiVien.value?.httpStatus)) {
      deXuatStore.xoaDuLieu()
      await router.replace({ name: 'ptHoiVien' })
    }
    return
  }
  if (hoiVienStore.hoiVienDaChonId !== Number(id)) return
  if (!profile.assignment?.id || profile.assignment.is_current === false) {
    deXuatStore.xoaDuLieu()
    hoiVienStore.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  await hoiVienStore.taiKeHoachTapHoiVien(id)
  if (hoiVienStore.hoiVienDaChonId !== Number(id)
    || [403, 404].includes(loiKeHoachTap.value?.httpStatus)) {
    deXuatStore.xoaDuLieu()
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  const danhSach = await deXuatStore.taiDanhSachDeXuat(id)
  if (danhSach?.scopeLost) {
    hoiVienStore.xoaHoiVienDangChon()
    await router.replace({ name: 'ptHoiVien' })
  }
}

watch(() => route.params.id, () => { void taiNguCanhDeXuat() }, { flush: 'sync', immediate: true })

onBeforeRouteLeave((to) => {
  const giuBanNhap = ['ptDeXuatKeHoach', 'ptTaoDeXuatKeHoach'].includes(to.name)
    && String(to.params.id) === String(memberId.value)
  if (!giuBanNhap) {
    deXuatStore.xoaDuLieu()
    hoiVienStore.xoaHoiVienDangChon()
  }
})
</script>

<template>
  <section
    class="pt-trang pt-trang-tao-de-xuat"
    aria-label="Tạo đề xuất kế hoạch tập"
    :aria-busy="dangTaiKeHoachTap || dangTaoDeXuat"
  >
    <TieuDeTrang
      tieu-de="Tạo đề xuất kế hoạch"
      mo-ta="Đề xuất chỉ chờ hội viên xác nhận. Quyền tạo phụ thuộc assignment hiện tại, không phụ thuộc gói, chat hoặc lượt PT."
    >
      <template #hanhDong>
        <RouterLink class="nut nut--lien-ket" :to="{ name: 'ptDeXuatKeHoach', params: { id: memberId } }">
          Danh sách đề xuất
        </RouterLink>
      </template>
    </TieuDeTrang>
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <section class="pt-card" aria-labelledby="pt-de-xuat-ngu-canh-tieu-de">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">OFFICIAL PLAN CONTEXT</p>
          <h2 id="pt-de-xuat-ngu-canh-tieu-de">Ngữ cảnh kế hoạch chính thức</h2>
        </div>
        <span class="pt-badge">Assignment #{{ assignmentId ?? '—' }}</span>
      </div>
      <p v-if="dangTaiKeHoachTap">Đang tải kế hoạch chính thức để làm ngữ cảnh…</p>
      <p v-else-if="keHoachChinhThuc">
        Bản nháp được khởi tạo từ “{{ keHoachChinhThuc.name }}”, phiên bản {{ phienBanChinhThuc?.number ?? 'hiện tại' }}. Nội dung này chưa thay đổi kế hoạch chính thức.
      </p>
      <TrangThaiTaiDuLieu v-if="dangTaiChiTietHoiVien" nhan="Đang xác minh assignment hiện tại…" />
      <TrangThaiLoi
        v-if="loiChiTietHoiVien"
        :thong-bao="layThongBaoLoiApi(loiChiTietHoiVien, 'Không thể xác minh assignment hiện tại.')"
        :co-the-thu-lai="coTheThuLaiProfile"
        :dang-thu-lai="dangTaiChiTietHoiVien"
        @thu-lai="taiNguCanhDeXuat"
      />
      <TrangThaiLoi
        v-if="loiKeHoachTap"
        :thong-bao="layThongBaoLoiApi(loiKeHoachTap, 'Không thể tải kế hoạch chính thức.')"
        :co-the-thu-lai="loiKeHoachTap.isNetworkError || loiKeHoachTap.httpStatus >= 500"
        :dang-thu-lai="dangTaiKeHoachTap"
        @thu-lai="taiNguCanhDeXuat"
      />
      <p v-else-if="!loiKeHoachTap">
        Chưa có kế hoạch chính thức. Hãy tạo đề xuất mới bằng danh mục mã bài tập đã biết; trang không gọi một API danh mục PT chưa được xác nhận.
      </p>
    </section>

    <form class="pt-card pt-form pt-de-xuat-form" @submit.prevent="guiDeXuat">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">DRAFT</p>
          <h2>Nội dung gửi hội viên</h2>
        </div>
        <span class="pt-badge">Không có thao tác áp dụng từ Web PT</span>
      </div>
      <fieldset :disabled="dangTaoDeXuat || outcomeUnknown">
        <label class="pt-field" for="pt-de-xuat-loai">
          <span class="pt-field__nhan">Loại thay đổi</span>
          <select id="pt-de-xuat-loai" v-model="banNhap.change_type" required>
            <option value="TAO_MOI">Tạo mới kế hoạch</option>
            <option v-if="keHoachChinhThuc" value="DIEU_CHINH">Điều chỉnh kế hoạch hiện tại</option>
            <option v-if="keHoachChinhThuc" value="THAY_BAI">Thay bài tập</option>
          </select>
        </label>
        <label class="pt-field" for="pt-de-xuat-tieu-de">
          <span class="pt-field__nhan">Tiêu đề</span>
          <input id="pt-de-xuat-tieu-de" v-model="banNhap.title" name="title" maxlength="200" required>
        </label>
        <label class="pt-field" for="pt-de-xuat-giai-thich">
          <span class="pt-field__nhan">Giải thích cho hội viên</span>
          <textarea id="pt-de-xuat-giai-thich" v-model="banNhap.explanation" name="explanation" maxlength="10000" rows="4" required />
        </label>
        <label class="pt-field" for="pt-de-xuat-ngay-ap-dung">
          <span class="pt-field__nhan">Ngày dự kiến áp dụng</span>
          <input id="pt-de-xuat-ngay-ap-dung" v-model="banNhap.effective_from" type="date" name="effective_from" required>
          <small>Ngày phải từ hôm nay trở đi theo múi giờ nghiệp vụ của máy chủ.</small>
        </label>

        <section class="pt-form__nhom" aria-labelledby="pt-de-xuat-ke-hoach-tieu-de">
          <h3 id="pt-de-xuat-ke-hoach-tieu-de">Kế hoạch đề xuất</h3>
          <label class="pt-field" for="pt-de-xuat-ke-hoach-ten">
            <span class="pt-field__nhan">Tên kế hoạch</span>
            <input id="pt-de-xuat-ke-hoach-ten" v-model="banNhap.plan.name" maxlength="150" required>
          </label>
          <label class="pt-field" for="pt-de-xuat-ke-hoach-muc-tieu">
            <span class="pt-field__nhan">Mục tiêu</span>
            <input id="pt-de-xuat-ke-hoach-muc-tieu" v-model="banNhap.plan.goal" maxlength="100" required>
          </label>
          <label class="pt-field" for="pt-de-xuat-ke-hoach-template">
            <span class="pt-field__nhan">Mã giáo án mẫu <span class="pt-field__phu">(không bắt buộc)</span></span>
            <input
              id="pt-de-xuat-ke-hoach-template"
              type="number"
              min="1"
              :value="banNhap.plan.template_id ?? ''"
              @input="capNhatTemplate"
            >
          </label>
        </section>

        <section
          v-for="(day, dayIndex) in banNhap.plan.days"
          :key="day.order"
          class="pt-form__nhom pt-de-xuat-ngay"
          :aria-labelledby="`pt-de-xuat-ngay-tieu-de-${dayIndex}`"
        >
          <div class="pt-card__dau">
            <h3 :id="`pt-de-xuat-ngay-tieu-de-${dayIndex}`">Ngày tập {{ day.order }}</h3>
            <button v-if="banNhap.plan.days.length > 1" class="nut nut--phu" type="button" @click="banNhap.plan.days.splice(dayIndex, 1)">
              Xóa ngày
            </button>
          </div>
          <div class="pt-form__luoi">
            <label class="pt-field" :for="`pt-de-xuat-ngay-ten-${dayIndex}`">
              <span class="pt-field__nhan">Tên ngày</span>
              <input :id="`pt-de-xuat-ngay-ten-${dayIndex}`" v-model="day.name" maxlength="150" required>
            </label>
            <label class="pt-field" :for="`pt-de-xuat-ngay-thu-${dayIndex}`">
              <span class="pt-field__nhan">Thứ (giá trị 2–8)</span>
              <input :id="`pt-de-xuat-ngay-thu-${dayIndex}`" v-model.number="day.weekday" type="number" min="2" max="8" required>
            </label>
            <label class="pt-field" :for="`pt-de-xuat-ngay-phut-${dayIndex}`">
              <span class="pt-field__nhan">Thời lượng (phút)</span>
              <input :id="`pt-de-xuat-ngay-phut-${dayIndex}`" v-model.number="day.estimated_minutes" type="number" min="1" max="1440" required>
            </label>
          </div>

          <fieldset v-for="(exercise, exerciseIndex) in day.exercises" :key="exercise.order" class="pt-de-xuat-bai">
            <legend>Bài tập {{ exercise.order }}</legend>
            <div class="pt-form__luoi">
              <label class="pt-field" :for="`pt-de-xuat-bai-id-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Mã bài tập</span>
                <input :id="`pt-de-xuat-bai-id-${dayIndex}-${exerciseIndex}`" v-model.number="exercise.exercise_id" type="number" min="1" required>
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-hiep-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Số hiệp</span>
                <input :id="`pt-de-xuat-bai-hiep-${dayIndex}-${exerciseIndex}`" v-model.number="exercise.target_sets" type="number" min="1" max="100" required>
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-rep-min-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Lần lặp tối thiểu</span>
                <input :id="`pt-de-xuat-bai-rep-min-${dayIndex}-${exerciseIndex}`" v-model.number="exercise.min_reps" type="number" min="1" max="1000" required>
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-rep-max-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Lần lặp tối đa</span>
                <input :id="`pt-de-xuat-bai-rep-max-${dayIndex}-${exerciseIndex}`" v-model.number="exercise.max_reps" type="number" min="1" max="1000" required>
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-tai-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Mức tạ kg <span class="pt-field__phu">(không bắt buộc)</span></span>
                <input
                  :id="`pt-de-xuat-bai-tai-${dayIndex}-${exerciseIndex}`"
                  type="number"
                  min="0"
                  max="9999.99"
                  step="0.01"
                  :value="exercise.target_weight_kg ?? ''"
                  @input="capNhatKhoiLuong(day, exercise, $event)"
                >
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-nghi-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Nghỉ giữa hiệp (giây)</span>
                <input :id="`pt-de-xuat-bai-nghi-${dayIndex}-${exerciseIndex}`" v-model.number="exercise.rest_seconds" type="number" min="0" max="86400" required>
              </label>
              <label class="pt-field" :for="`pt-de-xuat-bai-ghi-chu-${dayIndex}-${exerciseIndex}`">
                <span class="pt-field__nhan">Ghi chú bài tập</span>
                <textarea :id="`pt-de-xuat-bai-ghi-chu-${dayIndex}-${exerciseIndex}`" v-model="exercise.notes" maxlength="1000" rows="2" />
              </label>
            </div>
            <button v-if="day.exercises.length > 1" class="nut nut--phu" type="button" @click="day.exercises.splice(exerciseIndex, 1)">
              Xóa bài tập
            </button>
          </fieldset>
          <button class="nut nut--phu" type="button" :disabled="day.exercises.length >= 50" @click="themBaiTap(day)">
            Thêm bài tập
          </button>
        </section>
        <button class="nut nut--phu" type="button" :disabled="banNhap.plan.days.length >= 7" @click="themNgayTap">
          Thêm ngày tập
        </button>
      </fieldset>

      <VungThongBao
        v-if="loiTaoDeXuat && !outcomeUnknown && !laXungDot"
        :danh-sach="[{ kieu: 'nguy_hiem', noiDung: layThongBaoLoiApi(loiTaoDeXuat, 'Không thể gửi đề xuất.') }, ...cacLoiTruong.map((loi) => ({ kieu: 'nguy_hiem', noiDung: `${loi.truong}: ${loi.thongBao}` }))]"
      />
      <p v-if="laXungDot" class="pt-thong-bao pt-thong-bao--canh-bao" role="alert">
        Kế hoạch hoặc assignment đã thay đổi. Bản nháp được giữ nguyên; tải lại ngữ cảnh và danh sách trước khi quyết định gửi lại.
      </p>
      <button v-if="laXungDot" class="nut nut--phu" type="button" @click="taiLaiSauXungDot">
        Tải lại ngữ cảnh và danh sách
      </button>
      <p v-if="outcomeUnknown" class="pt-thong-bao pt-thong-bao--canh-bao" role="status">
        Chưa xác định được kết quả. Hệ thống đã thử tải lại danh sách để đối chiếu; danh sách không có request key nên kết quả vẫn chưa được xác nhận. Khi thử lại, hệ thống gửi nguyên body và khóa ban đầu để Backend nhận diện replay.
      </p>
      <button
        v-if="outcomeUnknown"
        class="nut nut--phu"
        type="button"
        :disabled="dangTaoDeXuat"
        :aria-busy="dangTaoDeXuat"
        @click="thuLaiDeXuat"
      >
        {{ dangTaoDeXuat ? 'Đang gửi lại an toàn…' : 'Gửi lại nguyên đề xuất an toàn' }}
      </button>
      <div v-else class="pt-form__hanh-dong">
        <button class="nut nut--chinh" type="submit" :disabled="assignmentId === null || dangTaoDeXuat || dangTaiKeHoachTap || Boolean(loiKeHoachTap)" :aria-busy="dangTaoDeXuat">
          {{ dangTaoDeXuat ? 'Đang gửi…' : 'Gửi đề xuất cho hội viên' }}
        </button>
      </div>
      <p v-if="ketQuaDeXuat" class="pt-thong-bao pt-thong-bao--thanh-cong" role="status">
        {{ ketQuaDeXuat.replayed
          ? 'Backend xác nhận đề xuất đã được gửi trước đó và vẫn chờ hội viên xác nhận.'
          : 'Đề xuất đã được gửi ở trạng thái chờ hội viên xác nhận.' }}
        Hết hạn: {{ ketQuaDeXuat.expires_at }}.
      </p>
    </form>
  </section>
</template>

<style scoped>
.pt-de-xuat-form fieldset {
  min-width: 0;
  border: 0;
  margin: 0;
  padding: 0;
}

.pt-de-xuat-form > fieldset {
  display: grid;
  gap: var(--khoang-4);
}

.pt-de-xuat-form fieldset[disabled] {
  opacity: 0.78;
}

.pt-form__nhom {
  display: grid;
  gap: var(--khoang-4);
  border-top: 1px solid var(--mau-vien);
  padding-top: var(--khoang-4);
}

.pt-form__luoi {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 210px), 1fr));
  gap: var(--khoang-3);
}

.pt-de-xuat-bai {
  display: grid;
  gap: var(--khoang-3);
  border: 1px solid var(--mau-vien) !important;
  border-radius: var(--bo-tron-the);
  padding: var(--khoang-4) !important;
}

.pt-de-xuat-bai legend {
  padding-inline: var(--khoang-2);
  font-weight: 700;
}
</style>
