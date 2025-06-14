<template>

  <Head title="Selamat Datang" />

  <div class="min-h-screen flex flex-col bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]">
    <!-- Header -->
    <header class="w-full px-6 py-4 flex justify-between items-center border-b dark:border-gray-800">
      <h1 class="text-xl font-bold"></h1>
      <nav class="flex space-x-4">
        <Link :href="route('home')"
          class="px-4 py-2 border rounded hover:bg-orange-100 dark:hover:bg-gray-700 transition">Beranda</Link>
        <Link v-if="$page.props.auth.user" :href="route('dashboard')"
          class="px-4 py-2 border rounded hover:bg-orange-100 dark:hover:bg-gray-700 transition">Dashboard</Link>
        <template v-else>
          <Link :href="route('login')"
            class="px-4 py-2 border rounded hover:bg-orange-100 dark:hover:bg-gray-700 transition">Masuk</Link>
          <Link :href="route('register')"
            class="px-4 py-2 border rounded hover:bg-orange-100 dark:hover:bg-gray-700 transition">Daftar</Link>
        </template>
      </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative h-[500px] bg-cover bg-center rounded-xl shadow mx-6 mt-8 overflow-hidden"
      style="background-image: url('/images/img.jpg')">
      <div class="absolute inset-0 bg-black/50 flex flex-col justify-center px-10 py-12 text-white">
        <h2 class="text-4xl lg:text-5xl font-bold mb-4">Solusi Digital Anda</h2>
        <p class="text-lg lg:text-xl max-w-xl mb-6">
          Kelola produksi, stok bahan, dan penjualan dalam satu platform terintegrasi. Praktis, cepat, dan efisien!
        </p>
        <div class="flex space-x-4">
          <Link :href="route('login')"
            class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded transition">
          Masuk Aplikasi</Link>
          <Link :href="route('register')"
            class="border border-white text-white hover:bg-white hover:text-orange-600 px-6 py-3 rounded transition">
          Daftar
          Sekarang</Link>
        </div>
      </div>
    </section>

    <!-- Keunggulan -->

    <section class="mt-12 px-6 max-w-6xl mx-auto text-center">
      <h3 class="text-2xl font-semibold mb-4">Fitur Unggulan</h3>
      <div class="flex flex-wrap justify-center gap-6">
        <!-- WIP -->
        <div class="w-[120px] h-[120px] p-4 rounded-lg shadow text-white 
                flex flex-col items-center justify-center text-center 
                bg-orange-500 hover:scale-105 transition-transform duration-300 ease-in-out">
          <Factory class="w-8 h-8" />
          <span class="mt-2 text-lg">Produksi</span>
        </div>

        <!-- Inventory -->
        <div class="w-[120px] h-[120px] p-4 rounded-lg shadow text-white 
                flex flex-col items-center justify-center text-center 
                bg-blue-500 hover:scale-105 transition-transform duration-300 ease-in-out">
          <Package class="w-8 h-8" />
          <span class="mt-2 text-lg">Stok Barang</span>
        </div>

        <!-- POS -->
        <div class="w-[120px] h-[120px] p-4 rounded-lg shadow text-white 
                flex flex-col items-center justify-center text-center 
                bg-green-500 hover:scale-105 transition-transform duration-300 ease-in-out">
          <ShoppingCart class="w-8 h-8" />
          <span class="mt-2 text-lg">POS Kasir</span>
        </div>

        <!-- Report -->
        <div class="w-[120px] h-[120px] p-4 rounded-lg shadow text-white 
                flex flex-col items-center justify-center text-center 
                bg-purple-500 hover:scale-105 transition-transform duration-300 ease-in-out">
          <BarChart3 class="w-8 h-8" />
          <span class="mt-2 text-lg">Laporan</span>
        </div>
      </div>
    </section>



  </div>
</template>
<script lang="ts" setup>
import { Package, Factory, ShoppingCart, BarChart3 } from 'lucide-vue-next';
</script>