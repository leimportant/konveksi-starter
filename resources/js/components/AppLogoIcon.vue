<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 400 150"
    width="100%"
    height="auto"
    :class="className"
    v-bind="$attrs"
  >
    <text
      x="0"
      y="80"
      font-family="Georgia, serif"
      font-size="64"
      font-weight="bold"
      fill="#111827"
      letter-spacing="2"
    >
      Aninka
    </text>
    <text
      x="5"
      y="115"
      font-family="Arial, sans-serif"
      font-size="20"
      fill="#6B7280"
      letter-spacing="1"
    >
      FASHION
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
