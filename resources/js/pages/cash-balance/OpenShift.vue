<template>
  <Head title="Open New Shift" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="w-full px-4 py-6">
          <form @submit.prevent="handleSubmit" class="space-y-4 sm:space-y-6">
            <div>
              <label for="shiftNumber" class="block text-sm font-medium mb-1">
                Shift Number
              </label>
              <Input
                id="shiftNumber"
                v-model.number="form.shift_number"
                type="number"
                min="1"
                required
                class="w-full"
                :disabled="loading"
                @blur="validateShiftNumber"
              />
              <p v-if="errors.shift_number" class="mt-1 text-sm text-red-600">
                {{ errors.shift_number }}
              </p>
            </div>

            <div>
              <label for="openingBalance" class="block text-sm font-medium mb-1">
                Saldo Awal
              </label>
              <Input
                id="openingBalance"
                v-model.number="form.opening_balance"
                type="number"
                min="0"
                step="0.01"
                class="w-full"
                required
                :disabled="loading"
                @blur="validateOpeningBalance"
              />
              <p v-if="errors.opening_balance" class="mt-1 text-sm text-red-600">
                {{ errors.opening_balance }}
              </p>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-4 pt-4">
              <Button
                type="button"
                variant="outline"
                :disabled="loading"
                @click="$inertia.visit(route('cash-balance.index'))"
              >
                Cancel
              </Button>
              <Button type="submit" :disabled="loading || hasErrors">
                <span v-if="loading">
                  <Spinner class="mr-2" />
                  Processing...
                </span>
                <span v-else>Mulai Shift</span>
              </Button>
            </div>
          </form>
        </div>

  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { useCashBalanceStore } from '@/stores/useCashBalanceStore'
import { useToast } from '@/composables/useToast'

interface ShiftForm {
  shift_number: number
  opening_balance: number
}

interface FormErrors {
  shift_number?: string
  opening_balance?: string
}

const store = useCashBalanceStore()
const toast = useToast()

const form = ref<ShiftForm>({
  shift_number: 1,
  opening_balance: 0
})

const errors = ref<FormErrors>({})
const loading = ref(false)

const hasErrors = computed(() => {
  return Object.keys(errors.value).length > 0
})

const validateShiftNumber = () => {
  if (!form.value.shift_number) {
    errors.value.shift_number = 'Shift number is required'
  } else if (form.value.shift_number < 1) {
    errors.value.shift_number = 'Shift number must be at least 1'
  } else {
    delete errors.value.shift_number
  }
}

const validateOpeningBalance = () => {
  if (form.value.opening_balance === undefined || form.value.opening_balance === null) {
    errors.value.opening_balance = 'Opening balance is required'
  } else if (form.value.opening_balance < 0) {
    errors.value.opening_balance = 'Opening balance cannot be negative'
  } else {
    delete errors.value.opening_balance
  }
}

const handleSubmit = async () => {
  validateShiftNumber()
  validateOpeningBalance()

  if (hasErrors.value) {
    return
  }

  loading.value = true

  try {
    await store.createCashBalance(form.value)
    toast.success('Shift opened successfully')
    router.visit(route('sales.index'))
  } catch (error: any) {
    toast.error('Failed to open shift')
    console.error('Shift creation error:', error)
    
    if (error.response?.data?.errors) {
      errors.value = { ...errors.value, ...error.response.data.errors }
    }
  } finally {
    loading.value = false
  }
}

const breadcrumbs = [
  { title: 'Cash Management', href: route('cash-balance.index') },
  { title: 'Open New Shift', href: route('cash-balance.openshift') }
]
</script>