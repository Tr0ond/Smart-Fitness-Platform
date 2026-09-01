<template>
  <section
    class="man-hinh-chon-vai-tro"
    aria-labelledby="tieu-de-chon-vai-tro"
  >
    <div class="man-hinh-chon-vai-tro__dau">
      <div>
        <p class="man-hinh-chon-vai-tro__nhan">
          Cổng Web / Workspace
        </p>
        <h1 id="tieu-de-chon-vai-tro">
          Chọn vai trò<br><em>làm việc.</em>
        </h1>
      </div>
      <span
        class="man-hinh-chon-vai-tro__so"
        aria-hidden="true"
      >01</span>
    </div>

    <p class="man-hinh-chon-vai-tro__mo-ta">
      Mỗi vai trò có một không gian riêng để bạn tập trung vào công việc quan trọng nhất.
    </p>

    <TrangThaiTaiDuLieu
      v-if="store.dangKhoiPhucPhien || dangThuLaiKhoiPhuc"
      nhan="Đang xác minh lại phiên làm việc…"
    />

    <TrangThaiLoi
      v-else-if="store.loiKhoiPhucPhien"
      :thong-bao="store.loiKhoiPhucPhien.message"
      :co-the-thu-lai="true"
      :dang-thu-lai="dangThuLaiKhoiPhuc"
      @thu-lai="thuLaiKhoiPhucPhien"
    />

    <template v-else-if="!daCoPhien">
      <div class="man-hinh-chon-vai-tro__tieu-de-khu-vuc">
        <span
          class="man-hinh-chon-vai-tro__duong-ke"
          aria-hidden="true"
        />
        <p>Chọn điểm bắt đầu</p>
      </div>
      <nav
        class="danh-sach-vai-tro"
        aria-label="Các trang đăng nhập"
      >
        <ul>
          <li>
            <RouterLink
              class="the-vai-tro the-vai-tro--admin"
              :to="{ name: 'adminDangNhap' }"
            >
              <span
                class="the-vai-tro__icon"
                aria-hidden="true"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path
                    d="M4 19.5V8.8L12 4l8 4.8v10.7M8 19.5v-5h8v5M9 9.5h6M12 7v5"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </span>
              <span class="the-vai-tro__noi-dung">
                <strong>Quản trị viên</strong>
                <small>Vận hành &amp; cấu hình hệ thống</small>
              </span>
              <span
                class="the-vai-tro__mui-ten"
                aria-hidden="true"
              >→</span>
            </RouterLink>
          </li>
          <li>
            <RouterLink
              class="the-vai-tro the-vai-tro--pt"
              :to="{ name: 'ptDangNhap' }"
            >
              <span
                class="the-vai-tro__icon"
                aria-hidden="true"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path
                    d="M7 4v16M17 4v16M4 8h6M14 8h6M4 16h6M14 16h6"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                  />
                  <path
                    d="M10 12h4"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                  />
                </svg>
              </span>
              <span class="the-vai-tro__noi-dung">
                <strong>Huấn luyện viên</strong>
                <small>Đồng hành cùng tiến độ hội viên</small>
              </span>
              <span
                class="the-vai-tro__mui-ten"
                aria-hidden="true"
              >→</span>
            </RouterLink>
          </li>
          <li>
            <RouterLink
              class="the-vai-tro the-vai-tro--le-tan"
              :to="{ name: 'leTanDangNhap' }"
            >
              <span
                class="the-vai-tro__icon"
                aria-hidden="true"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <path
                    d="M5 20v-9h14v9M3 20h18M8 11V7h8v4M10 7V4h4v3M9 15h6M9 18h6"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </span>
              <span class="the-vai-tro__noi-dung">
                <strong>Lễ tân</strong>
                <small>Đón tiếp &amp; hỗ trợ check-in</small>
              </span>
              <span
                class="the-vai-tro__mui-ten"
                aria-hidden="true"
              >→</span>
            </RouterLink>
          </li>
        </ul>
      </nav>
      <p class="man-hinh-chon-vai-tro__tro-giup">
        Bạn là hội viên? Ứng dụng tập luyện dành cho hội viên sẽ sớm được kết nối.
      </p>
      <p class="man-hinh-chon-vai-tro__quen-mat-khau">
        <RouterLink :to="{ name: 'quenMatKhau' }">
          Cần đặt lại mật khẩu?
        </RouterLink>
      </p>
    </template>

    <template v-else-if="laTaiKhoanKhongHoTro">
      <div class="trang-thai-tai-khoan">
        <span
          class="trang-thai-tai-khoan__icon"
          aria-hidden="true"
        >!</span>
        <div>
          <h2>Tài khoản này dùng cho ứng dụng hội viên</h2>
          <p>Cổng Web chỉ hỗ trợ Quản trị viên, Huấn luyện viên và Lễ tân.</p>
        </div>
      </div>
      <button
        class="nut nut--phu"
        type="button"
        @click="xuLyDangXuat"
      >
        Đăng xuất khỏi phiên này
      </button>
    </template>

    <template v-else>
      <div class="man-hinh-chon-vai-tro__tieu-de-khu-vuc">
        <span
          class="man-hinh-chon-vai-tro__duong-ke"
          aria-hidden="true"
        />
        <p>Vai trò khả dụng trong phiên</p>
      </div>
      <ul class="danh-sach-vai-tro danh-sach-vai-tro--da-dang-nhap">
        <li
          v-for="vaiTro in danhSachVaiTroWeb"
          :key="vaiTro"
        >
          <button
            class="the-vai-tro the-vai-tro--nut"
            type="button"
            @click="chonCongViec(vaiTro)"
          >
            <span
              class="the-vai-tro__icon"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
              >
                <path
                  d="M5 12h14M13 6l6 6-6 6"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
            <span class="the-vai-tro__noi-dung">
              <strong>{{ tenVaiTro[vaiTro] }}</strong>
              <small>{{ thongTinVaiTro[vaiTro] }}</small>
            </span>
            <span
              class="the-vai-tro__mui-ten"
              aria-hidden="true"
            >→</span>
          </button>
        </li>
      </ul>
      <p v-if="store.vaiTroDangDung">
        Vai trò đang dùng: {{ tenVaiTro[store.vaiTroDangDung] }}
      </p>
      <button
        class="nut nut--phu"
        type="button"
        @click="xuLyDangXuat"
      >
        Đăng xuất
      </button>
    </template>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import TrangThaiLoi from '../../dung_chung/trang_thai_loi.vue'
import TrangThaiTaiDuLieu from '../../dung_chung/trang_thai_tai_du_lieu.vue'
import { CAC_VAI_TRO_WEB, dieuPhoiTheoVaiTro } from '../../../router/bao_ve_tuyen_duong.js'
import { useXacThucStore } from '../../../stores/xac_thuc.store.js'

const router = useRouter()
const store = useXacThucStore()
const dangThuLaiKhoiPhuc = ref(false)
const tenVaiTro = Object.freeze({
  ADMIN: 'Quản trị viên',
  PT: 'Huấn luyện viên',
  RECEPTIONIST: 'Lễ tân',
})
const thongTinVaiTro = Object.freeze({
  ADMIN: 'Vận hành & cấu hình hệ thống',
  PT: 'Đồng hành cùng tiến độ hội viên',
  RECEPTIONIST: 'Đón tiếp & hỗ trợ check-in',
})

const danhSachVaiTroWeb = computed(() => {
  if (!Array.isArray(store.vaiTro)) {
    return []
  }

  return store.vaiTro.filter((vaiTro) => CAC_VAI_TRO_WEB.includes(vaiTro))
})

const daCoPhien = computed(() => typeof store.token === 'string' && store.token !== '' && store.nguoiDung !== null)
const laTaiKhoanKhongHoTro = computed(() => daCoPhien.value && danhSachVaiTroWeb.value.length === 0)

/**
 * Thu lai GET /auth/me sau network/timeout/5xx ma khong tao token moi.
 *
 * Dau vao: token dang duoc giu trong sessionStorage boi Auth Store.
 * Cach hoat dong: goi lai lifecycle restore; Store tiep tuc fail-closed trong luc pending
 * va chi khoi phuc user/roles/actor khi /me thanh cong.
 * Ket qua: selector hien roles moi, login neutral neu token invalid, hoac loi tam thoi moi.
 * Side effect: mot request /me; khong tu retry lap va khong mo protected content tu cache.
 * Business Rule: 401 xoa phien, network/timeout/5xx giu token de nguoi dung chu dong retry.
 */
async function thuLaiKhoiPhucPhien() {
  if (dangThuLaiKhoiPhuc.value || store.dangKhoiPhucPhien) {
    return
  }

  dangThuLaiKhoiPhuc.value = true

  try {
    await store.khoiPhucPhien()
  } finally {
    dangThuLaiKhoiPhuc.value = false
  }
}

/**
 * Chon actor context tu danh sach role da revalidate boi /auth/me.
 *
 * Dau vao: mot Web role dang hien thi trong selector.
 * Cach hoat dong: goi action Store allow-list, sau do chi dieu huong neu da co home route dang ky.
 * Ket qua: selector giu nguyen neu chua co business home; khong tao route gia.
 * Side effect: luu actor context hop le vao sessionStorage qua Store.
 * Business Rule: khong tu uu tien role, khong chon MEMBER va khong goi API role mutation.
 */
async function chonCongViec(vaiTro) {
  store.chonVaiTroDangDung(vaiTro)
  const diemDen = dieuPhoiTheoVaiTro(store, vaiTro)

  if (diemDen !== null) {
    await router.push(diemDen)
  }
}

/**
 * Dang xuat khoi neutral selector va xoa phien local/remote theo Auth Store.
 *
 * Dau vao: khong co.
 * Cach hoat dong: goi logout best-effort cua Store, sau do quay ve chooser cong khai.
 * Ket qua: route chooser khong xac thuc.
 * Side effect: Auth Store luon xoa token, actor, user va roles local.
 * Business Rule: logout khong phu thuoc remote logout thanh cong moi cleanup.
 */
async function xuLyDangXuat() {
  await store.dangXuat()
  await router.replace({ name: 'chonVaiTro' })
}
</script>

<style scoped>
.man-hinh-chon-vai-tro {
  max-width: 42rem;
  margin: 0 auto;
  padding: 0.25rem 0;
}

.man-hinh-chon-vai-tro__dau {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.man-hinh-chon-vai-tro__nhan,
.man-hinh-chon-vai-tro__tieu-de-khu-vuc p {
  margin: 0;
  color: #84939a;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

.man-hinh-chon-vai-tro h1 {
  margin: 0.7rem 0 0;
  color: #10212b;
  font-size: clamp(2.3rem, 6vw, 3.5rem);
  line-height: 0.94;
}

.man-hinh-chon-vai-tro h1 em {
  color: #e66a2c;
  font-family: "Barlow Condensed", sans-serif;
  font-style: normal;
}

.man-hinh-chon-vai-tro__so {
  display: inline-grid;
  width: 3.25rem;
  height: 3.25rem;
  place-items: center;
  border: 1px solid #d9e1db;
  border-radius: 0.8rem;
  color: #e66a2c;
  font-family: "Barlow Condensed", sans-serif;
  font-size: 1.45rem;
  font-weight: 700;
}

.man-hinh-chon-vai-tro__mo-ta {
  max-width: 29rem;
  margin: 1.35rem 0 2rem;
  color: #62747b;
  line-height: 1.55;
}

.man-hinh-chon-vai-tro__tieu-de-khu-vuc {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
}

.man-hinh-chon-vai-tro__duong-ke {
  width: 2rem;
  height: 2px;
  background: #b8f34a;
}

.danh-sach-vai-tro {
  margin: 0;
  padding: 0;
  list-style: none;
}

.danh-sach-vai-tro ul {
  display: grid;
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.the-vai-tro {
  display: flex;
  width: 100%;
  min-height: 5.2rem;
  align-items: center;
  gap: 1rem;
  border: 1px solid #dce5de;
  border-radius: 0.9rem;
  padding: 0.9rem 1rem;
  color: #10212b;
  background: #f8fbf8;
  text-align: left;
  text-decoration: none;
  transition: border-color 180ms ease, background-color 180ms ease, transform 180ms ease, box-shadow 180ms ease;
}

.the-vai-tro:hover {
  border-color: #e66a2c;
  color: #10212b;
  background: #fff;
  box-shadow: 0 10px 24px rgb(16 33 43 / 9%);
  transform: translateY(-2px);
}

.the-vai-tro:focus-visible {
  outline: 3px solid rgb(230 106 44 / 35%);
  outline-offset: 3px;
}

.the-vai-tro__icon {
  display: inline-grid;
  width: 2.6rem;
  height: 2.6rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 0.7rem;
  color: #10212b;
  background: #d8f69c;
}

.the-vai-tro--pt .the-vai-tro__icon {
  color: #fff;
  background: #e66a2c;
}

.the-vai-tro--le-tan .the-vai-tro__icon {
  background: #c9eaf2;
}

.the-vai-tro__icon svg {
  width: 1.35rem;
  height: 1.35rem;
}

.the-vai-tro__noi-dung {
  display: grid;
  min-width: 0;
  gap: 0.2rem;
}

.the-vai-tro__noi-dung strong {
  font-size: 1.05rem;
}

.the-vai-tro__noi-dung small {
  color: #708188;
  font-size: 0.82rem;
}

.the-vai-tro__mui-ten {
  margin-left: auto;
  color: #e66a2c;
  font-size: 1.35rem;
  line-height: 1;
}

.the-vai-tro--nut {
  cursor: pointer;
  font: inherit;
}

.man-hinh-chon-vai-tro__tro-giup {
  margin: 1.4rem 0 0;
  color: #84939a;
  font-size: 0.82rem;
  line-height: 1.5;
}

.man-hinh-chon-vai-tro__quen-mat-khau {
  margin: 1.2rem 0 0;
  font-size: 0.88rem;
  font-weight: 700;
}

.trang-thai-tai-khoan {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  margin-bottom: 1.25rem;
  padding: 1rem;
  border: 1px solid #f0c7ae;
  border-radius: 0.8rem;
  background: #fff6ef;
}

.trang-thai-tai-khoan__icon {
  display: inline-grid;
  width: 1.7rem;
  height: 1.7rem;
  flex: 0 0 auto;
  place-items: center;
  border-radius: 50%;
  color: #fff;
  background: #e66a2c;
  font-weight: 800;
}

.trang-thai-tai-khoan h2 {
  margin: 0;
  color: #10212b;
  font-size: 1rem;
}

.trang-thai-tai-khoan p {
  margin: 0.3rem 0 0;
  color: #7a675d;
  font-size: 0.86rem;
}

@media (max-width: 520px) {
  .man-hinh-chon-vai-tro__so {
    width: 2.7rem;
    height: 2.7rem;
  }

  .the-vai-tro {
    gap: 0.75rem;
  }

  .the-vai-tro__noi-dung small {
    max-width: 15rem;
  }
}
</style>
