<script setup lang="ts">
import QrcodeVue from 'qrcode.vue';
import { ref } from 'vue';

const props = defineProps({
  value: {
    type: String,
    required: true,
  },
  size: {
    type: Number,
    default: 200,
  },
});

const qrValue = ref(props.value);
</script>

<template>
  <div class="qrcode-container">
    <qrcode-vue :value="qrValue" :size="props.size" level="H" />
  </div>
</template>

<style scoped>
.qrcode-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 8px;
  background-color: #fff;
}
</style>