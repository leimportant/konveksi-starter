<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {  onMounted } from 'vue';
import { useDashboardStore } from '@/stores/useDashboardStore';
import { storeToRefs } from 'pinia';
import { ShirtIcon, Truck, ShoppingBag } from 'lucide-vue-next';

import SalesChart from '../components/SalesChart.vue';
import SalesChartByAmount from '../components/SalesChartByAmount.vue';

const dashboardStore = useDashboardStore();
const { stats } = storeToRefs(dashboardStore);


onMounted(() => {
    dashboardStore.fetchStats();
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatNumber = (val: number) =>
    new Intl.NumberFormat('id-ID').format(val);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Stats Section  -->
         <div class="min-w-[200px] h-32 relative rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-between bg-sky-50 dark:bg-sky-950/20">
            <div class="relative z-10">
                <h3 class="text-gray-500 text-sm font-medium">Total Orders</h3>
                <p class="text-2xl font-bold mt-1 break-words">
                    {{ formatNumber(stats.total_order) }}
                </p>
            </div>
            <ShirtIcon class="absolute top-1/2 -translate-y-1/2 right-3 h-16 w-16 text-sky-600/10 dark:text-sky-400/10" />
        </div>

        <!-- CARD 2 -->
        <div class="min-w-[200px] h-32 relative rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-between bg-emerald-50 dark:bg-emerald-950/20">
            <div class="relative z-10">
                <h3 class="text-gray-500 text-sm font-medium">Total Transactions</h3>
                <p class="text-2xl font-bold mt-1 break-words">
                    {{ formatNumber(stats.total_transactions) }}
                </p>
            </div>
            <Truck class="absolute top-1/2 -translate-y-1/2 right-3 h-16 w-16 text-emerald-600/10 dark:text-emerald-400/10" />
        </div>

        <!-- CARD 3 -->
        <div class="min-w-[200px] h-32 relative rounded-xl border border-gray-200 dark:border-gray-700 p-4 flex flex-col justify-between bg-purple-50 dark:bg-purple-950/20">
            <div class="relative z-10">
                <h3 class="text-gray-500 text-sm font-medium">Total Products</h3>
                <p class="text-2xl font-bold mt-1 break-words">
                    {{ formatNumber(stats.total_products) }}
                </p>
            </div>
            <ShoppingBag class="absolute top-1/2 -translate-y-1/2 right-3 h-16 w-16 text-purple-600/10 dark:text-purple-400/10" />
        </div>


            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <SalesChart />
            </div>

             <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <SalesChartByAmount />
            </div>
           
        </div>
    </AppLayout>
</template>
