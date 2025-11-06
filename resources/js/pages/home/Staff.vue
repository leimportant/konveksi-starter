<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useDashboardStore } from '@/stores/useDashboardStore';
import { Head, usePage } from '@inertiajs/vue3';
import { CakeSliceIcon, Clock, CreditCard, PlusCircle, Wallet } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

const dashboardStore = useDashboardStore();
const page = usePage();
const user = page.props.auth.user;

const isCashier = computed(() => user.employee_status === 'kasir');
const isProduction = computed(() => user.employee_role === 'staff');

// contoh data sementara
const sisaKasbon = computed(() => dashboardStore.kasbon?.saldo_kasbon ?? 0);

onMounted(() => {
    dashboardStore.fetchKasbon();
});
</script>

<template>
    <Head title="Dashboard Staff" />
    <AppLayout :breadcrumbs="[{ title: 'Dashboard Staff', href: '/home/staff' }]">
        <div class="space-y-6 p-6">
            <!-- 🧭 Onboarding / Greeting -->
            <div class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 p-5 text-white shadow-md">
                <div class="flex items-center justify-between">
                    <h1 class="text-sm font-semibold">👋 Halo, {{ user.name }}</h1>
                    <span class="rounded-md bg-indigo-700/40 px-2 py-1 text-xs font-medium">
                        {{ user.employee_status.toUpperCase() }}
                    </span>
                </div>
                <p class="mt-2 text-sm opacity-90">Selamat datang kembali! Gunakan informasi ini untuk operasional harian.</p>
            </div>

            <!-- 💰 Card Sisa Kasbon -->
            <div class="flex items-center justify-between rounded-xl border border-green-300 bg-green-50 p-4 shadow-sm">
                <div>
                    <h2 v-if="user.employee_status && user.employee_status.toUpperCase() === 'OWNER'" class="text-sm font-medium text-gray-600">
                        Total Kasbon Karyawan
                    </h2>
                    <h2 v-else class="text-sm font-medium text-gray-600">Sisa Kasbon Anda</h2>
                    <p class="mt-1 text-2xl font-bold text-green-700">Rp {{ sisaKasbon.toLocaleString('id-ID') }}</p>
                </div>
                <div class="rounded-full bg-green-200 p-3">
                    <Wallet class="h-6 w-6 text-green-700" />
                </div>
            </div>

            <!-- 📱 Menu Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <!-- Buat Kasbon -->
                <div
                    @click="$inertia.visit('/kasbon/create')"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-transparent bg-indigo-50 p-4 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-100"
                >
                    <div class="mb-2 rounded-full bg-indigo-600 p-3">
                        <PlusCircle class="h-6 w-6 text-white" />
                    </div>
                    <span class="text-sm font-medium text-indigo-800">Buat Kasbon</span>
                </div>

                <!-- History Kasbon -->
                <div
                    @click="$inertia.visit('/kasbon')"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-transparent bg-amber-50 p-4 shadow-sm transition hover:border-amber-300 hover:bg-amber-100"
                >
                    <div class="mb-2 rounded-full bg-amber-500 p-3">
                        <Clock class="h-6 w-6 text-white" />
                    </div>
                    <span class="text-sm font-medium text-amber-800">History Kasbon</span>
                </div>

                <!-- History Pembayaran -->
                <div
                    @click="$inertia.visit('/kasbon/mutasi')"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-transparent bg-emerald-50 p-4 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100"
                >
                    <div class="mb-2 rounded-full bg-emerald-600 p-3">
                        <CreditCard class="h-6 w-6 text-white" />
                    </div>
                    <span class="text-sm font-medium text-emerald-800">History Pembayaran</span>
                </div>

                <!-- Payroll Kamu -->
                <div @click="$inertia.visit('/payroll')" class="flex flex-col items-center justify-center rounded-xl p-4 shadow-sm cursor-pointer
                        bg-rose-50 hover:bg-rose-100 transition border border-transparent hover:border-rose-300">
                <div class="bg-rose-600 p-3 rounded-full mb-2">
                  <CakeSliceIcon class="w-6 h-6 text-white" />
                </div>
                <span class="text-sm font-medium text-rose-800">Slip Gaji</span>
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
