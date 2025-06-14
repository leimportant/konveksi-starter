<!-- src/components/QuillEditor.vue -->
<script setup lang="ts">
import { ref } from 'vue'

const content = ref('')
</script>

<template>
  <QuillEditor
    v-model:content="content"
    theme="snow"
    contentType="html"
  />
</template>
