<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useDashboardStore } from '@/stores/useDashboardStore';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { Wallet, Clock, CreditCard, PlusCircle } from 'lucide-vue-next';

const dashboardStore = useDashboardStore();
const page = usePage();
const user = page.props.auth.user;

const isCashier = computed(() => user.employee_status === 'kasir');
const isProduction = computed(() => user.employee_role === 'staff');

// contoh data sementara
const sisaKasbon = ref(1250000);

onMounted(() => {
  dashboardStore.fetchStats();
});
</script>

<template>
  <Head title="Dashboard Staff" />
  <AppLayout :breadcrumbs="[{ title: 'Dashboard Staff', href: '/home/staff' }]">
    <div class="space-y-6 p-6">
      <!-- 🧭 Onboarding / Greeting -->
      <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 p-5 text-white shadow-md">
  <div class="flex items-center justify-between">
    <h1 class="text-sm font-semibold">
      👋 Halo, {{ user.name }}
    </h1>
    <span class="text-xs font-medium bg-indigo-700/40 px-2 py-1 rounded-md">
      {{ user.employee_status.toUpperCase() }}
    </span>
  </div>
  <p class="text-sm opacity-90 mt-2">
    Selamat datang kembali! Gunakan informasi ini untuk operasional harian.
  </p>
</div>


      <!-- 💰 Card Sisa Kasbon -->
      <div class="rounded-xl border border-green-300 bg-green-50 p-4 shadow-sm flex items-center justify-between">
        <div>
          <h2 class="text-sm font-medium text-gray-600">Sisa Kasbon Anda</h2>
          <p class="text-2xl font-bold text-green-700 mt-1">
            Rp {{ sisaKasbon.toLocaleString('id-ID') }}
          </p>
        </div>
        <div class="bg-green-200 p-3 rounded-full">
          <Wallet class="w-6 h-6 text-green-700" />
        </div>
      </div>

      <!-- 📱 Menu Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
  <!-- Buat Kasbon -->
  <div
    @click="$inertia.visit('/kasbon/create')"
    class="flex flex-col items-center justify-center rounded-xl p-4 shadow-sm cursor-pointer
           bg-indigo-50 hover:bg-indigo-100 transition border border-transparent hover:border-indigo-300"
  >
    <div class="bg-indigo-600 p-3 rounded-full mb-2">
      <PlusCircle class="w-6 h-6 text-white" />
    </div>
    <span class="text-sm font-medium text-indigo-800">Buat Kasbon</span>
  </div>

  <!-- History Kasbon -->
  <div
    @click="$inertia.visit('/kasbon')"
    class="flex flex-col items-center justify-center rounded-xl p-4 shadow-sm cursor-pointer
           bg-amber-50 hover:bg-amber-100 transition border border-transparent hover:border-amber-300"
  >
    <div class="bg-amber-500 p-3 rounded-full mb-2">
      <Clock class="w-6 h-6 text-white" />
    </div>
    <span class="text-sm font-medium text-amber-800">History Kasbon</span>
  </div>

  <!-- History Pembayaran -->
  <div
    @click="$inertia.visit('/kasbon/mutasi')"
    class="flex flex-col items-center justify-center rounded-xl p-4 shadow-sm cursor-pointer
           bg-emerald-50 hover:bg-emerald-100 transition border border-transparent hover:border-emerald-300"
  >
    <div class="bg-emerald-600 p-3 rounded-full mb-2">
      <CreditCard class="w-6 h-6 text-white" />
    </div>
    <span class="text-sm font-medium text-emerald-800">History Pembayaran</span>
  </div>
</div>


              <div v-if="isCashier">
                <h2 class="text-xl font-bold text-gray-800">
                    <div></div>
                </h2>
            </div>
            <div v-else-if="isProduction">
                <h2 class="text-xl font-bold text-gray-800">
                    <!-- 📊 Statistik Produksi -->
                </h2>
            </div>
    </div>
  </AppLayout>
</template>
