<script setup>
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import { useRoute } from 'vue-router'
import BoCucAdmin from './layouts/bo_cuc_admin.vue'
import BoCucCongKhai from './layouts/bo_cuc_cong_khai.vue'
import BoCucLeTan from './layouts/bo_cuc_le_tan.vue'
import BoCucLoi from './layouts/bo_cuc_loi.vue'
import BoCucPt from './layouts/bo_cuc_pt.vue'

const route = useRoute()
const BO_CUC_THEO_MA = Object.freeze({
  cong_khai: BoCucCongKhai,
  loi: BoCucLoi,
  admin: BoCucAdmin,
  pt: BoCucPt,
  le_tan: BoCucLeTan,
})

const boCucHienTai = computed(() => BO_CUC_THEO_MA[route?.meta?.boCuc] ?? BoCucCongKhai)
</script>

<template>
  <RouterView v-slot="{ Component }">
    <component :is="boCucHienTai">
      <component
        :is="Component"
        v-if="Component"
      />
    </component>
  </RouterView>
</template>
