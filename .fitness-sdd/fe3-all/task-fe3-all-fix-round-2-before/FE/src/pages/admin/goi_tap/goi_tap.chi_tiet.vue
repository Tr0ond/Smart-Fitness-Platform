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
const routeId = Number(route.params.id)
function nap(duLieu) { if (duLieu) Object.assign(form, { ...duLieu, benefits: { ...(duLieu.benefits ?? {}) } }) }
function layLoi(field) { return loi.value?.fieldErrors?.[field]?.[0] ?? '' }
async function tai() { nap(await store.taiChiTietGoiTap(routeId)) }
function xuLyTaiLai() { void tai() }
/** Update package metadata while preserving the Q01 prior-purchase snapshot. */
async function xuLyLuu() { dangLuu.value = true; loi.value = null; await store.capNhatGoiTap(routeId, { name: form.name, price: form.price, duration_days: form.duration_days, description: form.description, status: form.status }); loi.value = store.goiTap.loiMutation; dangLuu.value = false; if (!loi.value) await tai() }
/** Replace benefits for future purchases; Backend retains prior snapshots. */
async function xuLyLuuQuyenLoi(value) { dangLuuQuyenLoi.value = true; await store.thayTheQuyenLoiGoiTap(routeId, value); loi.value = store.goiTap.loiMutation; dangLuuQuyenLoi.value = false; if (!loi.value) await tai() }
function xuLyQuayLai() { void router.push({ name: 'adminGoiTap' }) }
onMounted(tai)
</script>

<template>
  <section
    class="trang-danh-muc trang-danh-muc--form"
    aria-label="Chi tiết gói tập"
    :aria-busy="store.goiTap.dangTaiChiTiet || dangLuu"
  >
    <TieuDeTrang
      tieu-de="Chi tiết gói tập"
      mo-ta="Chỉnh sửa metadata và quyền lợi theo hợp đồng Backend."
    />
    <TrangThaiTaiDuLieu
      v-if="store.goiTap.dangTaiChiTiet && !store.goiTap.chiTiet"
      nhan="Đang tải chi tiết gói tập…"
    />
    <TrangThaiLoi
      v-else-if="store.goiTap.loiChiTiet"
      :thong-bao="store.goiTap.loiChiTiet.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="store.goiTap.dangTaiChiTiet"
      @thu-lai="xuLyTaiLai"
    />
    <form
      v-else
      @submit.prevent="xuLyLuu"
    >
      <p
        class="trang-danh-muc__canh-bao"
        role="note"
      >
        Quyền lợi kỳ đã mua được đóng băng bằng snapshot; thay đổi tại đây không sửa quyền lợi của kỳ cũ.
      </p>
      <div class="luoi-bieu-mau">
        <TruongBieuMau
          id="goi-tap-detail-code"
          nhan="Mã gói"
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              :value="form.code"
              readonly
              aria-readonly="true"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-name"
          nhan="Tên gói"
          bat-buoc
          :loi="layLoi('name')"
        >
          <template #default="{ id: idTruong, ariaDescribedby, ariaInvalid }">
            <input
              :id="idTruong"
              v-model="form.name"
              required
              :aria-describedby="ariaDescribedby"
              :aria-invalid="ariaInvalid"
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-price"
          nhan="Giá"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.price"
              type="number"
              min="0"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-duration"
          nhan="Thời hạn (ngày)"
          bat-buoc
        >
          <template #default="{ id: idTruong }">
            <input
              :id="idTruong"
              v-model.number="form.duration_days"
              type="number"
              min="1"
              required
            >
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-status"
          nhan="Trạng thái"
        >
          <template #default="{ id: idTruong }">
            <select
              :id="idTruong"
              v-model="form.status"
            >
              <option value="DANG_BAN">
                Đang bán
              </option><option value="NGUNG_BAN">
                Ngừng bán
              </option>
            </select>
          </template>
        </TruongBieuMau><TruongBieuMau
          id="goi-tap-detail-description"
          nhan="Mô tả"
        >
          <template #default="{ id: idTruong }">
            <textarea
              :id="idTruong"
              v-model="form.description"
              rows="4"
              maxlength="2000"
            />
          </template>
        </TruongBieuMau>
      </div>
      <BoSuaQuyenLoiGoiTap
        :quyen-loi="form.benefits"
        :dang-luu="dangLuuQuyenLoi"
        :loi="loi"
        @luu="xuLyLuuQuyenLoi"
      />
      <p
        v-if="loi?.message"
        class="trang-danh-muc__loi"
        role="alert"
      >
        {{ loi.message }}
      </p><div class="trang-danh-muc__hanh-dong">
        <button
          class="nut nut--phu"
          type="button"
          :disabled="dangLuu"
          @click="xuLyQuayLai"
        >
          Quay lại
        </button><button
          class="nut nut--chinh"
          type="submit"
          :disabled="dangLuu"
        >
          {{ dangLuu ? 'Đang lưu…' : 'Lưu metadata' }}
        </button>
      </div>
    </form>
  </section>
</template>
