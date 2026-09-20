<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute(); const router = useRouter(); const store = useDanhMucStore(); const { baiTap, giaoAnMau } = storeToRefs(store); const id = Number(route.params.id); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', goal: '', level: '', sessions_per_week: 1, description: '', status: 'HOAT_DONG', days: [] })
function nap(item) { if (item) Object.assign(form, { ...item, days: (item.days ?? []).map((day) => ({ ...day, exercises: (day.exercises ?? []).map((exercise) => ({ ...exercise })) })) }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietGiaoAnMau(id)) }
async function luu() { dangLuu.value = true; loi.value = null; await store.capNhatGiaoAnMau(id, { name: form.name, goal: form.goal, level: form.level, sessions_per_week: form.sessions_per_week, description: form.description, status: form.status }); loi.value = store.giaoAnMau.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
onMounted(() => { void Promise.all([tai(), store.taiDanhSachBaiTap({ status: 'HOAT_DONG' })]) })
</script>

<template>
  <section class="trang-danh-muc trang-danh-muc--form" aria-label="Chi tiet giao an mau" :aria-busy="giaoAnMau.dangTaiChiTiet || dangLuu"><TieuDeTrang tieu-de="Chi tiet giao an mau" mo-ta="PATCH chi sua metadata/status; noi dung days chi duoc thay bang phien ban moi." /><TrangThaiTaiDuLieu v-if="giaoAnMau.dangTaiChiTiet && !giaoAnMau.chiTiet" nhan="Dang tai chi tiet giao an mau..." /><TrangThaiLoi v-else-if="giaoAnMau.loiChiTiet" :thong-bao="giaoAnMau.loiChiTiet.message" :co-the-thu-lai="true" :dang-thu-lai="giaoAnMau.dangTaiChiTiet" @thu-lai="tai" /><form v-else @submit.prevent="luu"><div class="luoi-bieu-mau"><TruongBieuMau id="giao-an-detail-code" nhan="Ma giao an"><template #default="{ id }"><input :id="id" :value="form.code" readonly aria-readonly="true"></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-name" nhan="Ten giao an" bat-buoc :loi="layLoi('name')"><template #default="{ id }"><input :id="id" v-model="form.name" required></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-goal" nhan="Muc tieu" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.goal" required></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-level" nhan="Cap do" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.level" required></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-sessions" nhan="So buoi moi tuan" bat-buoc><template #default="{ id }"><input :id="id" v-model.number="form.sessions_per_week" type="number" min="1" required></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="form.status"><option value="HOAT_DONG">Hoat dong</option><option value="NGUNG_SU_DUNG">Ngung su dung</option></select></template></TruongBieuMau><TruongBieuMau id="giao-an-detail-description" nhan="Mo ta"><template #default="{ id }"><textarea :id="id" v-model="form.description" rows="3" maxlength="3000" /></template></TruongBieuMau></div><p class="trang-danh-muc__canh-bao" role="note">Days chi doc trong trang chi tiet. Muon thay doi noi dung, hay tao phien ban moi voi expected_content_version.</p><CayGiaoAn v-model="form.days" :exercise-options="baiTap.danhSach" disabled /><p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p><div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="router.push({ name: 'adminGiaoAnMau' })">Quay lai</button><button class="nut nut--phu" type="button" :disabled="dangLuu || form.status !== 'HOAT_DONG'" @click="router.push({ name: 'adminTaoPhienBanGiaoAnMau', params: { id } })">Tao phien ban</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Luu metadata' }}</button></div></form></section>
</template>
