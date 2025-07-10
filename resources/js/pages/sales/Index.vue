<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
  Warehouse,
  MonitorCog,
  ShoppingBag,
  FileArchive
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'POS (Sales)', href: '/sales' },
];

const handleClosing = () => {
  router.visit(`/cash-balances/closing`);
};
const handlePOS = () => {
  router.visit(`/pos`);
};
const handleStockMonitoring = () => {
  router.visit(`/inventory/stock-monitoring`);
};

const handleOpname = () => {
  router.visit(`/stock-opnames`);
};
</script>

<template>
  <Head title="Sales" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-4">
      <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <!-- POS Kasir -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-sky-50 dark:bg-sky-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handlePOS"
        >
          <ShoppingBag class="h-12 w-12 text-sky-600 dark:text-sky-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-sky-100 dark:bg-sky-800 py-2 text-center font-semibold text-sky-700 dark:text-sky-300 text-sm sm: md:text-sm">
            POS (Point Of Sales) / Kasir
          </div>
        </div>

        <!-- STOCK MONITORING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleStockMonitoring"
        >
          <MonitorCog class="h-12 w-12 text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-indigo-100 dark:bg-indigo-800 py-2 text-center font-semibold text-indigo-700 dark:text-indigo-300 text-sm sm: md:text-sm">
            STOCK MONITORING
          </div>
        </div>

        <!-- CUTTING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleClosing()"
        >
          <FileArchive class="h-12 w-12 text-green-600 dark:text-green-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-green-100 dark:bg-green-800 py-2 text-center font-semibold text-green-700 dark:text-green-300 text-sm sm: md:text-sm">
            CLOSING
          </div>
        </div>

        <!-- SEWING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleOpname()"
        >
          <Warehouse class="h-12 w-12 text-blue-600 dark:text-blue-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-blue-100 dark:bg-blue-800 py-2 text-center font-semibold text-blue-700 dark:text-blue-300 text-sm sm: md:text-sm">
            OPNAME
          </div>
        </div>


      </div>
    </div>
  </AppLayout>
</template>
