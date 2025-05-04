<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Scissors, Cable, Scale3D, ShoppingBag, Truck, CalendarCheck, FileArchive, ShirtIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Konveksi',
        href: '/konveksi',
    },
];

const handleProcessClick = (processId: string) => {
    router.visit(`/konveksi/process/${processId}`);
};
</script>

<template>

    <Head title="Konveksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid auto-rows-min gap-3 md:grid-cols-4">

                <!-- ORDER MODEL -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-sky-100 dark:bg-sky-900 flex flex-col items-center justify-center cursor-pointer hover:bg-sky-200 dark:hover:bg-sky-800 transition-colors"
                    @click="handleProcessClick('ORD')">
                    <ShirtIcon class="h-12 w-12 text-sky-600 dark:text-sky-400" />
                    <div
                        class="absolute bottom-0 w-full bg-sky-200 dark:bg-sky-800 py-2 text-center text-sm font-medium text-sky-700 dark:text-sky-300">
                        ORDER MODEL
                    </div>
                </div>

                <!-- PENERIMAAN KAIN -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-indigo-100 dark:bg-indigo-900 flex flex-col items-center justify-center cursor-pointer hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-colors"
                    @click="handleProcessClick('REC')">
                    <FileArchive class="h-12 w-12 text-indigo-600 dark:text-indigo-400" />
                    <div
                        class="absolute bottom-0 w-full bg-indigo-200 dark:bg-indigo-800 py-2 text-center text-sm font-medium text-indigo-700 dark:text-indigo-300">
                        PENERIMAAN BAHAN & KAIN
                    </div>
                </div>

                <!-- CUTTING -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-green-100 dark:bg-green-900 flex flex-col items-center justify-center cursor-pointer hover:bg-green-200 dark:hover:bg-green-800 transition-colors"
                    @click="handleProcessClick('CUT')">
                    <Scissors class="h-12 w-12 text-green-600 dark:text-green-400" />
                    <div
                        class="absolute bottom-0 w-full bg-green-200 dark:bg-green-800 py-2 text-center text-sm font-medium text-green-700 dark:text-green-300">
                        CUTTING
                    </div>
                </div>

                <!-- SEWING -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-blue-100 dark:bg-blue-900 flex flex-col items-center justify-center cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                    @click="handleProcessClick('SEW')">
                    <Cable class="h-12 w-12 text-blue-600 dark:text-blue-400" />
                    <div
                        class="absolute bottom-0 w-full bg-blue-200 dark:bg-blue-800 py-2 text-center text-sm font-medium text-blue-700 dark:text-blue-300">
                        SEWING
                    </div>
                </div>

                <!-- FINISHING -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-orange-100 dark:bg-orange-900 flex flex-col items-center justify-center cursor-pointer hover:bg-orange-200 dark:hover:bg-orange-800 transition-colors"
                    @click="handleProcessClick('FIN')">
                    <Scale3D class="h-12 w-12 text-orange-600 dark:text-orange-400" />
                    <div
                        class="absolute bottom-0 w-full bg-orange-200 dark:bg-orange-800 py-2 text-center text-sm font-medium text-orange-700 dark:text-orange-300">
                        FINISHING & AKSESORIS
                    </div>
                </div>

                <!-- PAKING -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-yellow-100 dark:bg-yellow-900 flex flex-col items-center justify-center cursor-pointer hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-colors"
                    @click="handleProcessClick('PACK')">
                    <ShoppingBag class="h-12 w-12 text-yellow-600 dark:text-yellow-400" />
                    <div
                        class="absolute bottom-0 w-full bg-yellow-200 dark:bg-yellow-800 py-2 text-center text-sm font-medium text-yellow-700 dark:text-yellow-300">
                        PAKING
                    </div>
                </div>

                <!-- PENGIRIMAN -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-red-100 dark:bg-red-900 flex flex-col items-center justify-center cursor-pointer hover:bg-red-200 dark:hover:bg-red-800 transition-colors"
                    @click="handleProcessClick('SHIP')">
                    <Truck class="h-12 w-12 text-red-600 dark:text-red-400" />
                    <div
                        class="absolute bottom-0 w-full bg-red-200 dark:bg-red-800 py-2 text-center text-sm font-medium text-red-700 dark:text-red-300">
                        PENGIRIMAN
                    </div>
                </div>

                <!-- QUALITY CHECK -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-violet-100 dark:bg-violet-900 flex flex-col items-center justify-center cursor-pointer hover:bg-violet-200 dark:hover:bg-violet-800 transition-colors"
                    @click="handleProcessClick('QC')">
                    <CalendarCheck class="h-12 w-12 text-violet-600 dark:text-violet-400" />
                    <div
                        class="absolute bottom-0 w-full bg-violet-200 dark:bg-violet-800 py-2 text-center text-sm font-medium text-violet-700 dark:text-violet-300">
                        QUALITY CHECK
                    </div>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
