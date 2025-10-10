<script setup lang="ts">
import { onMounted } from 'vue';
import { useStoreCookies } from '@/stores/storeCookies';


const assistantUrl = import.meta.env.VITE_APP_CHATBOT_URL || 'http://medinachat.eu-4.evennode.com';
const storeCookies = useStoreCookies();

onMounted(() => {
  // Mulai transfer cookie saat halaman dimuat
  storeCookies.transferCookies(assistantUrl);
});


</script>
