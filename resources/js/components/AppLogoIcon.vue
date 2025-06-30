<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 400 450"
    width="100%"
    height="auto"
    :class="className"
    v-bind="$attrs"
  >
    <text
      x="50%"
      y="50%"
      dominant-baseline="middle"
      text-anchor="middle"
      font-size="200"
      font-family="Arial, sans-serif"
    >
      A&amp;F
    </text>
  </svg>
</template>


<script setup lang="ts">
import type { HTMLAttributes } from 'vue'

defineOptions({
  inheritAttrs: false,
})

interface Props {
  className?: HTMLAttributes['class']
}

defineProps<Props>()
</script>
