<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  quyenLoi: { type: Object, default: null },
  dangLuu: { type: Boolean, default: false },
  loi: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'luu'])
const macDinh = () => ({
  gym_access: true,
  fitness_assistant: false,
  fitness_assistant_limit: 0,
  trainer_chat: false,
  direct_trainer_sessions: 0,
})
const duLieu = reactive(macDinh())
const aiKhongGioiHan = ref(false)
const loiNoiBo = ref('')

function dongBo(value) {
  Object.assign(duLieu, macDinh(), value ?? {})
  if (!duLieu.fitness_assistant) {
    duLieu.fitness_assistant_limit = 0
    aiKhongGioiHan.value = false
  } else {
    aiKhongGioiHan.value = duLieu.fitness_assistant_limit === null
  }
  loiNoiBo.value = ''
}

watch(() => props.quyenLoi ?? props.modelValue, dongBo, { deep: true, immediate: true })

function xuLyPhatThayDoi() {
  emit('update:modelValue', { ...duLieu })
}

/**
 * Mục đích: đồng bộ quota khi Admin bật/tắt quyền trợ lý.
 * Đầu vào: trạng thái checkbox fitness_assistant hiện tại.
 * Xử lý: AI tắt luôn đặt quota về 0; AI bật từ 0 chuyển sang giới hạn dương mặc định.
 * Kết quả: modelValue phản ánh invariant mà Backend yêu cầu.
 * Side effect: phát một update UI, không gọi API và không thay đổi snapshot đã mua.
 * Quy tắc/Contract: AI bật dùng số dương hoặc null (không giới hạn), AI tắt dùng 0.
 */
function xuLyDoiTrangThaiTroLy() {
  if (!duLieu.fitness_assistant) {
    duLieu.fitness_assistant_limit = 0
    aiKhongGioiHan.value = false
  } else if (duLieu.fitness_assistant_limit === 0 || duLieu.fitness_assistant_limit === undefined) {
    duLieu.fitness_assistant_limit = 1
    aiKhongGioiHan.value = false
  }
  xuLyPhatThayDoi()
}

/**
 * Mục đích: chọn chế độ quota hữu hạn hoặc không giới hạn.
 * Đầu vào: lựa chọn aiKhongGioiHan từ control của trợ lý.
 * Xử lý: ánh xạ không giới hạn thành null và bảo đảm chế độ hữu hạn có số dương.
 * Kết quả: payload cục bộ sẵn sàng cho service validator.
 * Side effect: chỉ phát update modelValue; không tự lưu mạng.
 * Quy tắc/Contract: null là unlimited theo Backend; không dùng chuỗi giả như "unlimited".
 */
function xuLyCheDoTroLy() {
  if (!duLieu.fitness_assistant) {
    duLieu.fitness_assistant_limit = 0
    aiKhongGioiHan.value = false
  } else if (aiKhongGioiHan.value) {
    duLieu.fitness_assistant_limit = null
  } else if (duLieu.fitness_assistant_limit === null || duLieu.fitness_assistant_limit <= 0) {
    duLieu.fitness_assistant_limit = 1
  }
  xuLyPhatThayDoi()
}

/**
 * Mục đích: kiểm tra invariant quyền lợi trước khi phát sự kiện lưu.
 * Đầu vào: năm quyền lợi đang hiển thị trong form.
 * Xử lý: chặn bộ quyền lợi rỗng và quota hữu hạn không dương; lỗi hiển thị tại chỗ.
 * Kết quả: chỉ emit luu khi request chắc chắn phù hợp contract cơ bản.
 * Side effect: không gọi API; caller vẫn chịu trách nhiệm xử lý 422 từ Backend.
 * Quy tắc/Contract: phải có Gym, AI, chat hoặc số buổi PT trực tiếp; Q01 giữ snapshot kỳ cũ.
 */
function xuLyLuu() {
  if (!duLieu.fitness_assistant) duLieu.fitness_assistant_limit = 0
  if (duLieu.fitness_assistant && !aiKhongGioiHan.value
    && (!Number.isSafeInteger(duLieu.fitness_assistant_limit) || duLieu.fitness_assistant_limit <= 0)) {
    loiNoiBo.value = 'Nhập số lượt trợ lý lớn hơn 0 hoặc chọn Không giới hạn.'
    return
  }
  if (!duLieu.gym_access && !duLieu.fitness_assistant && !duLieu.trainer_chat
    && Number(duLieu.direct_trainer_sessions) === 0) {
    loiNoiBo.value = 'Gói tập phải có ít nhất một quyền lợi đang bật.'
    return
  }
  loiNoiBo.value = ''
  xuLyPhatThayDoi()
  emit('luu', { ...duLieu })
}
</script>

<template>
  <fieldset class="danh-muc-quyen-loi">
    <legend>Quyền lợi gói tập</legend>
    <p class="danh-muc-quyen-loi__goi-y">
      Quyền lợi được lưu theo snapshot của gói tại thời điểm mua.
    </p>
    <label><input
      v-model="duLieu.gym_access"
      type="checkbox"
      @change="xuLyPhatThayDoi"
    > Quyền vào phòng gym</label>
    <label><input
      v-model="duLieu.fitness_assistant"
      type="checkbox"
      @change="xuLyDoiTrangThaiTroLy"
    > Trợ lý fitness</label>
    <label for="fitness-assistant-mode">Chế độ lượt trợ lý</label>
    <select
      id="fitness-assistant-mode"
      v-model="aiKhongGioiHan"
      :disabled="!duLieu.fitness_assistant || props.dangLuu"
      @change="xuLyCheDoTroLy"
    >
      <option :value="false">
        Giới hạn lượt
      </option><option :value="true">
        Không giới hạn
      </option>
    </select>
    <label for="fitness-assistant-limit">Lượt trợ lý khi có giới hạn</label>
    <input
      id="fitness-assistant-limit"
      v-model.number="duLieu.fitness_assistant_limit"
      type="number"
      min="1"
      max="4294967295"
      step="1"
      :disabled="!duLieu.fitness_assistant || aiKhongGioiHan || props.dangLuu"
      @change="xuLyPhatThayDoi"
    >
    <label><input
      v-model="duLieu.trainer_chat"
      type="checkbox"
      @change="xuLyPhatThayDoi"
    > Chat với PT</label>
    <label for="direct-trainer-sessions">Số buổi PT trực tiếp</label>
    <input
      id="direct-trainer-sessions"
      v-model.number="duLieu.direct_trainer_sessions"
      type="number"
      min="0"
      max="65535"
      step="1"
      :disabled="props.dangLuu"
      @change="xuLyPhatThayDoi"
    >
    <p
      class="danh-muc-quyen-loi__canh-bao"
      role="note"
    >
      Thay đổi quyền lợi chỉ áp dụng cho lượt cấp mới; Backend giữ snapshot của các kỳ đã mua.
    </p>
    <p
      v-if="loiNoiBo"
      class="truong-bieu-mau__loi"
      role="alert"
      aria-live="polite"
    >
      {{ loiNoiBo }}
    </p>
    <p
      v-if="props.loi?.fieldErrors?.benefits"
      class="truong-bieu-mau__loi"
      role="alert"
    >
      {{ props.loi.fieldErrors.benefits[0] }}
    </p>
    <button
      class="nut nut--chinh"
      type="button"
      :disabled="props.dangLuu"
      @click="xuLyLuu"
    >
      {{ props.dangLuu ? 'Đang lưu…' : 'Lưu quyền lợi' }}
    </button>
  </fieldset>
</template>
