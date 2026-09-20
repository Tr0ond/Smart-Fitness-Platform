<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import BoChonQuanHeBaiTap from '../../../components/danh_muc/bo_chon_quan_he_bai_tap.vue'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute(); const router = useRouter(); const store = useDanhMucStore(); const { dungCu, nhomCo, baiTap } = storeToRefs(store)
const id = Number(route.params.id); const dangLuu = ref(false); const loi = ref(null)
const form = reactive({ code: '', name: '', difficulty: '', instructions: '', image_path: '', video_path: '', metadata: {}, status: 'HOAT_DONG', equipment_ids: [], muscle_groups: [] })
function nap(item) { if (!item) return; Object.assign(form, { ...item, equipment_ids: (item.equipment ?? []).map((value) => Number(value.id)), muscle_groups: (item.muscle_groups ?? []).map((value) => ({ id: Number(value.id), role: value.role })), metadata: { ...(item.metadata ?? {}) } }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietBaiTap(id)) }
async function luu() { dangLuu.value = true; loi.value = null; await store.capNhatBaiTap(id, { name: form.name, difficulty: form.difficulty, instructions: form.instructions, image_path: form.image_path, video_path: form.video_path, metadata: form.metadata, status: form.status, equipment_ids: form.equipment_ids, muscle_groups: form.muscle_groups }); loi.value = store.baiTap.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
onMounted(() => { void Promise.all([tai(), store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section class="trang-danh-muc trang-danh-muc--form" aria-label="Chi tiet bai tap" :aria-busy="baiTap.dangTaiChiTiet || dangLuu"><TieuDeTrang tieu-de="Chi tiet bai tap" mo-ta="Cap nhat metadata va quan he; ma bai tap la bat bien." /><TrangThaiTaiDuLieu v-if="baiTap.dangTaiChiTiet && !baiTap.chiTiet" nhan="Dang tai chi tiet bai tap..." /><TrangThaiLoi v-else-if="baiTap.loiChiTiet" :thong-bao="baiTap.loiChiTiet.message" :co-the-thu-lai="true" :dang-thu-lai="baiTap.dangTaiChiTiet" @thu-lai="tai" /><form v-else @submit.prevent="luu"><div class="luoi-bieu-mau"><TruongBieuMau id="bai-tap-detail-code" nhan="Ma bai tap"><template #default="{ id }"><input :id="id" :value="form.code" readonly aria-readonly="true"></template></TruongBieuMau><TruongBieuMau id="bai-tap-detail-name" nhan="Ten bai tap" bat-buoc :loi="layLoi('name')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.name" required :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid"></template></TruongBieuMau><TruongBieuMau id="bai-tap-detail-difficulty" nhan="Do kho" bat-buoc><template #default="{ id }"><input :id="id" v-model="form.difficulty" required></template></TruongBieuMau><TruongBieuMau id="bai-tap-detail-instructions" nhan="Huong dan"><template #default="{ id }"><textarea :id="id" v-model="form.instructions" rows="4" maxlength="5000" /></template></TruongBieuMau><TruongBieuMau id="bai-tap-detail-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="form.status"><option value="HOAT_DONG">Hoat dong</option><option value="NGUNG_SU_DUNG">Ngung su dung</option></select></template></TruongBieuMau></div><p class="trang-danh-muc__canh-bao" role="note">Quan he may dung cu tuan theo sematics AND. Trang thai exercise do Backend quyet dinh.</p><BoChonQuanHeBaiTap v-model="form" :dung-cu="dungCu.danhSach" :nhom-co="nhomCo.danhSach" :disabled="dangLuu" /><p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p><div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="router.push({ name: 'adminBaiTap' })">Quay lai</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Luu bai tap' }}</button></div></form></section>
</template>
