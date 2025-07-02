<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
  Scissors,
  Cable,
  Scale3D,
  ShoppingBag,
  Truck,
  CalendarCheck,
  FileArchive,
  ShirtIcon,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Konveksi', href: '/konveksi' },
];

const handleProcessClick = (processId: string) => {
  router.visit(`/production/${processId}`);
};
const handleListModelClick = () => {
  router.visit(`/konveksi/model/list`);
};
const handleListGoodReceiveClick = () => {
  router.visit(`/good-receive`);
};
</script>

<template>
  <Head title="Konveksi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-4">
      <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <!-- ORDER MODEL -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-sky-50 dark:bg-sky-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleListModelClick"
        >
          <ShirtIcon class="h-12 w-12 text-sky-600 dark:text-sky-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-sky-100 dark:bg-sky-800 py-2 text-center font-semibold text-sky-700 dark:text-sky-300 text-sm sm: md:text-sm">
            BUAT DESIGN
          </div>
        </div>

        <!-- PENERIMAAN BAHAN -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleListGoodReceiveClick"
        >
          <FileArchive class="h-12 w-12 text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-indigo-100 dark:bg-indigo-800 py-2 text-center font-semibold text-indigo-700 dark:text-indigo-300 text-sm sm: md:text-sm">
            PENERIMAAN BAHAN & KAIN
          </div>
        </div>

        <!-- CUTTING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('CUTTING')"
        >
          <Scissors class="h-12 w-12 text-green-600 dark:text-green-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-green-100 dark:bg-green-800 py-2 text-center font-semibold text-green-700 dark:text-green-300 text-sm sm: md:text-sm">
            CUTTING
          </div>
        </div>

        <!-- SEWING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('SEWING')"
        >
          <Cable class="h-12 w-12 text-blue-600 dark:text-blue-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-blue-100 dark:bg-blue-800 py-2 text-center font-semibold text-blue-700 dark:text-blue-300 text-sm sm: md:text-sm">
            JAHIT
          </div>
        </div>

        <!-- FINISHING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-orange-50 dark:bg-orange-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('FINISHING')"
        >
          <Scale3D class="h-12 w-12 text-orange-600 dark:text-orange-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-orange-100 dark:bg-orange-800 py-2 text-center font-semibold text-orange-700 dark:text-orange-300 text-sm sm: md:text-sm">
            FINISHING & AKSESORIS
          </div>
        </div>

        <!-- PAKING -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-yellow-50 dark:bg-yellow-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('PAKING')"
        >
          <ShoppingBag class="h-12 w-12 text-yellow-600 dark:text-yellow-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-yellow-100 dark:bg-yellow-800 py-2 text-center font-semibold text-yellow-700 dark:text-yellow-300 text-sm sm: md:text-sm">
            PAKING
          </div>
        </div>

        <!-- PENGIRIMAN -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('PENGIRIMAN')"
        >
          <Truck class="h-12 w-12 text-red-600 dark:text-red-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-red-100 dark:bg-red-800 py-2 text-center font-semibold text-red-700 dark:text-red-300 text-sm sm: md:text-sm">
            PENGIRIMAN
          </div>
        </div>

        <!-- QUALITY CHECK -->
        <div
          class="group relative aspect-video rounded-2xl border border-gray-200 dark:border-gray-700 bg-violet-50 dark:bg-violet-900 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
          @click="handleProcessClick('QUALITY_CHECK')"
        >
          <CalendarCheck class="h-12 w-12 text-violet-600 dark:text-violet-400 transition-transform group-hover:scale-110" />
          <div class="absolute bottom-0 w-full bg-violet-100 dark:bg-violet-800 py-2 text-center font-semibold text-violet-700 dark:text-violet-300 text-sm sm: md:text-sm">
            QUALITY CHECK (REJECT)
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
