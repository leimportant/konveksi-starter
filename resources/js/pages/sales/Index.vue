<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
  ShoppingBag,
  MonitorCog,
  FileArchive,
  Wallet,
  Warehouse
} from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user;

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Point of sales', href: '/sales' }
];

// 🔗 Navigation
const go = (url: string) => router.visit(url);
</script>

<template>

  <Head title="Penjualan" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">

      <!-- Greeting -->
      <div
        class="rounded-xl bg-gradient-to-br from-blue-700 via-blue-600 to-blue-500 p-3 text-white shadow-lg relative overflow-hidden">

        <!-- Wave / Glow effect -->
        <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
        </div>

        <div class="relative flex items-center justify-between">
          <div>
            <h1 class="text-sm font-semibold tracking text-white">👋 Halo, {{ user.name }}</h1>
            <p class="text-xs text-blue-200 mt-0.5  text-white">Semoga harimu produktif dan menyenangkan</p>
          </div>

          <span class="rounded-md bg-indigo-700/40 px-2 py-1 text-xs font-medium">
            {{ user.employee_status.toUpperCase() }}
          </span>
        </div>
      </div>
      <div class="rounded-xl bg-amber-600 from-amber-500 via-amber-600 to-amber-500 p-2 text-white shadow-lg relative overflow-hidden">
        <div class="relative mt-2 space-y-1 text-xs">
          <p class="font-semibold flex gap-1">
            📅 {{ new Date().toLocaleDateString('id-ID', {
              weekday: 'long', year: 'numeric', month: 'long', day:
                'numeric'
            }) }}
          </p>

          <p class="text-white/90 flex gap-1">
            <span class="text-blue-200">•</span> Pastikan stok produk tersedia sebelum penjualan.
          </p>

          <p class="text-white/90 flex gap-1">
            <span class="text-blue-200 text-sm">•</span> Pastikan saldo kas awal sudah dibuat.
          </p>
        </div>

      </div>


      <!-- Total Transaksi -->
      <div class="hidden flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
        <div>
          <h2 class="text-sm font-medium text-gray-700">Total Transaksi Hari Ini</h2>
          <p class="mt-1 text-2xl font-bold text-blue-700">
            Rp {{ (page.props.sales_today_total ?? 0).toLocaleString('id-ID') }}
          </p>
        </div>
        <div class="rounded-full bg-blue-200 p-3">
          <Wallet class="h-6 w-6 text-blue-700" />
        </div>
      </div>

      <!-- MENU GRID -->
      <div class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-3">

        <!-- POS / Kasir -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-sky-50 p-4 shadow-sm transition hover:shadow-md hover:bg-sky-100 h-36"
          @click="go('/pos')">
          <div
            class="mb-2 rounded-full bg-indigo-600 p-3 flex items-center justify-center ring-4 ring-indigo-200/40 transition hover:scale-110">
            <ShoppingBag class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-sky-700">POS / Kasir</span>
        </div>

        <!-- Stock Monitoring -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-indigo-50 p-4 shadow-sm transition hover:shadow-md hover:bg-indigo-100 h-36"
          @click="go('/inventory/stock-monitoring')">
          <div
            class="mb-2 rounded-full bg-purple-600 p-3 flex items-center justify-center ring-4 ring-purple-200/40 transition hover:scale-110">
            <MonitorCog class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-indigo-700">Stock Monitoring</span>
        </div>

        <!-- Kas Awal -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-yellow-50 p-4 shadow-sm transition hover:shadow-md hover:bg-yellow-100 h-36"
          @click="go('/cash-balances/open-shift')">
          <div
            class="mb-2 rounded-full bg-yellow-600 p-3 flex items-center justify-center ring-4 ring-yellow-200/40 transition hover:scale-110">
            <FileArchive class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-yellow-700">Kas Awal</span>
        </div>

        <!-- Kas Akhir -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-red-50 p-4 shadow-sm transition hover:shadow-md hover:bg-red-100 h-36"
          @click="go('/cash-balances')">
          <div
            class="mb-2 rounded-full bg-red-600 p-3 flex items-center justify-center ring-4 ring-red-200/40 transition hover:scale-110">
            <FileArchive class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-red-700">Kas Akhir</span>
        </div>

        <!-- Transfer Stok -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-purple-50 p-4 shadow-sm transition hover:shadow-md hover:bg-purple-100 h-36"
          @click="go('/inventory/transfer-stock')">
          <div
            class="mb-2 rounded-full bg-fuchsia-600 p-3 flex items-center justify-center ring-4 ring-fuchsia-200/40 transition hover:scale-110">
            <Warehouse class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-purple-700">Transfer Stok</span>
        </div>

        <!-- Opname -->
        <div
          class="flex cursor-pointer flex-col items-center justify-center rounded-xl bg-emerald-50 p-4 shadow-sm transition hover:shadow-md hover:bg-emerald-100 h-36"
          @click="go('/stock-opnames')">
          <div
            class="mb-2 rounded-full bg-emerald-600 p-3 flex items-center justify-center ring-4 ring-emerald-200/40 transition hover:scale-110">
            <Warehouse class="h-6 w-6 text-white" />
          </div>
          <span class="mt-2 text-sm font-medium text-emerald-700">Opname</span>
        </div>

      </div>


    </div>
  </AppLayout>
</template>
