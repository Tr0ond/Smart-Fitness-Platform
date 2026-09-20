<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import CayGiaoAn from '../../../components/danh_muc/cay_giao_an.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter(); const store = useDanhMucStore(); const { baiTap } = storeToRefs(store); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', goal: '', level: '', sessions_per_week: 1, description: '', status: 'HOAT_DONG', days: [{ order: 1, name: 'Ngay 1', estimated_minutes: 45, exercises: [] }] })
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function luu() { dangLuu.value = true; loi.value = null; const result = await store.taoGiaoAnMau({ ...form, days: form.days.map((day) => ({ ...day, exercises: day.exercises.map((item) => ({ ...item })) })) }); loi.value = store.giaoAnMau.loiMutation; dangLuu.value = false; if (result?.id) await router.push({ name: 'adminChiTietGiaoAnMau', params: { id: result.id } }) }
onMounted(() => { void store.taiDanhSachBaiTap({ status: 'HOAT_DONG' }) })
</script>

<template>
  <section class="trang-danh-muc trang-danh-muc--form" aria-label="Tao giao an mau"><TieuDeTrang tieu-de="Tao moi giao an mau" mo-ta="Tao noi dung day du; Backend kiem tra so buoi, thu tu va exercise dang hoat dong." /><form @submit.prevent="luu"><div class="luoi-bieu-mau"><TruongBieuMau id="giao-an-create-code" nhan="Ma giao an" bat-buoc :loi="layLoi('code')"><template #default="{ id }"><input :id="id" v-model="form.code" required></template></TruongBieuMau><TruongBieuMau id="giao-an-create-name" nhan="Ten giao an" bat-buoc :loi="layLoi('name')"><template #default="{ id }"><input :id="id" v-model="form.name" required></template></TruongBieuMau><TruongBieuMau id="giao-an-create-goal" nhan="Muc tieu" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.goal" required></template></TruongBieuMau><TruongBieuMau id="giao-an-create-level" nhan="Cap do" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.level" required></template></TruongBieuMau><TruongBieuMau id="giao-an-create-sessions" nhan="So buoi moi tuan" bat-buoc :loi="layLoi('sessions_per_week')"><template #default="{ id }"><input :id="id" v-model.number="form.sessions_per_week" type="number" min="1" required></template></TruongBieuMau><TruongBieuMau id="giao-an-create-description" nhan="Mo ta"><template #default="{ id }"><textarea :id="id" v-model="form.description" rows="3" maxlength="3000" /></template></TruongBieuMau></div><CayGiaoAn v-model="form.days" :exercise-options="baiTap.danhSach" :disabled="dangLuu" /><p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p><div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="router.push({ name: 'adminGiaoAnMau' })">Huy</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Tao giao an mau' }}</button></div></form></section>
</template>
