<script setup>
import { computed, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BoLocDanhSach from '../../../components/dung_chung/bo_loc_danh_sach.vue'
import BangDuLieu from '../../../components/dung_chung/bang_du_lieu.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { baiTap, dungCu, nhomCo } = storeToRefs(store)
const boLoc = reactive({ search: '', status: '', difficulty: '', equipment_id: '', muscle_group_id: '' })
const danhSachHienThi = computed(() => baiTap.value.danhSach.filter((item) => {
  const dungDifficulty = boLoc.difficulty === '' || item.difficulty === boLoc.difficulty
  const dungEquipment = boLoc.equipment_id === '' || item.equipment?.some((equipment) => Number(equipment.id) === Number(boLoc.equipment_id))
  const dungMuscle = boLoc.muscle_group_id === '' || item.muscle_groups?.some((group) => Number(group.id) === Number(boLoc.muscle_group_id))
  return dungDifficulty && dungEquipment && dungMuscle
}))
function layNhan(status) { return status === 'HOAT_DONG' ? 'Hoat dong' : 'Ngung su dung' }
function kieu(status) { return status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao' }
async function apDung() { await store.taiDanhSachBaiTap({ search: boLoc.search, status: boLoc.status }) }
function moChiTiet(item) { if (item?.id) void router.push({ name: 'adminChiTietBaiTap', params: { id: item.id } }) }
onMounted(() => { void Promise.all([store.taiDanhSachBaiTap(), store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section class="trang-danh-muc" aria-label="Danh sach bai tap" :aria-busy="baiTap.dangTai">
    <TieuDeTrang tieu-de="Bai tap" mo-ta="Tra cuu bai tap va quan he dung cu/nhom co theo Backend."><template #hanhDong><button class="nut nut--chinh" type="button" @click="router.push({ name: 'adminTaoBaiTap' })">Tao bai tap</button></template></TieuDeTrang>
    <BoLocDanhSach :dang-xu-ly="baiTap.dangTai" @ap-dung="apDung" @dat-lai="boLoc.search = ''; boLoc.status = ''; boLoc.difficulty = ''; boLoc.equipment_id = ''; boLoc.muscle_group_id = ''; apDung()"><TruongBieuMau id="bai-tap-search" nhan="Tim kiem"><template #default="{ id }"><input :id="id" v-model="boLoc.search" type="search" maxlength="150" placeholder="Ma hoac ten bai tap"></template></TruongBieuMau><TruongBieuMau id="bai-tap-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="boLoc.status"><option value="">Tat ca</option><option value="HOAT_DONG">Hoat dong</option><option value="NGUNG_SU_DUNG">Ngung su dung</option></select></template></TruongBieuMau><TruongBieuMau id="bai-tap-difficulty" nhan="Do kho (loc tai cho)"><template #default="{ id }"><input :id="id" v-model="boLoc.difficulty" placeholder="Vi du: Beginner"></template></TruongBieuMau><TruongBieuMau id="bai-tap-equipment-filter" nhan="Dung cu"><template #default="{ id }"><select :id="id" v-model="boLoc.equipment_id"><option value="">Tat ca dung cu</option><option v-for="item in dungCu.danhSach" :key="item.id" :value="item.id">{{ item.name }}</option></select></template></TruongBieuMau><TruongBieuMau id="bai-tap-muscle-filter" nhan="Nhom co"><template #default="{ id }"><select :id="id" v-model="boLoc.muscle_group_id"><option value="">Tat ca nhom co</option><option v-for="item in nhomCo.danhSach" :key="item.id" :value="item.id">{{ item.name }}</option></select></template></TruongBieuMau></BoLocDanhSach>
    <TrangThaiTaiDuLieu v-if="baiTap.dangTai && !baiTap.daTaiLanDau" nhan="Dang tai bai tap..." />
    <TrangThaiLoi v-else-if="baiTap.loi" :thong-bao="baiTap.loi.message" :co-the-thu-lai="true" :dang-thu-lai="baiTap.dangTai" @thu-lai="store.taiDanhSachBaiTap" />
    <TrangThaiTrong v-else-if="baiTap.daTaiLanDau && baiTap.danhSach.length === 0" tieu-de="Chua co bai tap" />
    <TrangThaiTrong v-else-if="baiTap.daTaiLanDau && danhSachHienThi.length === 0" tieu-de="Khong co ket qua phu hop" />
    <BangDuLieu v-else :cot="[]" :hang="danhSachHienThi" tieu-de="Danh sach bai tap"><template #tieuDeCot><tr><th scope="col">Ma</th><th scope="col">Ten</th><th scope="col">Do kho</th><th scope="col">Quan he</th><th scope="col">Trang thai</th><th scope="col">Thao tac</th></tr></template><template #hang="{ hang }"><tr v-for="item in hang" :key="item.id"><td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.difficulty }}</td><td>{{ item.equipment_semantics ?? 'AND' }} · {{ item.equipment?.length ?? 0 }} dung cu · {{ item.muscle_groups?.length ?? 0 }} nhom co</td><td><HuyHieuTrangThai :trang-thai="kieu(item.status)" :nhan="layNhan(item.status)" /></td><td><button class="nut nut--lien-ket" type="button" @click="moChiTiet(item)">Chi tiet</button></td></tr></template></BangDuLieu>
  </section>
</template>
