<script setup>
import { computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import ThanhDieuHuongHoiVien from '../../../components/PT/thanh_dieu_huong_hoi_vien.vue'
import KhungXemDeXuat from '../../../components/PT/khung_xem_de_xuat.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useDeXuatStore } from '../../../stores/de_xuat.store.js'
import { useHoiVienPtStore } from '../../../stores/hoi_vien_pt.store.js'
import { layThongBaoLoiApi } from '../../../utils/thong_bao_loi.js'

const route = useRoute()
const router = useRouter()
const deXuatStore = useDeXuatStore()
const hoiVienStore = useHoiVienPtStore()
const { danhSachDeXuat, dangTaiDanhSachDeXuat, loiTaiDanhSachDeXuat } = storeToRefs(deXuatStore)
const { chiTietHoiVien, loiChiTietHoiVien, dangTaiChiTietHoiVien } = storeToRefs(hoiVienStore)
const memberId = computed(() => typeof route.params.id === 'string' ? route.params.id.trim() : route.params.id)
const coIdHopLe = computed(() => /^[1-9]\d*$/.test(String(memberId.value ?? '')))
const coAssignmentHienTai = computed(() => Boolean(chiTietHoiVien.value?.assignment?.id)
  && chiTietHoiVien.value.assignment.is_current !== false)
const deXuatDangXem = computed(() => {
  const id = route.query.xem
  return danhSachDeXuat.value.find((proposal) => String(proposal.id) === String(id)) ?? null
})
const coTheThuLai = computed(() => loiTaiDanhSachDeXuat.value?.isNetworkError === true
  || (Number.isInteger(loiTaiDanhSachDeXuat.value?.httpStatus) && loiTaiDanhSachDeXuat.value.httpStatus >= 500))
const coTheThuLaiProfile = computed(() => loiChiTietHoiVien.value?.isNetworkError === true
  || (Number.isInteger(loiChiTietHoiVien.value?.httpStatus) && loiChiTietHoiVien.value.httpStatus >= 500))
const NHAN_TRANG_THAI = Object.freeze({
  CHO_XAC_NHAN: 'Chờ hội viên xác nhận',
  DA_TU_CHOI: 'Hội viên đã từ chối',
  HET_HAN: 'Đề xuất đã hết hạn',
  XUNG_DOT: 'Đề xuất có xung đột',
  DA_AP_DUNG: 'Hội viên đã áp dụng',
})

/** Kiem tra assignment bang profile API roi tai danh sach proposal scoped Member. */
async function taiDanhSachDeXuat() {
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
  const danhSach = await deXuatStore.taiDanhSachDeXuat(id)
  if (danhSach?.scopeLost) await router.replace({ name: 'ptHoiVien' })
}

async function dongXemTruoc() {
  await router.replace({ name: 'ptDeXuatKeHoach', params: { id: memberId.value } })
}

watch(() => route.params.id, () => { void taiDanhSachDeXuat() }, { flush: 'sync', immediate: true })

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
    class="pt-trang pt-trang-de-xuat"
    aria-label="Đề xuất kế hoạch tập"
    :aria-busy="dangTaiDanhSachDeXuat"
  >
    <TieuDeTrang
      tieu-de="Đề xuất kế hoạch tập"
      mo-ta="Đề xuất của PT chờ hội viên xem và quyết định; kế hoạch chính thức chỉ đổi sau khi hội viên xác nhận."
    >
      <template #hanhDong>
        <RouterLink
          v-if="coAssignmentHienTai"
          class="nut nut--chinh"
          :to="{ name: 'ptTaoDeXuatKeHoach', params: { id: memberId } }"
        >
          Tạo đề xuất
        </RouterLink>
      </template>
    </TieuDeTrang>
    <ThanhDieuHuongHoiVien v-if="coIdHopLe" :member-id="memberId" />

    <section class="pt-card" aria-labelledby="pt-de-xuat-danh-sach-tieu-de">
      <div class="pt-card__dau">
        <div>
          <p class="pt-kicker">PROPOSAL HISTORY</p>
          <h2 id="pt-de-xuat-danh-sach-tieu-de">Đề xuất gần đây</h2>
        </div>
        <span class="pt-badge">Tối đa 100 đề xuất gần nhất</span>
      </div>
      <p>
        Chỉ đề xuất trong assignment hiện tại được trả về. Khi danh sách đạt giới hạn 100 bản ghi, dữ liệu cũ hơn có thể không xuất hiện.
      </p>
      <TrangThaiTaiDuLieu v-if="dangTaiChiTietHoiVien" nhan="Đang xác minh assignment hiện tại…" />
      <TrangThaiLoi
        v-if="loiChiTietHoiVien"
        :thong-bao="layThongBaoLoiApi(loiChiTietHoiVien, 'Không thể xác minh assignment hiện tại.')"
        :co-the-thu-lai="coTheThuLaiProfile"
        :dang-thu-lai="dangTaiChiTietHoiVien"
        @thu-lai="taiDanhSachDeXuat"
      />
      <TrangThaiTaiDuLieu v-if="dangTaiDanhSachDeXuat && danhSachDeXuat.length === 0" nhan="Đang tải đề xuất…" />
      <TrangThaiLoi
        v-if="loiTaiDanhSachDeXuat"
        :thong-bao="layThongBaoLoiApi(loiTaiDanhSachDeXuat, 'Không thể tải danh sách đề xuất.')"
        :co-the-thu-lai="coTheThuLai"
        :dang-thu-lai="dangTaiDanhSachDeXuat"
        @thu-lai="taiDanhSachDeXuat"
      />
      <TrangThaiTrong
        v-if="!dangTaiDanhSachDeXuat && !loiTaiDanhSachDeXuat && danhSachDeXuat.length === 0"
        tieu-de="Chưa có đề xuất trong phạm vi hiện tại"
        mo-ta="Bạn có thể tạo một đề xuất để hội viên xem xét trên ứng dụng di động."
      />
      <ol v-else class="pt-danh-sach">
        <li v-for="proposal in danhSachDeXuat" :key="proposal.id" class="pt-card pt-de-xuat__muc">
          <div class="pt-card__dau">
            <div>
              <p class="pt-kicker">{{ proposal.change_type }}</p>
              <h3>{{ proposal.title }}</h3>
            </div>
            <span class="pt-badge">{{ NHAN_TRANG_THAI[proposal.status] ?? proposal.status }}</span>
          </div>
          <p>{{ proposal.explanation }}</p>
          <p class="pt-card__phu-de">
            Áp dụng từ {{ proposal.effective_from ?? 'chưa đặt ngày' }} · Hết hạn {{ proposal.expires_at ?? 'chưa có dữ liệu' }}
          </p>
          <button
            class="nut nut--phu"
            type="button"
            :aria-label="`Xem trước ${proposal.title}`"
            @click="router.push({ name: 'ptDeXuatKeHoach', params: { id: memberId }, query: { xem: proposal.id } })"
          >
            Xem trước
          </button>
        </li>
      </ol>
    </section>
    <KhungXemDeXuat :proposal="deXuatDangXem" :mo="Boolean(deXuatDangXem)" @dong="dongXemTruoc" />
  </section>
</template>
