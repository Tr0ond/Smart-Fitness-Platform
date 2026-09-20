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
function layNhan(status) { return status === 'HOAT_DONG' ? 'Hoạt động' : 'Ngừng sử dụng' }
function kieu(status) { return status === 'HOAT_DONG' ? 'thanh_cong' : 'canh_bao' }
async function xuLyApDung() { await store.taiDanhSachBaiTap({ search: boLoc.search, status: boLoc.status }) }
function xuLyDatLaiBoLoc() { boLoc.search = ''; boLoc.status = ''; boLoc.difficulty = ''; boLoc.equipment_id = ''; boLoc.muscle_group_id = ''; void xuLyApDung() }
function xuLyMoTaoMoi() { void router.push({ name: 'adminTaoBaiTap' }) }
function xuLyMoChiTiet(item) { if (item?.id) void router.push({ name: 'adminChiTietBaiTap', params: { id: item.id } }) }
function xuLyTaiLai() { void store.taiDanhSachBaiTap(boLoc) }
onMounted(() => { void Promise.all([store.taiDanhSachBaiTap(), store.taiDanhSachDungCu(), store.taiDanhSachNhomCo()]) })
</script>

<template>
  <section
    class="trang-danh-muc"
    aria-label="Danh sách bài tập"
    :aria-busy="baiTap.dangTai"
  >
    <TieuDeTrang
      tieu-de="Bài tập"
      mo-ta="Tra cứu bài tập và quan hệ dụng cụ/nhóm cơ theo Backend."
    >
      <template #hanhDong>
        <button
          class="nut nut--chinh"
          type="button"
          @click="xuLyMoTaoMoi"
        >
          Tạo bài tập
        </button>
      </template>
    </TieuDeTrang>
    <BoLocDanhSach
      :dang-xu-ly="baiTap.dangTai"
      @ap-dung="xuLyApDung"
      @dat-lai="xuLyDatLaiBoLoc"
    >
      <TruongBieuMau
        id="bai-tap-search"
        nhan="Tìm kiếm"
      >
        <template #default="{ id }">
          <input
            :id="id"
            v-model="boLoc.search"
            type="search"
            maxlength="150"
            placeholder="Mã hoặc tên bài tập"
          >
        </template>
      </TruongBieuMau><TruongBieuMau
        id="bai-tap-status"
        nhan="Trạng thái"
      >
        <template #default="{ id }">
          <select
            :id="id"
            v-model="boLoc.status"
          >
            <option value="">
              Tất cả
            </option><option value="HOAT_DONG">
              Hoạt động
            </option><option value="NGUNG_SU_DUNG">
              Ngừng sử dụng
            </option>
          </select>
        </template>
      </TruongBieuMau><TruongBieuMau
        id="bai-tap-difficulty"
        nhan="Độ khó (lọc tại chỗ)"
      >
        <template #default="{ id }">
          <input
            :id="id"
            v-model="boLoc.difficulty"
            placeholder="Ví dụ: Beginner"
          >
        </template>
      </TruongBieuMau><TruongBieuMau
        id="bai-tap-equipment-filter"
        nhan="Dụng cụ"
      >
        <template #default="{ id }">
          <select
            :id="id"
            v-model="boLoc.equipment_id"
          >
            <option value="">
              Tất cả dụng cụ
            </option><option
              v-for="item in dungCu.danhSach"
              :key="item.id"
              :value="item.id"
            >
              {{ item.name }}
            </option>
          </select>
        </template>
      </TruongBieuMau><TruongBieuMau
        id="bai-tap-muscle-filter"
        nhan="Nhóm cơ"
      >
        <template #default="{ id }">
          <select
            :id="id"
            v-model="boLoc.muscle_group_id"
          >
            <option value="">
              Tất cả nhóm cơ
            </option><option
              v-for="item in nhomCo.danhSach"
              :key="item.id"
              :value="item.id"
            >
              {{ item.name }}
            </option>
          </select>
        </template>
      </TruongBieuMau>
    </BoLocDanhSach>
    <TrangThaiTaiDuLieu
      v-if="baiTap.dangTai && !baiTap.daTaiLanDau"
      nhan="Đang tải bài tập…"
    />
    <TrangThaiLoi
      v-else-if="baiTap.loi"
      :thong-bao="baiTap.loi.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="baiTap.dangTai"
      @thu-lai="xuLyTaiLai"
    />
    <TrangThaiTrong
      v-else-if="baiTap.daTaiLanDau && baiTap.danhSach.length === 0"
      tieu-de="Chưa có bài tập"
    />
    <TrangThaiTrong
      v-else-if="baiTap.daTaiLanDau && danhSachHienThi.length === 0"
      tieu-de="Không có kết quả phù hợp"
    />
    <BangDuLieu
      v-else
      :cot="[]"
      :hang="danhSachHienThi"
      tieu-de="Danh sách bài tập"
    >
      <template #tieuDeCot>
        <tr>
          <th scope="col">
            Mã
          </th><th scope="col">
            Tên
          </th><th scope="col">
            Độ khó
          </th><th scope="col">
            Quan hệ
          </th><th scope="col">
            Trạng thái
          </th><th scope="col">
            Thao tác
          </th>
        </tr>
      </template><template #hang="{ hang }">
        <tr
          v-for="item in hang"
          :key="item.id"
        >
          <td>{{ item.code }}</td><td><strong>{{ item.name }}</strong></td><td>{{ item.difficulty }}</td><td>{{ item.equipment_semantics ?? 'AND' }} · {{ item.equipment?.length ?? 0 }} dụng cụ · {{ item.muscle_groups?.length ?? 0 }} nhóm cơ</td><td>
            <HuyHieuTrangThai
              :trang-thai="kieu(item.status)"
              :nhan="layNhan(item.status)"
            />
          </td><td>
            <button
              class="nut nut--lien-ket"
              type="button"
              @click="xuLyMoChiTiet(item)"
            >
              Chi tiết
            </button>
          </td>
        </tr>
      </template>
    </BangDuLieu>
  </section>
</template>
