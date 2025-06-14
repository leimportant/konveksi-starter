<template>
  <Head title="Selamat Datang" />

  <div class="scroll-smooth min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans" style="background-image: url('/images/background-fashion.jpg'); background-size: cover; background-position: center;">
    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-lg">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight text-gray-800 dark:text-white"><span class="text-pink-500">ANINKA</span> Fashion</h1>
        <nav class="hidden md:flex space-x-6 text-base font-medium">
          <Link :href="route('home')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Home</Link>
          <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Dashboard</Link>
          <template v-else>
            <Link :href="route('login')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Login</Link>
            <Link :href="route('register')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Register</Link>
          </template>
        </nav>
          <div class="md:hidden flex items-center">
            <button @click="toggleMobileMenu" class="text-gray-600 dark:text-gray-300 focus:outline-none">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
              </svg>
            </button>
          </div>
      </div>
    </header>

      <!-- Mobile Menu -->
      <nav v-if="showMobileMenu" class="md:hidden bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-md px-6 py-4">
        <div class="flex flex-col space-y-3 text-base font-medium">
          <Link :href="route('home')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Home</Link>
          <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Dashboard</Link>
          <template v-else>
            <Link :href="route('login')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Login</Link>
            <Link :href="route('register')" class="text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300">Register</Link>
          </template>
        </div>
      </nav>

    <!-- Hero Section with Slider -->
    <section class="relative w-full h-[600px] md:h-[700px] overflow-hidden">
      <Swiper class="h-full w-full" :autoplay="{ delay: 5000 }" :loop="true" effect="fade">
        <SwiperSlide v-for="(img, index) in ['/images/fashion1.png', '/images/fashion2.png']" :key="index" class="relative bg-cover bg-center bg-no-repeat transition-all duration-1000 ease-in-out">
          <div class="h-full w-full bg-cover bg-center" :style="`background-image: url(${img})`">
            <div class="absolute inset-0 bg-black/50 flex flex-col justify-center items-center text-center p-6 md:p-12 text-white">
              <h2 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4 drop-shadow-lg">Temukan Gaya Terbaik Anda</h2>
              <p class="text-base md:text-xl max-w-3xl mb-8 opacity-90 drop-shadow-md">Aplikasi fashion terintegrasi dengan sistem kasir, produksi & manajemen stok terbaik.</p>
              <div class="flex space-x-4">
                <Link :href="route('login')" class="bg-pink-600 hover:bg-pink-700 text-white px-8 py-3 rounded-full shadow-lg transition duration-300 transform hover:scale-105">Mulai Sekarang</Link>
                <Link :href="route('register')" class="border border-white text-white px-8 py-3 rounded-full shadow-lg transition duration-300 transform hover:scale-105 hover:bg-white hover:text-pink-600">Daftar Gratis</Link>
              </div>
            </div>
          </div>
        </SwiperSlide>
      </Swiper>
    </section>

    <!-- Section Koleksi Produk -->
    <section class="py-20 px-6 max-w-7xl mx-auto">
       <h3 class="text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white mb-12 text-center">Koleksi Produk Terbaru</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 px-4">
        <div class="bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-102">
          <img src="/images/img1.png" alt="Koleksi 1" class="w-full h-60 object-cover transition-transform duration-300 hover:scale-110" />
          <div class="p-4">
            <h4 class="font-bold text-xl text-gray-800 dark:text-white mb-2">Casual Outfit</h4>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Tampil modis dan nyaman untuk aktivitas harian.</p>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-102">
          <img src="/images/img2.png" alt="Koleksi 2" class="w-full h-60 object-cover transition-transform duration-300 hover:scale-110" />
          <div class="p-4">
            <h4 class="font-bold text-xl text-gray-800 dark:text-white mb-2">Formal Style</h4>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Gaya elegan untuk acara formal & kantor.</p>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-102">
          <img src="/images/img3.png" alt="Koleksi 3" class="w-full h-60 object-cover transition-transform duration-300 hover:scale-110" />
          <div class="p-4">
            <h4 class="font-bold text-xl text-gray-800 dark:text-white mb-2">Street Fashion</h4>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Look trendy dan penuh percaya diri di jalanan.</p>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-102">
          <img src="/images/img4.png" alt="Koleksi 4" class="w-full h-60 object-cover transition-transform duration-300 hover:scale-110" />
          <div class="p-4">
            <h4 class="font-bold text-xl text-gray-800 dark:text-white mb-2">Sporty Look</h4>
            <p class="text-gray-600 dark:text-gray-300 text-sm">Fashion aktif dan energik untuk keseharianmu.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script lang="ts" setup>
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
</script>
