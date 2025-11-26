<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { useActivityGroupStore } from '@/stores/useActivityGroupStore'
import { storeToRefs } from 'pinia'
import { type BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import * as Icons from 'lucide-vue-next' 
import { onMounted } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Konveksi', href: '/konveksi' }]
const activityGroupStore = useActivityGroupStore()
const { isCreateProduksi } = storeToRefs(activityGroupStore)

const handleClick = (code: string, isCreate: string) => {
  console.log(isCreate);

  switch (code) {
    case 'DESIGN':
      router.visit('/konveksi/model/list')
      break
    case 'PENERIMAAN':
      router.visit('/purchase-order')
      break
    default:
      router.visit(`/production/${code}/create/${isCreate}`)
      break
  }
}

onMounted(async () => {
  await activityGroupStore.fetchActivityGroups()
})
</script>

<template>
  <Head title="Konveksi" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div v-if="activityGroupStore.isLoading">Loading...</div>
    <div v-else-if="activityGroupStore.error">Error: {{ activityGroupStore.error }}</div>
    <!-- Empty State -->
    <div v-else-if="activityGroupStore.activityGroups.length === 0" 
         class="flex flex-col justify-center items-center py-24 text-center space-y-4">
      <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-full">
        <Icons.Lock class="h-10 w-10 text-gray-400" />
      </div>
      <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
        Belum ada akses ke menu
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Silakan hubungi administrator untuk mendapatkan hak akses.
      </p>
    </div>
    <!-- Activity Groups -->

    <div v-else class="grid gap-4 grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 p-4">
      <div
        v-for="group in activityGroupStore.activityGroups"
        :key="group.id"
        class="group relative aspect-video rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col items-center justify-center"
        :class="`bg-${group.bg_color} dark:bg-${group.color}-900`"
        @click="handleClick(group.id, isCreateProduksi)"
      >
        <component
          :is="(Icons as any)[group.icon]"
          class="h-10 w-10 sm:h-12 sm:w-12 transition-transform group-hover:scale-110"
          :class="`text-${group.color}-600 dark:text-${group.color}-400`"
        />

        <div
          class="absolute bottom-0 w-full py-1.5 text-center font-semibold text-xs sm:text-sm"
          :class="`text-${group.color}-700 dark:text-${group.color}-300 bg-${group.bg_color} dark:bg-${group.color}-800`"
        >
          {{ group.name }} 
        </div>
      </div>
    </div>
  </AppLayout>
</template>
