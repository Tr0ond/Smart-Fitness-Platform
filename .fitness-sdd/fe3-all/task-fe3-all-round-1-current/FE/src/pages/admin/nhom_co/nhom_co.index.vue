<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import HopThoaiXacNhan from '../../../components/dung_chung/hop_thoai_xac_nhan.vue'
import HuyHieuTrangThai from '../../../components/dung_chung/huy_hieu_trang_thai.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import TrangThaiTrong from '../../../components/dung_chung/trang_thai_trong.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const store = useDanhMucStore()
const { nhomCo } = storeToRefs(store)
const dangLuu = ref(false)
const dangDoiTrangThai = ref(false)
const itemCanDoiTrangThai = ref(null)
const loi = ref(null)
const dangChinhSua = ref(false)
const form = reactive({ id: null, code: '', name: '', description: '', status: 'HOAT_DONG' })
function datLaiForm() { Object.assign(form, { id: null, code: '', name: '', description: '', status: 'HOAT_DONG' }); dangChinhSua.value = false; loi.value = null }
function chonItem(item) { Object.assign(form, { id: item.id, code: item.code, name: item.name, description: item.description ?? '', status: item.status }); dangChinhSua.value = true; loi.value = null }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function luu() {
  dangLuu.value = true; loi.value = null
  const payload = dangChinhSua.value ? { name: form.name, description: form.description, status: form.status } : { code: form.code, name: form.name, description: form.description, status: form.status }
  const result = dangChinhSua.value ? await store.capNhatNhomCo(form.id, payload) : await store.taoNhomCo(payload)
  loi.value = store.nhomCo.loiMutation
  dangLuu.value = false
  if (result) { datLaiForm(); await store.taiDanhSachNhomCo() }
}
function moDoiTrangThai(item) { itemCanDoiTrangThai.value = item }
function dongDoiTrangThai() { if (!dangDoiTrangThai.value) itemCanDoiTrangThai.value = null }
async function xacNhanDoiTrangThai() {
  const item = itemCanDoiTrangThai.value
  if (!item || dangDoiTrangThai.value) return
  dangDoiTrangThai.value = true
  await store.capNhatNhomCo(item.id, { status: item.status === 'HOAT_DONG' ? 'NGUNG_SU_DUNG' : 'HOAT_DONG' })
  await store.taiDanhSachNhomCo()
  dangDoiTrangThai.value = false
  itemCanDoiTrangThai.value = null
}
onMounted(() => { void store.taiDanhSachNhomCo() })
</script>

<template>
  <section class="trang-danh-muc" aria-label="Danh sach nhom co" :aria-busy="nhomCo.dangTai || dangLuu">
    <TieuDeTrang tieu-de="Nhom co" mo-ta="Hien ca nhom co ngung su dung de bao toan quan he bai tap hien huu.">
      <template #hanhDong><button class="nut nut--phu" type="button" @click="datLaiForm">Them nhom co</button></template>
    </TieuDeTrang>
    <div class="trang-danh-muc__khung-hai-cot">
      <form class="trang-danh-muc__form" @submit.prevent="luu">
        <h2>{{ dangChinhSua ? 'Sua nhom co' : 'Them nhom co' }}</h2>
        <TruongBieuMau id="nhom-co-code" nhan="Ma nhom co" :bat-buoc="!dangChinhSua" :loi="layLoi('code')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.code" :readonly="dangChinhSua" :aria-readonly="dangChinhSua" :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid" required></template></TruongBieuMau>
        <TruongBieuMau id="nhom-co-name" nhan="Ten nhom co" bat-buoc :loi="layLoi('name')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.name" required :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid"></template></TruongBieuMau>
        <TruongBieuMau id="nhom-co-description" nhan="Mo ta"><template #default="{ id }"><textarea :id="id" v-model="form.description" rows="3" maxlength="2000" /></template></TruongBieuMau>
        <TruongBieuMau id="nhom-co-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="form.status"><option value="HOAT_DONG">Hoat dong</option><option value="NGUNG_SU_DUNG">Ngung su dung</option></select></template></TruongBieuMau>
        <p class="trang-danh-muc__canh-bao" role="note">Khi ngung su dung, quan he hien huu phai duoc giu va Backend se tu choi quan he moi neu khong hop le.</p>
        <p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p>
        <div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="datLaiForm">Huy</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Luu nhom co' }}</button></div>
      </form>
      <div>
        <TrangThaiTaiDuLieu v-if="nhomCo.dangTai && !nhomCo.daTaiLanDau" nhan="Dang tai nhom co..." />
        <TrangThaiLoi v-else-if="nhomCo.loi" :thong-bao="nhomCo.loi.message" :co-the-thu-lai="true" :dang-thu-lai="nhomCo.dangTai" @thu-lai="store.taiDanhSachNhomCo" />
        <TrangThaiTrong v-else-if="nhomCo.daTaiLanDau && nhomCo.danhSach.length === 0" tieu-de="Chua co nhom co" />
        <table v-else class="bang-du-lieu" aria-label="Danh sach nhom co"><caption>Danh sach nhom co</caption><thead><tr><th scope="col">Ma</th><th scope="col">Ten</th><th scope="col">Trang thai</th><th scope="col">Thao tac</th></tr></thead><tbody><tr v-for="item in nhomCo.danhSach" :key="item.id"><td>{{ item.code }}</td><td>{{ item.name }}</td><td><HuyHieuTrangThai :trang-thai="item.status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao'" :nhan="item.status === 'HOAT_DONG' ? 'Hoat dong' : 'Ngung su dung'" /></td><td><button class="nut nut--phu" type="button" @click="chonItem(item)">Sua</button><button class="nut nut--nguy-hiem" type="button" @click="moDoiTrangThai(item)">{{ item.status === 'HOAT_DONG' ? 'Ngung su dung' : 'Mo lai' }}</button></td></tr></tbody></table>
      </div>
    </div>
    <HopThoaiXacNhan :hien-thi="Boolean(itemCanDoiTrangThai)" tieu-de="Cap nhat trang thai nhom co?" mo-ta="Ban ghi khong bi xoa; cac quan he bai tap hien huu van duoc bao toan." :dang-xu-ly="dangDoiTrangThai" mang-nguy-hiem @xac-nhan="xacNhanDoiTrangThai" @huy="dongDoiTrangThai" />
  </section>
</template>
