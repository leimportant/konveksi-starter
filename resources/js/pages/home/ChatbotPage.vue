<script setup lang="ts">
import { onMounted } from 'vue';

const assistantUrl = import.meta.env.VITE_ASSISTANT_URL || 'http://localhost:3000';

onMounted(() => {
  window.location.href = assistantUrl;
});
</script>
