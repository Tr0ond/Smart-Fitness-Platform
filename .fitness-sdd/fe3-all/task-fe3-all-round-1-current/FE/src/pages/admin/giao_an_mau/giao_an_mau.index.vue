<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter(); const store = useDanhMucStore(); const { giaoAnMau } = storeToRefs(store)
const dangDoiTrangThai = ref(false); const itemCanDoiTrangThai = ref(null)
function layNhan(status) { return status === 'HOAT_DONG' ? 'Hoat dong' : 'Ngung su dung' }
function moChiTiet(item) { if (item?.id) void router.push({ name: 'adminChiTietGiaoAnMau', params: { id: item.id } }) }
function moDoiTrangThai(item) { itemCanDoiTrangThai.value = item }
function dongDoiTrangThai() { if (!dangDoiTrangThai.value) itemCanDoiTrangThai.value = null }
async function xacNhanDoiTrangThai() { const item = itemCanDoiTrangThai.value; if (!item || dangDoiTrangThai.value) return; dangDoiTrangThai.value = true; await store.capNhatGiaoAnMau(item.id, { status: item.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG' }); await store.taiDanhSachGiaoAnMau(); dangDoiTrangThai.value = false; itemCanDoiTrangThai.value = null }
onMounted(() => { void store.taiDanhSachGiaoAnMau() })
</script>

<template>
  <section class="trang-danh-muc" aria-label="Danh sach giao an mau" :aria-busy="giaoAnMau.dangTai"><TieuDeTrang tieu-de="Giao an mau" mo-ta="Quan ly template va phien ban copy-on-write tu Backend."><template #hanhDong><button class="nut nut--chinh" type="button" @click="router.push({ name: 'adminTaoGiaoAnMau' })">Tao giao an mau</button></template></TieuDeTrang><TrangThaiTaiDuLieu v-if="giaoAnMau.dangTai && !giaoAnMau.daTaiLanDau" nhan="Dang tai giao an mau..." /><TrangThaiLoi v-else-if="giaoAnMau.loi" :thong-bao="giaoAnMau.loi.message" :co-the-thu-lai="true" :dang-thu-lai="giaoAnMau.dangTai" @thu-lai="store.taiDanhSachGiaoAnMau" /><TrangThaiTrong v-else-if="giaoAnMau.daTaiLanDau && giaoAnMau.danhSach.length === 0" tieu-de="Chua co giao an mau" /><BangDuLieu v-else :cot="[]" :hang="giaoAnMau.danhSach" tieu-de="Danh sach giao an mau"><template #tieuDeCot><tr><th scope="col">Ma</th><th scope="col">Ten</th><th scope="col">Muc tieu</th><th scope="col">Cap do</th><th scope="col">So buoi</th><th scope="col">Trang thai</th><th scope="col">Thao tac</th></tr></template><template #hang="{ hang }"><tr v-for="item in hang" :key="item.id"><td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.goal }}</td><td>{{ item.level }}</td><td>{{ item.sessions_per_week }} ({{ item.day_count ?? 0 }} ngay)</td><td><HuyHieuTrangThai :trang-thai="item.status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao'" :nhan="layNhan(item.status)" /></td><td><button class="nut nut--lien-ket" type="button" @click="moChiTiet(item)">Chi tiet</button><button class="nut nut--phu" type="button" @click="moDoiTrangThai(item)">{{ item.status === 'HOAT_DONG' ? 'Ngung su dung' : 'Mo lai' }}</button></td></tr></template></BangDuLieu><div v-if="itemCanDoiTrangThai" class="hop-thoai-xac-nhan" role="dialog" aria-modal="true"><section class="hop-thoai-xac-nhan__hop"><h2>Cap nhat trang thai giao an?</h2><p>Ban ghi van duoc giu de bao toan lich su phien ban.</p><button class="nut nut--phu" type="button" :disabled="dangDoiTrangThai" @click="dongDoiTrangThai">Huy</button><button class="nut nut--nguy-hiem" type="button" :disabled="dangDoiTrangThai" @click="xacNhanDoiTrangThai">Xac nhan</button></section></div></section>
</template>
