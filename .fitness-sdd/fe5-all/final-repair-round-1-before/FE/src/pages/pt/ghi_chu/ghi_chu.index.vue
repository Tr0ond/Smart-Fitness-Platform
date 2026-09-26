<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/pt/thanh_dieu_huong_hoi_vien.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import VungThongBao from '../../../components/dung_chung/vung_thong_bao.vue'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const store = useHoiVienPtStore()
const {
  danhSachGhiChu,
  dangTaiGhiChu,
  loiGhiChu,
  dangThemGhiChu,
  loiThemGhiChu,
} = storeToRefs(store)
const banNhap = reactive({ noi_dung: '' })
const draftOwnerId = ref(null)

const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const memberIdDraft = computed(() => coIdHopLe.value ? String(memberId.value) : null)
const draftThuocHoiVien = computed(() => memberIdDraft.value !== null && draftOwnerId.value === memberIdDraft.value)
const coTheThuLai = computed(() => loiGhiChu.value?.isNetworkError === true
  || (Number.isInteger(loiGhiChu.value?.httpStatus) && loiGhiChu.value.httpStatus >= 500))
const coTheThuLaiThem = computed(() => loiThemGhiChu.value?.outcomeUnknown !== true
  && (loiThemGhiChu.value?.isNetworkError === true
    || (Number.isInteger(loiThemGhiChu.value?.httpStatus) && loiThemGhiChu.value.httpStatus >= 500)))

watch(memberIdDraft, (id) => {
  if (draftOwnerId.value === id) return
  banNhap.noi_dung = ''
  draftOwnerId.value = id
}, { flush: 'sync', immediate: true })

function dinhDangNgay(giaTri) {
  if (typeof giaTri !== 'string' || Number.isNaN(Date.parse(giaTri))) return 'Chưa cập nhật'
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(giaTri))
}

function layNoiDung(note) {
  return note?.content ?? note?.noi_dung ?? note?.body ?? 'Không có nội dung'
}

async function taiGhiChu() {
  const id = memberIdDraft.value
  if (id === null) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  store.chonHoiVien(id)
  await store.taiDanhSachGhiChu(id)
  if (memberIdDraft.value !== id) return
  if ([403, 404].includes(loiGhiChu.value?.httpStatus)) {
    await router.replace({ name: 'ptHoiVien' })
  }
}

async function themGhiChu() {
  const id = memberIdDraft.value
  const ownerId = draftOwnerId.value
  const noiDung = banNhap.noi_dung
  if (id === null || ownerId !== id || noiDung.trim() === '' || dangThemGhiChu.value) return
  const ketQua = await store.themGhiChuHuanLuyen(id, { content: noiDung })
  if (memberIdDraft.value !== id || draftOwnerId.value !== ownerId) return
  if ([loiThemGhiChu.value?.httpStatus, loiGhiChu.value?.httpStatus]
    .some((httpStatus) => [403, 404].includes(httpStatus))) {
    await router.replace({ name: 'ptHoiVien' })
    return
  }
  if (ketQua && !loiThemGhiChu.value) {
    if (banNhap.noi_dung === noiDung) banNhap.noi_dung = ''
  }
}

async function thuLaiThemGhiChu() {
  // Không blind retry POST: reconcile bằng GET danh sách và giữ nguyên draft.
  await taiGhiChu()
}

watch(() => route.params.id, () => {
  void taiGhiChu()
}, { flush: 'sync' })

onMounted(() => {
  void taiGhiChu()
})

onBeforeUnmount(() => {
  store.xoaHoiVienDangChon()
})
</script>

<template>
  <section
    class="pt-trang pt-trang-ghi-chu"
    aria-label="Ghi chú huấn luyện của hội viên"
    :aria-busy="dangTaiGhiChu || dangThemGhiChu"
  >
    <TieuDeTrang
      tieu-de="Ghi chú huấn luyện"
      mo-ta="Ghi chú chỉ được thêm mới trong phạm vi assignment hiện tại; không có sửa hoặc xóa."
    />
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <TrangThaiTaiDuLieu
      v-if="dangTaiGhiChu && danhSachGhiChu.length === 0"
      nhan="Đang tải ghi chú…"
    />
    <TrangThaiLoi
      v-if="loiGhiChu"
      :thong-bao="layThongBaoLoiApi(loiGhiChu, 'Không thể tải ghi chú huấn luyện.')"
      :co-the-thu-lai="coTheThuLai"
      :dang-thu-lai="dangTaiGhiChu"
      @thu-lai="taiGhiChu"
    />

    <section class="pt-card pt-ghi-chu-tao" aria-labelledby="pt-ghi-chu-tao-tieu-de">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">APPEND-ONLY NOTES</p>
          <h2 id="pt-ghi-chu-tao-tieu-de">Thêm ghi chú</h2>
        </div>
        <span class="pt-badge">Tối đa 100 bản ghi gần nhất</span>
      </div>
      <form class="pt-form" @submit.prevent="themGhiChu">
        <TruongBieuMau
          id="pt-ghi-chu-noi-dung"
          nhan="Nội dung ghi chú"
          tro-giup="Ghi chú sẽ được lưu nguyên văn trong audit trail."
        >
          <template #default="{ id, ariaDescribedby }">
            <textarea
              :id="id"
              v-model="banNhap.noi_dung"
              name="content"
              maxlength="2000"
              rows="5"
              required
              :aria-describedby="ariaDescribedby"
            />
          </template>
        </TruongBieuMau>
        <VungThongBao
          v-if="loiThemGhiChu"
          :danh-sach="[{ kieu: 'nguy_hiem', noiDung: layThongBaoLoiApi(loiThemGhiChu, 'Không thể thêm ghi chú.') }]"
        />
        <p v-if="loiThemGhiChu?.outcomeUnknown" class="pt-ghi-chu-tao__canh-bao" role="alert">
          Chưa xác định được kết quả lưu. Draft được giữ nguyên; hãy kiểm tra danh sách trước khi gửi lại.
        </p>
        <div class="pt-form__hanh-dong">
          <button
            class="nut nut--chinh"
            type="submit"
            :disabled="!draftThuocHoiVien || dangThemGhiChu || banNhap.noi_dung.trim() === ''"
          >
            {{ dangThemGhiChu ? 'Đang lưu…' : 'Thêm ghi chú' }}
          </button>
          <span class="pt-form__dem">{{ banNhap.noi_dung.length }}/2000</span>
        </div>
      </form>
    </section>

    <section class="pt-card" aria-labelledby="pt-ghi-chu-danh-sach">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">AUDIT TRAIL</p>
          <h2 id="pt-ghi-chu-danh-sach">Ghi chú gần đây</h2>
        </div>
        <p class="pt-card__phu-de">Mới nhất trước</p>
      </div>
      <TrangThaiTrong
        v-if="!dangTaiGhiChu && !loiGhiChu && danhSachGhiChu.length === 0"
        tieu-de="Chưa có ghi chú"
        mo-ta="Ghi chú đầu tiên sẽ xuất hiện sau khi được thêm thành công."
      />
      <ol v-else class="pt-danh-sach-ghi-chu">
        <li v-for="note in danhSachGhiChu" :key="note.id">
          <div class="pt-danh-sach-ghi-chu__dau">
            <time :datetime="note.created_at">{{ dinhDangNgay(note.created_at) }}</time>
            <span class="pt-badge">Append-only</span>
          </div>
          <p>{{ layNoiDung(note) }}</p>
          <small v-if="note.trainer?.name">Bởi {{ note.trainer.name }}</small>
        </li>
      </ol>
      <p v-if="coTheThuLaiThem" class="pt-ghi-chu-tao__goi-y">
        Nếu lần thêm ghi chú gặp lỗi mạng, hãy tải lại danh sách để đối chiếu trước khi gửi lại.
      </p>
      <button
        v-if="loiThemGhiChu?.outcomeUnknown"
        class="nut nut--phu"
        type="button"
        @click="thuLaiThemGhiChu"
      >
        Đối chiếu danh sách ghi chú
      </button>
    </section>
  </section>
</template>
