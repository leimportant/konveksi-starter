import { ref, watch } from 'vue';

export function useCurrencyInput(initialValue: number = 0) {
  const internalValue = ref(initialValue);
  const formattedValue = ref('');

  const formatRupiah = (value: number | string): string => {
    if (value === null || value === undefined || value === '') return '';
    const numberValue = typeof value === 'string' ? parseFloat(value.replace(/[^\d,-]/g, '').replace(/,/g, '.')) : value;
    if (isNaN(numberValue)) return '';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(numberValue);
  };

  const parseRupiah = (value: string): number => {
    const parsed = parseFloat(value.replace(/[^\d,-]/g, '').replace(/,/g, '.'));
    return isNaN(parsed) ? 0 : parsed;
  };

  // Initialize formatted value
  formattedValue.value = formatRupiah(initialValue);

  watch(internalValue, (newValue) => {
    formattedValue.value = formatRupiah(newValue);
  });

  watch(formattedValue, (newFormattedValue) => {
    const parsed = parseRupiah(newFormattedValue);
    if (parsed !== internalValue.value) {
      internalValue.value = parsed;
    }
  });

  return { internalValue, formattedValue, formatRupiah, parseRupiah };
}