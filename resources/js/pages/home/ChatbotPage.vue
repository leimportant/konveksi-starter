<script setup lang="ts">
import { onMounted } from 'vue';
import { useStoreCookies } from '@/stores/storeCookies';


const assistantUrl = import.meta.env.VITE_ASSISTANT_URL || 'http://localhost:3000';
const storeCookies = useStoreCookies();

onMounted(() => {
  // Mulai transfer cookie saat halaman dimuat
  storeCookies.transferCookies(assistantUrl);
});
</script>
