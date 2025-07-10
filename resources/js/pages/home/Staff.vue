<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useDashboardStore } from '@/stores/useDashboardStore';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const dashboardStore = useDashboardStore();
const page = usePage();
const user = page.props.auth.user;

// Role checking
const isCashier = computed(() => user.employee_status === 'kasir');
const isProduction = computed(() => user.employee_role === 'staff');

onMounted(() => {
    dashboardStore.fetchStats();
});
</script>

<template>
    <Head title="Home" />
    <AppLayout :breadcrumbs="[{ title: 'Dashboard Staff', href: '/home/staff' }]">
        <div class="space-y-6 p-6">
            <div class="text-2l font-bold text-gray-800">&nbsp;&nbsp;Selamat Datang <br/>&nbsp;👋 Halo, {{ user.name }} ({{ user.employee_status.toUpperCase() }})</div>

            <div class="flex items-center gap-3 rounded-xl border border-green-300 bg-green-100 p-4 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M18 10c0 4.418-3.582 8-8 8s-8-3.582-8-8 3.582-8 8-8 8 3.582 8 8zm-8 4a1 1 0 100-2 1 1 0 000 2zm1-7a1 1 0 00-2 0v4a1 1 0 002 0V7z"
                        clip-rule="evenodd"
                    />
                </svg>
                <p class="text-sm font-medium text-green-800">Gunakan informasi ini untuk mendukung operasional harian.</p>
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
