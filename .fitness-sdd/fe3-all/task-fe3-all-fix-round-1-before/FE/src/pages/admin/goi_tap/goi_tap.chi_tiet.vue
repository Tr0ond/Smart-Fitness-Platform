<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TieuDeTrang from '../../../components/dung_chung/tieu_de_trang.vue'
import TruongBieuMau from '../../../components/dung_chung/truong_bieu_mau.vue'
import TrangThaiLoi from '../../../components/dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../../components/dung_chung/trang_thai_tai_du_lieu.vue'
import BoSuaQuyenLoiGoiTap from '../../../components/danh_muc/bo_sua_quyen_loi_goi_tap.vue'
import { useDanhMucStore } from '../../../stores/danh_muc.store.js'

const route = useRoute()
const router = useRouter()
const store = useDanhMucStore()
const form = reactive({ code: '', name: '', price: 0, duration_days: 1, description: '', status: 'NGUNG_BAN', benefits: {} })
const dangLuu = ref(false)
const dangLuuQuyenLoi = ref(false)
const loi = ref(null)
const id = Number(route.params.id)
function nap(duLieu) { if (duLieu) Object.assign(form, { ...duLieu, benefits: { ...(duLieu.benefits ?? {}) } }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietGoiTap(id)) }
async function luu() { dangLuu.value = true; loi.value = null; await store.capNhatGoiTap(id, { name: form.name, price: form.price, duration_days: form.duration_days, description: form.description, status: form.status }); loi.value = store.goiTap.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
async function luuQuyenLoi(value) { dangLuuQuyenLoi.value = true; await store.thayTheQuyenLoiGoiTap(id, value); loi.value = store.goiTap.loiMutation; dangLuuQuyenLoi.value = false; if (!loi.value) await tai() }
onMounted(tai)
</script>

<template>
  <section class="trang-danh-muc trang-danh-muc--form" aria-label="Chi tiet goi tap" :aria-busy="store.goiTap.dangTaiChiTiet || dangLuu">
    <TieuDeTrang tieu-de="Chi tiet goi tap" mo-ta="Chinh sua metadata va quyen loi theo contract Backend." />
    <TrangThaiTaiDuLieu v-if="store.goiTap.dangTaiChiTiet && !store.goiTap.chiTiet" nhan="Dang tai chi tiet goi tap..." />
    <TrangThaiLoi v-else-if="store.goiTap.loiChiTiet" :thong-bao="store.goiTap.loiChiTiet.message" :co-the-thu-lai="true" :dang-thu-lai="store.goiTap.dangTaiChiTiet" @thu-lai="tai" />
    <form v-else @submit.prevent="luu">
      <p class="trang-danh-muc__canh-bao" role="note">Quyen loi ky da mua duoc dong bang snapshot; thay doi tu day khong sua quyen loi cua ky cu.</p>
      <div class="luoi-bieu-mau"><TruongBieuMau id="goi-tap-detail-code" nhan="Ma goi"><template #default="{ id }"><input :id="id" :value="form.code" readonly aria-readonly="true"></template></TruongBieuMau><TruongBieuMau id="goi-tap-detail-name" nhan="Ten goi" bat-buoc :loi="layLoi('name')"><template #default="{ id, ariaDescribedby, ariaInvalid }"><input :id="id" v-model="form.name" required :aria-describedby="ariaDescribedby" :aria-invalid="ariaInvalid"></template></TruongBieuMau><TruongBieuMau id="goi-tap-detail-price" nhan="Gia" bat-buoc><template #default="{ id }"><input :id="id" v-model.number="form.price" type="number" min="0" required></template></TruongBieuMau><TruongBieuMau id="goi-tap-detail-duration" nhan="Thoi han (ngay)" bat-buoc><template #default="{ id }"><input :id="id" v-model.number="form.duration_days" type="number" min="1" required></template></TruongBieuMau><TruongBieuMau id="goi-tap-detail-status" nhan="Trang thai"><template #default="{ id }"><select :id="id" v-model="form.status"><option value="DANG_BAN">Dang ban</option><option value="NGUNG_BAN">Ngung ban</option></select></template></TruongBieuMau><TruongBieuMau id="goi-tap-detail-description" nhan="Mo ta"><template #default="{ id }"><textarea :id="id" v-model="form.description" rows="4" maxlength="2000" /></template></TruongBieuMau></div>
      <BoSuaQuyenLoiGoiTap :quyen-loi="form.benefits" :dang-luu="dangLuuQuyenLoi" :loi="loi" @luu="luuQuyenLoi" />
      <p v-if="loi?.message" class="trang-danh-muc__loi" role="alert">{{ loi.message }}</p><div class="trang-danh-muc__hanh-dong"><button class="nut nut--phu" type="button" :disabled="dangLuu" @click="router.push({ name: 'adminGoiTap' })">Quay lai</button><button class="nut nut--chinh" type="submit" :disabled="dangLuu">{{ dangLuu ? 'Dang luu...' : 'Luu metadata' }}</button></div>
    </form>
  </section>
</template>
