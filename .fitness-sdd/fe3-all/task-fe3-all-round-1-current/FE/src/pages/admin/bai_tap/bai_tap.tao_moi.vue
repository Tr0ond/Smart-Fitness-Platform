<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BoChonQuanHeBaiTap from '../../../components/danh_muc/bo_chon_quan_he_bai_tap.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const router = useRouter()
const store = useDanhMucStore()
const { dungCu, nhomCo } = storeToRefs(store)
const dangLuu = ref(false)
const loi = ref(null)
const form = reactive({ code: '', name: '', difficulty: '', instructions: '', image_path: '', video_path: '', metadata: {}, status: 'HOAT_DONG', equipment_ids: [], muscle_groups: [] })
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function luu() {
  loi.value = null; dangLuu.value = true
  const result = await store.taoBaiTap({ ...form, equipment_ids: [...form.equipment_ids], muscle_groups: form.muscle_groups.map((item) => ({ ...item })) })
  loi.value = store.baiTap.loiMutation; dangLuu.value = false
  if (result?.id) await router.push({ name: 'adminChiTietBaiTap', params: { id: result.id } })
}
onMounted(() => { void Promise.all([store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section class="trang-danh-muc trang-danh-muc--form" aria-label="Tao bai tap"><TieuDeTrang tieu-de="Tao moi bai tap" mo-ta="Khai bao quan he bat buoc theo contract Exercise catalog." /><form @submit.prevent="luu"><div class="luoi-bieu-mau"><TruongBieuMau id="bai-tap-create-code" nhan="Ma bai tap" bat-buoc :loi="layLoi('code')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.code" required :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid"></template></TruongBieuMau><TruongBieuMau id="bai-tap-create-name" nhan="Ten bai tap" bat-buoc :loi="layLoi('name')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.name" required :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid"></template></TruongBieuMau><TruongBieuMau id="bai-tap-create-difficulty" nhan="Do kho" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.difficulty" required></template></TruongBieuMau><TruongBieuMau id="bai-tap-create-instructions" nhan="Huong dan"><template #default="{ id }"><textarea :id="id" v-model="form.instructions" rows="4" maxlength="5000" /></template></TruongBieuMau><TruongBieuMau id="bai-tap-create-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="form.status"><option value="HOAT_DONG">Hoat dong</option><option value="NGUNG_SU_DUNG">Ngung su dung</option></select></template></TruongBieuMau></div><BoChonQuanHeBaiTap v-model="form" :dung-cu="dungCu.danhSach" :nhom-co="nhomCo.danhSach" :disabled="dangLuu" /><p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p><div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="router.push({ name: 'adminBaiTap' })">Huy</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Tao bai tap' }}</button></div></form></section>
</template>
