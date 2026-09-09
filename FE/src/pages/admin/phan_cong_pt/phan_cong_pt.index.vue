<script setup>
import { computed, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import { usePhanCongPtStore } from '../../../stores/phan_cong_pt.store.js'
import { useTaiKhoanStore } from '../../../stores/tai_khoan.store.js'
import { datFocusVaoTruongLoiDau } from '../../../composables/su_dung_bieu_mau.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const router = useRouter()
const store = usePhanCongPtStore()
const taiKhoanStore = useTaiKhoanStore()
const { danhSach, boLoc, phanTrang, dangTai, loiTaiDanhSach, daTaiLanDau, chiTiet, dangTaiChiTiet, loiTaiChiTiet, dangMutation, loiMutation } = storeToRefs(store)
const { danhSachHoiVien, danhSachHuanLuyenVien } = storeToRefs(taiKhoanStore)

const draftFilter = reactive({ member_id: '', trainer_id: '', current: '' })
const draftCreate = reactive({ member_id: '', trainer_id: '', start_at: '', end_at: '' })
const draftEnd = reactive({ reason: '' })
const draftReassign = reactive({ trainer_id: '', start_at: '', reason: '' })
const modal = reactive({ value: '', assignment: null, createPayload: null })

const memberOptions = computed(() => danhSachHoiVien.value.filter((account) => Number(account.member_profile?.id) > 0))
const trainerOptions = computed(() => danhSachHuanLuyenVien.value.filter((account) => Number(account.trainer_profile?.id) > 0))

function toIso(value) {
  if (typeof value !== 'string' || value.trim() === '') return undefined
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toISOString()
}

function layTenHoSo(options, profileKey, id) {
  const danhSachOptions = Array.isArray(options) ? options : options.value
  const account = danhSachOptions.find((item) => Number(item?.[profileKey]?.id) === Number(id))
  if (!account) return id ? `#${id}` : 'Chưa chọn'
  return `${account.name} · ${account[profileKey]?.code ?? `#${id}`}`
}

function layNhanThoiGian(value) {
  return value === undefined || value === null || value === '' ? 'Không đặt' : value
}

function saoChepAssignment(assignment) {
  if (!assignment) return null

  return Object.freeze({
    ...assignment,
    member: assignment.member ? Object.freeze({ ...assignment.member }) : assignment.member,
    trainer: assignment.trainer ? Object.freeze({ ...assignment.trainer }) : assignment.trainer,
  })
}

function taoPayloadTao() {
  return Object.freeze({
    member_id: Number(draftCreate.member_id),
    trainer_id: Number(draftCreate.trainer_id),
    start_at: toIso(draftCreate.start_at),
    end_at: toIso(draftCreate.end_at),
  })
}

function layLoiTruong(truong) {
  const errors = loiMutation.value?.fieldErrors?.[truong]
  return Array.isArray(errors) ? errors[0] ?? '' : ''
}

function coLoiTruong(truong) {
  return layLoiTruong(truong) !== ''
}

function layThuTuTruongTheoModal(loaiModal = modal.value) {
  if (loaiModal === 'create') return ['member_id', 'trainer_id', 'start_at', 'end_at']
  if (loaiModal === 'end') return ['reason']
  return ['trainer_id', 'start_at', 'reason']
}

function layIdTheoTruongTheoModal(loaiModal = modal.value) {
  if (loaiModal === 'create') {
    return {
      member_id: 'assignment-create-member',
      trainer_id: 'assignment-create-trainer',
      start_at: 'assignment-create-start',
      end_at: 'assignment-create-end',
    }
  }
  if (loaiModal === 'end') return { reason: 'assignment-end-reason' }
  return {
    trainer_id: 'assignment-reassign-trainer',
    start_at: 'assignment-reassign-start',
    reason: 'assignment-reassign-reason',
  }
}

async function datFocusLoiMutation(loaiModal = modal.value) {
  const idDuPhong = loaiModal === 'create'
    ? (modal.value === 'create' ? 'assignment-modal-loi-mutation' : 'phan-cong-pt-loi-mutation')
    : (modal.value === '' ? 'phan-cong-pt-unknown' : 'assignment-modal-loi-mutation')
  await datFocusVaoTruongLoiDau(
    loiMutation.value?.fieldErrors,
    layThuTuTruongTheoModal(loaiModal),
    layIdTheoTruongTheoModal(loaiModal),
    idDuPhong,
  )
}

function layBoLoc() {
  return {
    member_id: draftFilter.member_id || '',
    trainer_id: draftFilter.trainer_id || '',
    current: draftFilter.current === '' ? '' : draftFilter.current === 'true',
  }
}

async function xuLyLoiQuyen(error = loiTaiDanhSach.value || loiTaiChiTiet.value || loiMutation.value) {
  if (error?.httpStatus === 403) {
    store.xoaDuLieu()
    await router.replace({ name: 'khongCoQuyen' })
    return true
  }
  return false
}

async function taiDanhSach() {
  await store.taiDanhSachPhanCong({ boLoc: layBoLoc(), trang: 1 })
  await xuLyLoiQuyen()
}

async function chuyenTrang(trang) {
  if (!Number.isInteger(trang) || trang < 1 || trang > phanTrang.value.last_page) return
  await store.taiDanhSachPhanCong({ boLoc: layBoLoc(), trang })
  await xuLyLoiQuyen()
}

async function taiSelectors() {
  await Promise.all([
    taiKhoanStore.taiDanhSachHoiVien({ trang: 1 }),
    taiKhoanStore.taiDanhSachHuanLuyenVien({ trang: 1 }),
  ])
}

async function xemChiTiet(assignment) {
  await store.taiChiTietPhanCong(assignment.id)
  await xuLyLoiQuyen(loiTaiChiTiet.value)
}

function moModal(type, assignment) {
  modal.value = type
  modal.assignment = saoChepAssignment(assignment)
  modal.createPayload = type === 'create' ? taoPayloadTao() : null
  if (type === 'end') {
    draftEnd.reason = ''
  }
  if (type === 'reassign') {
    draftReassign.trainer_id = ''
    draftReassign.start_at = ''
    draftReassign.reason = ''
  }
}

function dongModal() {
  if (!dangMutation.value) {
    modal.value = ''
    modal.assignment = null
    modal.createPayload = null
    draftEnd.reason = ''
    draftReassign.trainer_id = ''
    draftReassign.start_at = ''
    draftReassign.reason = ''
  }
}

async function xacNhanMutation() {
  const assignment = modal.assignment
  const type = modal.value
  if (!type || (type !== 'create' && !assignment)) return
  let ketQua = null
  if (type === 'create') {
    ketQua = await store.taoMoiPhanCong(modal.createPayload ?? taoPayloadTao())
  } else if (type === 'end') {
    ketQua = await store.ketThucPhanCong(assignment.id, { reason: draftEnd.reason.trim() || undefined })
  } else {
    ketQua = await store.phanCongLai(assignment.id, {
      trainer_id: Number(draftReassign.trainer_id),
      start_at: toIso(draftReassign.start_at),
      reason: draftReassign.reason.trim() || undefined,
    })
  }
  const loiSauMutation = loiMutation.value
  if (type === 'create') {
    if (loiSauMutation || ketQua !== null) {
      dongModal()
      if (loiSauMutation?.httpStatus !== 403 && loiSauMutation) {
        await datFocusLoiMutation(type)
      }
    }
  } else if (loiSauMutation?.httpStatus === 422) {
    await datFocusLoiMutation()
  } else if (ketQua !== null || loiSauMutation?.outcomeUnknown || loiSauMutation?.httpStatus === 403) {
    dongModal()
    if (loiSauMutation?.outcomeUnknown) {
      await datFocusLoiMutation(type)
    }
  } else if (loiSauMutation) {
    await datFocusLoiMutation()
  }
  if (type === 'create' && ketQua !== null) {
    draftCreate.start_at = ''
    draftCreate.end_at = ''
  }
  await xuLyLoiQuyen(loiSauMutation)
}

async function taoMoi() {
  moModal('create', null)
}

const thongBaoDanhSach = computed(() => layThongBaoLoiApi(
  loiTaiDanhSach.value,
  'Không thể tải danh sách phân công PT. Vui lòng thử lại sau.',
))
const coTheThuLai = computed(() => loiTaiDanhSach.value?.isNetworkError === true
  || (Number.isInteger(loiTaiDanhSach.value?.httpStatus) && loiTaiDanhSach.value.httpStatus >= 500))
const thongBaoMutation = computed(() => layThongBaoLoiApi(
  loiMutation.value,
  'Không thể xử lý phân công PT. Vui lòng kiểm tra lại dữ liệu.',
))

onMounted(async () => {
  await Promise.all([taiSelectors(), taiDanhSach()])
})
</script>

<template>
  <section
    class="trang-phan-cong-pt"
    aria-label="Phân công PT"
    :aria-busy="dangTai || dangMutation"
  >
    <TieuDeTrang
      tieu-de="Phân công PT"
      mo-ta="Nguồn dữ liệu assignment authoritative; lịch sử và Chat của exact assignment luôn được giữ lại."
    />

    <section
      class="phan-cong-pt__bo-loc"
      aria-labelledby="phan-cong-pt-bo-loc"
    >
      <h2 id="phan-cong-pt-bo-loc">
        Bộ lọc server
      </h2>
      <div class="luoi-bieu-mau">
        <div class="truong-bieu-mau">
          <label for="assignment-filter-member">Hội viên</label>
          <select
            id="assignment-filter-member"
            v-model="draftFilter.member_id"
          >
            <option value="">
              Tất cả Hội viên
            </option>
            <option
              v-for="account in memberOptions"
              :key="account.id"
              :value="account.member_profile.id"
            >
              {{ account.name }} · {{ account.member_profile.code }}
            </option>
          </select>
          <small v-if="memberOptions.length === 0">Chưa có Hội viên có hồ sơ authoritative để lọc.</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="assignment-filter-trainer">PT</label>
          <select
            id="assignment-filter-trainer"
            v-model="draftFilter.trainer_id"
          >
            <option value="">
              Tất cả PT
            </option>
            <option
              v-for="account in trainerOptions"
              :key="account.id"
              :value="account.trainer_profile.id"
            >
              {{ account.name }} · {{ account.trainer_profile.code }}
            </option>
          </select>
          <small v-if="trainerOptions.length === 0">Chưa có PT có hồ sơ authoritative để lọc.</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="assignment-filter-current">Hiệu lực tại hiện tại</label>
          <select
            id="assignment-filter-current"
            v-model="draftFilter.current"
          >
            <option value="">
              Tất cả
            </option>
            <option value="true">
              Đang hiệu lực
            </option>
            <option value="false">
              Lịch sử/tương lai
            </option>
          </select>
        </div>
      </div>
      <button
        class="nut nut--chinh"
        type="button"
        :disabled="dangTai"
        @click="taiDanhSach"
      >
        Áp dụng bộ lọc
      </button>
    </section>

    <section
      class="phan-cong-pt__tao-moi"
      aria-labelledby="phan-cong-pt-tao-moi"
    >
      <h2 id="phan-cong-pt-tao-moi">
        Tạo phân công
      </h2>
      <div class="luoi-bieu-mau">
        <div class="truong-bieu-mau">
          <label for="assignment-create-member">Hội viên</label>
          <select
            id="assignment-create-member"
            v-model="draftCreate.member_id"
            name="member_id"
            :disabled="dangMutation || modal.value !== ''"
            :aria-invalid="coLoiTruong('member_id')"
            :aria-describedby="coLoiTruong('member_id') ? 'assignment-create-member-error' : undefined"
          >
            <option value="">
              Chọn Hội viên
            </option>
            <option
              v-for="account in memberOptions"
              :key="account.id"
              :value="account.member_profile.id"
            >
              {{ account.name }} · {{ account.member_profile.code }}
            </option>
          </select>
          <small
            v-if="coLoiTruong('member_id')"
            id="assignment-create-member-error"
            role="alert"
          >
            {{ layLoiTruong('member_id') }}
          </small>
          <small v-if="memberOptions.length === 0">Không có Hội viên đủ điều kiện để tạo phân công.</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="assignment-create-trainer">PT</label>
          <select
            id="assignment-create-trainer"
            v-model="draftCreate.trainer_id"
            name="trainer_id"
            :disabled="dangMutation || modal.value !== ''"
            :aria-invalid="coLoiTruong('trainer_id')"
            :aria-describedby="coLoiTruong('trainer_id') ? 'assignment-create-trainer-error' : undefined"
          >
            <option value="">
              Chọn PT
            </option>
            <option
              v-for="account in trainerOptions"
              :key="account.id"
              :value="account.trainer_profile.id"
            >
              {{ account.name }} · {{ account.trainer_profile.code }}
            </option>
          </select>
          <small
            v-if="coLoiTruong('trainer_id')"
            id="assignment-create-trainer-error"
            role="alert"
          >
            {{ layLoiTruong('trainer_id') }}
          </small>
          <small v-if="trainerOptions.length === 0">Không có PT đủ điều kiện để tạo phân công.</small>
        </div>
        <div class="truong-bieu-mau">
          <label for="assignment-create-start">Bắt đầu</label>
          <input
            id="assignment-create-start"
            v-model="draftCreate.start_at"
            name="start_at"
            type="datetime-local"
            :disabled="dangMutation || modal.value !== ''"
            :aria-invalid="coLoiTruong('start_at')"
            :aria-describedby="coLoiTruong('start_at') ? 'assignment-create-start-error' : undefined"
          >
          <small
            v-if="coLoiTruong('start_at')"
            id="assignment-create-start-error"
            role="alert"
          >
            {{ layLoiTruong('start_at') }}
          </small>
        </div>
        <div class="truong-bieu-mau">
          <label for="assignment-create-end">Kết thúc (tùy chọn)</label>
          <input
            id="assignment-create-end"
            v-model="draftCreate.end_at"
            name="end_at"
            type="datetime-local"
            :disabled="dangMutation || modal.value !== ''"
            :aria-invalid="coLoiTruong('end_at')"
            :aria-describedby="coLoiTruong('end_at') ? 'assignment-create-end-error' : undefined"
          >
          <small
            v-if="coLoiTruong('end_at')"
            id="assignment-create-end-error"
            role="alert"
          >
            {{ layLoiTruong('end_at') }}
          </small>
        </div>
      </div>
      <button
        class="nut nut--chinh"
        type="button"
        :disabled="dangMutation"
        @click="taoMoi"
      >
        Tạo phân công
      </button>
      <p
        v-if="loiMutation && modal.value === ''"
        id="phan-cong-pt-loi-mutation"
        role="alert"
        tabindex="-1"
      >
        {{ thongBaoMutation }}
      </p>
    </section>

    <TrangThaiTaiDuLieu
      v-if="dangTai && !daTaiLanDau"
      nhan="Đang tải danh sách phân công…"
    />
    <TrangThaiLoi
      v-if="loiTaiDanhSach"
      :thong-bao="thongBaoDanhSach"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTai"
      @thu-lai="taiDanhSach"
    />
    <p
      v-if="loiMutation?.outcomeUnknown"
      id="phan-cong-pt-unknown"
      class="phan-cong-pt__unknown"
      role="alert"
      tabindex="-1"
    >
      Kết quả thao tác chưa xác định. Hãy kiểm tra lại chi tiết/lịch sử trước khi thực hiện thao tác mới; hệ thống không tự gửi lại.
    </p>

    <table
      v-if="daTaiLanDau && !loiTaiDanhSach"
      class="bang-du-lieu"
      aria-label="Lịch sử phân công PT"
    >
      <thead>
        <tr><th>Hội viên</th><th>PT</th><th>Bắt đầu</th><th>Kết thúc</th><th>Hiệu lực</th><th>Thao tác</th></tr>
      </thead>
      <tbody>
        <tr
          v-for="assignment in danhSach"
          :key="assignment.id"
        >
          <td>{{ assignment.member.name }} · {{ assignment.member.code }}</td>
          <td>{{ assignment.trainer.name }} · {{ assignment.trainer.code }}</td>
          <td>{{ assignment.start_at }}</td>
          <td>{{ assignment.end_at ?? '—' }}</td>
          <td>{{ assignment.is_current ? 'Đang hiệu lực' : 'Không hiệu lực' }}</td>
          <td>
            <button
              class="nut nut--phu"
              type="button"
              @click="xemChiTiet(assignment)"
            >
              Chi tiết
            </button>
            <button
              v-if="assignment.is_current"
              class="nut nut--nguy-hiem"
              type="button"
              @click="moModal('end', assignment)"
            >
              Kết thúc
            </button>
            <button
              v-if="assignment.is_current"
              class="nut nut--phu"
              type="button"
              @click="moModal('reassign', assignment)"
            >
              Đổi PT
            </button>
          </td>
        </tr>
        <tr v-if="danhSach.length === 0">
          <td colspan="6">
            Chưa có phân công phù hợp.
          </td>
        </tr>
      </tbody>
    </table>

    <nav
      v-if="daTaiLanDau && phanTrang.last_page > 1"
      aria-label="Phân trang phân công"
    >
      <button
        class="nut nut--phu"
        type="button"
        :disabled="phanTrang.current_page <= 1 || dangTai"
        @click="chuyenTrang(phanTrang.current_page - 1)"
      >
        Trang trước
      </button>
      Trang {{ phanTrang.current_page }} / {{ phanTrang.last_page }} · {{ phanTrang.total }} dòng
      <button
        class="nut nut--phu"
        type="button"
        :disabled="phanTrang.current_page >= phanTrang.last_page || dangTai"
        @click="chuyenTrang(phanTrang.current_page + 1)"
      >
        Trang sau
      </button>
    </nav>

    <section
      v-if="chiTiet"
      class="phan-cong-pt__chi-tiet"
      aria-live="polite"
    >
      <h2>Chi tiết phân công #{{ chiTiet.id }}</h2>
      <p>{{ chiTiet.member.name }} với {{ chiTiet.trainer.name }} · {{ chiTiet.is_current ? 'Đang hiệu lực' : 'Lịch sử' }}</p>
      <p>Lịch sử assignment và Chat exact assignment vẫn được giữ lại khi kết thúc/đổi PT.</p>
      <p v-if="dangTaiChiTiet">
        Đang đối soát chi tiết…
      </p>
    </section>

    <HopThoaiXacNhan
      :hien-thi="modal.value !== ''"
      :tieu-de="modal.value === 'create'
        ? 'Tạo phân công?'
        : modal.value === 'end' ? 'Kết thúc phân công?' : 'Đổi PT cho phân công?'"
      :mo-ta="modal.value === 'end'
        ? 'Assignment và Chat history vẫn được giữ. PT hiện tại mất scope gửi/đọc từ ranh giới server.'
        : modal.value === 'reassign'
          ? 'Assignment cũ và Chat history vẫn được giữ. PT mới không kế thừa Chat của assignment cũ.'
          : 'Backend sẽ tạo assignment trong phạm vi branch; hãy kiểm tra Member, PT và thời gian trước khi xác nhận.'"
      :mang-nguy-hiem="modal.value === 'end'"
      :dang-xu-ly="dangMutation"
      @xac-nhan="xacNhanMutation"
      @huy="dongModal"
    >
      <template #default>
        <div
          v-if="modal.value === 'create'"
          class="hop-thoai-xac-nhan__tom-tat"
        >
          <p>Hội viên: {{ layTenHoSo(memberOptions, 'member_profile', modal.createPayload?.member_id) }}</p>
          <p>PT: {{ layTenHoSo(trainerOptions, 'trainer_profile', modal.createPayload?.trainer_id) }}</p>
          <p>Bắt đầu: {{ layNhanThoiGian(modal.createPayload?.start_at) }}</p>
          <p>Kết thúc: {{ layNhanThoiGian(modal.createPayload?.end_at) }}</p>
        </div>

        <div
          v-else-if="modal.value === 'end'"
          class="phan-cong-pt__mutation-fields"
        >
          <p>Hội viên: {{ modal.assignment?.member?.name ?? 'Không xác định' }}</p>
          <p>PT hiện tại: {{ modal.assignment?.trainer?.name ?? 'Không xác định' }}</p>
          <p>Bắt đầu: {{ layNhanThoiGian(modal.assignment?.start_at) }}</p>
          <p>Kết thúc hiện tại: {{ layNhanThoiGian(modal.assignment?.end_at) }}</p>
          <label for="assignment-end-reason">Lý do (tùy chọn)</label>
          <textarea
            id="assignment-end-reason"
            v-model="draftEnd.reason"
            name="reason"
            maxlength="500"
            rows="3"
            :disabled="dangMutation"
            :aria-invalid="coLoiTruong('reason')"
            :aria-describedby="coLoiTruong('reason') ? 'assignment-end-reason-error' : undefined"
          />
          <small
            v-if="coLoiTruong('reason')"
            id="assignment-end-reason-error"
            role="alert"
          >
            {{ layLoiTruong('reason') }}
          </small>
        </div>

        <div
          v-else-if="modal.value === 'reassign'"
          class="phan-cong-pt__mutation-fields"
        >
          <p>Hội viên: {{ modal.assignment?.member?.name ?? 'Không xác định' }}</p>
          <p>PT hiện tại: {{ modal.assignment?.trainer?.name ?? 'Không xác định' }}</p>
          <p>Bắt đầu hiện tại: {{ layNhanThoiGian(modal.assignment?.start_at) }}</p>
          <p>Kết thúc hiện tại: {{ layNhanThoiGian(modal.assignment?.end_at) }}</p>
          <label for="assignment-reassign-trainer">PT mới</label>
          <select
            id="assignment-reassign-trainer"
            v-model="draftReassign.trainer_id"
            name="trainer_id"
            :disabled="dangMutation"
            :aria-invalid="coLoiTruong('trainer_id')"
            :aria-describedby="coLoiTruong('trainer_id') ? 'assignment-reassign-trainer-error' : undefined"
          >
            <option value="">
              Chọn PT mới
            </option>
            <option
              v-for="account in trainerOptions"
              :key="account.id"
              :value="account.trainer_profile.id"
            >
              {{ account.name }} · {{ account.trainer_profile.code }}
            </option>
          </select>
          <small
            v-if="coLoiTruong('trainer_id')"
            id="assignment-reassign-trainer-error"
            role="alert"
          >
            {{ layLoiTruong('trainer_id') }}
          </small>
          <label for="assignment-reassign-start">Bắt đầu mới (tùy chọn)</label>
          <input
            id="assignment-reassign-start"
            v-model="draftReassign.start_at"
            name="start_at"
            type="datetime-local"
            :disabled="dangMutation"
            :aria-invalid="coLoiTruong('start_at')"
            :aria-describedby="coLoiTruong('start_at') ? 'assignment-reassign-start-error' : undefined"
          >
          <small
            v-if="coLoiTruong('start_at')"
            id="assignment-reassign-start-error"
            role="alert"
          >
            {{ layLoiTruong('start_at') }}
          </small>
          <label for="assignment-reassign-reason">Lý do (tùy chọn)</label>
          <textarea
            id="assignment-reassign-reason"
            v-model="draftReassign.reason"
            name="reason"
            maxlength="500"
            rows="3"
            :disabled="dangMutation"
            :aria-invalid="coLoiTruong('reason')"
            :aria-describedby="coLoiTruong('reason') ? 'assignment-reassign-reason-error' : undefined"
          />
          <small
            v-if="coLoiTruong('reason')"
            id="assignment-reassign-reason-error"
            role="alert"
          >
            {{ layLoiTruong('reason') }}
          </small>
        </div>

        <p
          v-if="loiMutation"
          id="assignment-modal-loi-mutation"
          role="alert"
          tabindex="-1"
        >
          {{ thongBaoMutation }}
        </p>
      </template>
    </HopThoaiXacNhan>
  </section>
</template>
