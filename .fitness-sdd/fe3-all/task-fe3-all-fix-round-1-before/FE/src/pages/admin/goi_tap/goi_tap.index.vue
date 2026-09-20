<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { goiTap } = storeToRefs(store)
const goiTapCanDoiTrangThai = ref(null)
const dangDoiTrangThai = ref(false)

const nhanTrangThai = computed(() => ({ DANG_BAN: 'Dang ban', NGUNG_BAN: 'Ngung ban' }))
const kieuTrangThai = (status) => status === 'DANG_BAN' ? 'thanh_cong' : 'canh_bao'

function layNhan(status) { return nhanTrangThai.value[status] ?? status ?? 'Khong xac dinh' }
function moTrangTao() { void router.push({ name: 'adminTaoGoiTap' }) }
function moChiTiet(item) {
  if (item?.id) void router.push({ name: 'adminChiTietGoiTap', params: { id: item.id } })
}
function moDoiTrangThai(item) { goiTapCanDoiTrangThai.value = item }
function dongDoiTrangThai() { if (!dangDoiTrangThai.value) goiTapCanDoiTrangThai.value = null }
async function xacNhanDoiTrangThai() {
  const item = goiTapCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.capNhatGoiTap(item.id, { status: item.status === 'DANG_BAN' ? 'NGUNG_BAN' : 'DANG_BAN' })
  await store.taiDanhSachGoiTap()
  dangDoiTrangThai.value = false
  goiTapCanDoiTrangThai.value = null
}
onMounted(() => { void store.taiDanhSachGoiTap() })
</script>

<template>
  <section class="trang-danh-muc" aria-label="Danh sach goi tap" :aria-busy="goiTap.dangTai">
    <TieuDeTrang tieu-de="Goi tap" mo-ta="Quan ly goi tap va quyen loi authoritative tu Backend.">
      <template #hanhDong><button class="nut nut--chinh" type="button" @click="moTrangTao">Tao goi tap</button></template>
    </TieuDeTrang>
    <TrangThaiTaiDuLieu v-if="goiTap.dangTai && !goiTap.daTaiLanDau" nhan="Dang tai goi tap..." />
    <TrangThaiLoi v-else-if="goiTap.loi" :thong-bao="goiTap.loi.message" :co-the-thu-lai="true" :dang-thu-lai="goiTap.dangTai" @thu-lai="store.taiDanhSachGoiTap" />
    <TrangThaiTrong v-else-if="goiTap.daTaiLanDau && goiTap.danhSach.length === 0" tieu-de="Chua co goi tap" mo-ta="Tao goi tap dau tien de cap quyen cho hoi vien." />
    <BangDuLieu v-else :cot="[]" :hang="goiTap.danhSach" tieu-de="Danh sach goi tap" thong-bao-trong="Chua co goi tap.">
      <template #tieuDeCot><tr><th scope="col">Ma</th><th scope="col">Ten</th><th scope="col">Gia</th><th scope="col">Thoi han</th><th scope="col">Trang thai</th><th scope="col">Thao tac</th></tr></template>
      <template #hang="{ hang }"><tr v-for="item in hang" :key="item.id"><td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.price }} {{ item.currency ?? '' }}</td><td>{{ item.duration_days }} ngay</td><td><HuyHieuTrangThai :trang-thai="kieuTrangThai(item.status)" :nhan="layNhan(item.status)" /></td><td><button class="nut nut--lien-ket" type="button" @click="moChiTiet(item)">Chi tiet</button><button class="nut nut--phu" type="button" @click="moDoiTrangThai(item)">{{ item.status === 'DANG_BAN' ? 'Ngung ban' : 'Mo ban' }}</button></td></tr></template>
    </BangDuLieu>
    <HopThoaiXacNhan
      :hien-thi="Boolean(goiTapCanDoiTrangThai)"
      :tieu-de="goiTapCanDoiTrangThai?.status === 'DANG_BAN' ? 'Ngung ban goi tap?' : 'Mo ban goi tap?'"
      mo-ta="Backend se giu snapshot quyen loi cua cac ky da mua."
      nhan-xac-nhan="Cap nhat trang thai"
      :dang-xu-ly="dangDoiTrangThai"
      mang-nguy-hiem
      @xac-nhan="xacNhanDoiTrangThai"
      @huy="dongDoiTrangThai"
    >
      <p v-if="goiTap.loiMutation" role="alert">{{ goiTap.loiMutation.message }}</p>
    </HopThoaiXacNhan>
  </section>
</template>
