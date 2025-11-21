<template>
  <Head title="Selamat Datang" />

  <div
    class="scroll-smooth min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans"
    style="background-image: url('/images/background-fashion.jpg'); background-size: cover; background-position: center;"
  >
    <!-- Navigation Bar -->
    <header
      class="sticky top-0 z-50 w-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-lg"
    >
      <div
        class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between"
      >
        <h1
          class="text-3xl font-extrabold tracking-tight text-gray-800 dark:text-white"
        >
          <span class="text-pink-500">Aninka</span> Fashion
        </h1>

        <!-- Mobile Toggle -->
        <div class="md:hidden flex items-center">
          <button
            @click="toggleMobileMenu"
            class="text-gray-600 dark:text-gray-300 focus:outline-none"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16m-7 6h7"
              ></path>
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Mobile Menu -->
    <nav
      v-if="showMobileMenu"
      class="md:hidden bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-md px-6 py-4"
    >
      <div class="flex flex-col space-y-3 font-medium">
        <Link href="/" class="nav-link">Home</Link>
        <Link href="/login" class="btn-primary">Login</Link>
        <Link href="/register" class="btn-primary">Register</Link>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative w-full h-[600px] md:h-[700px] overflow-hidden">
      <Swiper :autoplay="{ delay: 5000 }" :loop="false" effect="fade" class="h-full w-full">
        <SwiperSlide
          v-for="(img, index) in heroImages"
          :key="index"
          class="relative bg-cover bg-center bg-no-repeat"
        >
          <div
            class="h-full w-full bg-cover bg-center"
            :style="`background-image: url(${img})`"
          >
            <div
              class="absolute inset-0 bg-black/50 flex flex-col justify-center items-end text-right p-6 md:p-12 text-white w-full"
            >
              <h2
                class="text-4xl md:text-6xl font-extrabold leading-tight mb-4 drop-shadow-lg"
              >
                Temukan Gaya Terbaik Anda
              </h2>
              <p
                class="md:text-xl max-w-3xl mb-8 opacity-90 drop-shadow-md"
              >
                Platform fashion modern untuk belanja mudah, cepat, dan stylish.
              </p>
              <div class="flex space-x-4 justify-end">
                <Link href="/login" class="btn-cta">Mulai Sekarang</Link>
                <Link href="/register" class="btn-outline">Daftar Gratis</Link>
              </div>
            </div>
          </div>
        </SwiperSlide>
      </Swiper>
    </section>

    <!-- Koleksi Produk -->
    <section class="py-20 px-6 max-w-7xl mx-auto">
      <h3
        class="text-3xl md:text-4xl font-extrabold text-gray-800 dark:text-white mb-12 text-center"
      >
        Koleksi Produk Terbaru
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 px-4">
        <div
          v-for="(item, index) in products"
          :key="index"
          class="product-card"
        >
          <img :src="item.image" :alt="item.title" class="product-image" />
          <div class="p-4">
            <h4 class="product-title">{{ item.title }}</h4>
            <p class="product-desc">{{ item.desc }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script lang="ts" setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";

const showMobileMenu = ref(false);
const toggleMobileMenu = () => (showMobileMenu.value = !showMobileMenu.value);

// image list
const heroImages = ["/images/fashion1.png", "/images/fashion2.png"];

// product list
const products = [
  {
    image: "/images/img1.png",
    title: "Casual Outfit",
    desc: "Tampil modis dan nyaman untuk aktivitas harian.",
  },
  {
    image: "/images/img2.png",
    title: "Formal Style",
    desc: "Gaya elegan untuk acara formal & kantor.",
  },
  {
    image: "/images/img3.png",
    title: "Street Fashion",
    desc: "Look trendy dan penuh percaya diri di jalanan.",
  },
  {
    image: "/images/img4.png",
    title: "Sporty Look",
    desc: "Fashion aktif dan energik untuk keseharianmu.",
  },
];
</script>

<style scoped>
.nav-link {
  @apply text-gray-600 hover:text-pink-600 dark:text-gray-300 dark:hover:text-pink-400 transition duration-300;
}

.btn-primary {
  @apply px-4 py-2 rounded-full bg-gradient-to-r from-blue-500 to-blue-700 text-white shadow-lg transition duration-300 hover:from-blue-600 hover:to-blue-800;
}

.btn-cta {
  @apply bg-pink-600 hover:bg-pink-700 text-white px-8 py-3 rounded-full shadow-lg transition duration-300 transform hover:scale-105;
}

.btn-outline {
  @apply border border-white text-white px-8 py-3 rounded-full shadow-lg transition duration-300 transform hover:scale-105 hover:bg-white hover:text-pink-600;
}

.product-card {
  @apply bg-white dark:bg-gray-700 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-105;
}

.product-image {
  @apply w-full h-60 object-cover transition-transform duration-300 hover:scale-110;
}

.product-title {
  @apply font-bold text-xl text-gray-800 dark:text-white mb-2;
}

.product-desc {
  @apply text-gray-600 dark:text-gray-300 text-sm;
}
</style>