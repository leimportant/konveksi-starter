<template>
  <Head title="Tutup Shift Kasir" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full px-4 py-6">

      <form @submit.prevent="submit">
        <div class="space-y-4">
          <div>
            <label class="text-[14px]">Total Penjualan Tunai</label>
            <Input v-model="form.cash_sales_total" type="number" step="0.01" required />
          </div>

          <div>
            <label class="text-[14px]">Kas Masuk (Cash In)</label>
            <Input v-model="form.cash_in" type="number" step="0.01" required />
          </div>

          <div>
            <label class="text-[14px]">Kas Keluar (Cash Out)</label>
            <Input v-model="form.cash_out" type="number" step="0.01" required />
          </div>

          <div>
            <label class="text-[14px]">Saldo Akhir</label>
            <Input v-model="form.closing_balance" type="number" step="0.01" required />
          </div>

          <div>
            <label class="text-[14px]">Selisih</label>
            <Input v-model="form.discrepancy" type="number" step="0.01" required />
          </div>

          <div>
            <label class="text-[14px]">Catatan</label>
            <textarea
              v-model="form.notes"
              class="w-full border rounded px-3 py-2"
              rows="3"
              placeholder="Opsional"
            ></textarea>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <Button type="submit" :disabled="loading">Tutup Shift</Button>
          <Button type="button" variant="outline" @click="$inertia.visit('/cash-balances')">
            Batal
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useCashBalanceStore } from '@/stores/useCashBalanceStore'
import { useToast } from '@/composables/useToast'

const store = useCashBalanceStore()
const toast = useToast()

const page = usePage()
const id = page.props.id as number

const form = ref({
  cash_sales_total: 0,
  cash_in: 0,
  cash_out: 0,
  closing_balance: 0,
  discrepancy: 0,
  notes: ''
})

const loading = ref(false)

const submit = async () => {
  loading.value = true
  try {
    await store.closing(id, form.value)
    toast.success('Shift berhasil ditutup')
    router.visit('/sales');
  } catch (error) {
    toast.error('Gagal menutup shift')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const breadcrumbs = [
  { title: 'Close Shift', href: '/close-shift' }
];
</script>
