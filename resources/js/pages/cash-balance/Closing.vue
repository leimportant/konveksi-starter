<template>
  <Head title="Tutup Shift Kasir" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full px-4 py-6">

      <form @submit.prevent="submit">
        <div class="space-y-4">
          <div>
            <label class="text-[14px]">Total Penjualan Tunai</label>
            <Input v-model="cashSalesTotalInput.formattedValue.value" @blur="cashSalesTotalInput.formattedValue.value = cashSalesTotalInput.formatRupiah(cashSalesTotalInput.internalValue.value)" @focus="cashSalesTotalInput.formattedValue.value = cashSalesTotalInput.internalValue.value.toString()" type="text" required />
          </div>

          <div>
            <label class="text-[14px]">Kas Masuk (Cash In)</label>
            <Input v-model="cashInInput.formattedValue.value" @blur="cashInInput.formattedValue.value = cashInInput.formatRupiah(cashInInput.internalValue.value)" @focus="cashInInput.formattedValue.value = cashInInput.internalValue.value.toString()" type="text" required />
          </div>

          <div>
            <label class="text-[14px]">Kas Keluar (Cash Out)</label>
            <Input v-model="cashOutInput.formattedValue.value" @blur="cashOutInput.formattedValue.value = cashOutInput.formatRupiah(cashOutInput.internalValue.value)" @focus="cashOutInput.formattedValue.value = cashOutInput.internalValue.value.toString()" type="text" required />
          </div>

          <div>
            <label class="text-[14px]">Saldo Akhir</label>
            <Input v-model="closingBalanceInput.formattedValue.value" @blur="closingBalanceInput.formattedValue.value = closingBalanceInput.formatRupiah(closingBalanceInput.internalValue.value)" @focus="closingBalanceInput.formattedValue.value = closingBalanceInput.internalValue.value.toString()" type="text" required />
          </div>

          <div>
            <label class="text-[14px]">Selisih</label>
            <Input v-model="discrepancyInput.formattedValue.value" @blur="discrepancyInput.formattedValue.value = discrepancyInput.formatRupiah(discrepancyInput.internalValue.value)" @focus="discrepancyInput.formattedValue.value = discrepancyInput.internalValue.value.toString()" type="text" required />
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
import { ref, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useCashBalanceStore } from '@/stores/useCashBalanceStore'
import { useToast } from '@/composables/useToast'
import { useCurrencyInput } from '@/composables/useCurrencyInput'

const store = useCashBalanceStore()
const toast = useToast()

const page = usePage()
const id = page.props.id as number

const cashSalesTotalInput = useCurrencyInput(0);
const cashInInput = useCurrencyInput(0);
const cashOutInput = useCurrencyInput(0);
const closingBalanceInput = useCurrencyInput(0);
const discrepancyInput = useCurrencyInput(0);

const form = ref({
  cash_sales_total: cashSalesTotalInput.internalValue.value,
  cash_in: cashInInput.internalValue.value,
  cash_out: cashOutInput.internalValue.value,
  closing_balance: closingBalanceInput.internalValue.value,
  discrepancy: discrepancyInput.internalValue.value,
  notes: ''
});

watch(cashSalesTotalInput.internalValue, (newValue) => {
  form.value.cash_sales_total = newValue;
});
watch(cashInInput.internalValue, (newValue) => {
  form.value.cash_in = newValue;
});
watch(cashOutInput.internalValue, (newValue) => {
  form.value.cash_out = newValue;
});
watch(closingBalanceInput.internalValue, (newValue) => {
  form.value.closing_balance = newValue;
});
watch(discrepancyInput.internalValue, (newValue) => {
  form.value.discrepancy = newValue;
});

const loading = ref(false)

const submit = async () => {
  loading.value = true
  try {
    await store.closing(id, form.value)
    toast.success('Shift berhasil ditutup')
    router.visit('/sales');
  } catch (error: any) {
    if (error.response && error.response.data && error.response.data.errors) {
      for (const key in error.response.data.errors) {
        toast.error(error.response.data.errors[key][0]);
      }
    } else if (error.response && error.response.data && error.response.data.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('Gagal menutup shift');
      console.error(error);
    }
  } finally {
    loading.value = false
  }
}

const breadcrumbs = [
  { title: 'Close Shift', href: '/close-shift' }
];
</script>
