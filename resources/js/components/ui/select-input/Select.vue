<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: string | number
}>()

const emit = defineEmits(['update:modelValue'])

const value = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})
</script>

<template>
  <select v-model="value" class="input">
    <slot />
  </select>
</template>