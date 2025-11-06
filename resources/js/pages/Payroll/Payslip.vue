<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePayrollStore } from '@/stores/usePayrollStore';
import { Button } from '@/components/ui/button';
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';
import { Payslip } from '@/stores/usePayrollStore';
import { PrinterIcon, FileBadgeIcon } from 'lucide-vue-next';

// Props
const props = defineProps<{ id: string }>();
const payrollStore = usePayrollStore();

// Reactive
const currentSlip = ref<Payslip | null>(null);
const printArea = ref<HTMLElement | null>(null);

// Format currency
const formatRp = (v = 0) => `Rp ${Number(v || 0).toLocaleString('id-ID')}`;

// Gross & Net salary
const gross = computed(() =>
  (currentSlip.value?.total_upah || 0) +
  (currentSlip.value?.uang_makan || 0) +
  (currentSlip.value?.lembur || 0)
);
const net = computed(() =>
  currentSlip.value?.net_gaji ?? (gross.value - (currentSlip.value?.potongan || 0))
);

// Fetch payslip
const fetchSlip = async () => {
  try {
    currentSlip.value = await payrollStore.fetchPayslip(props.id);
  } catch (err) {
    console.error(err);
  }
};

// Print Payslip (without reloading)
const printSlip = () => {
  if (!printArea.value) return;
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  if (!printWindow) return;
  printWindow.document.write('<html><head><title>Payslip</title>');
  printWindow.document.write('<link href="/css/app.css" rel="stylesheet">'); // Tailwind CSS
  printWindow.document.write('</head><body>');
  printWindow.document.write(printArea.value.innerHTML);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.print();
};

// Export PDF

const exportPdf = async () => {
  if (!printArea.value) return;

  await new Promise((res) => setTimeout(res, 150));

  const canvas = await html2canvas(printArea.value, {
    scale: 2,
    useCORS: true,
    backgroundColor: "#ffffff",
  });

  const imgData = canvas.toDataURL("image/png");
  const pdf = new jsPDF({
    orientation: "portrait",
    unit: "mm",
    format: "a4",
  });

  const pageWidth = pdf.internal.pageSize.getWidth();
  const imgWidth = pageWidth;
  const imgHeight = (canvas.height * imgWidth) / canvas.width;

  pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
  pdf.save(`slip-gaji-${currentSlip.value?.employee?.name || 'employee'}.pdf`);
};



onMounted(fetchSlip);
</script>

<template>
  <Head title="Payslip View" />
  <AppLayout :breadcrumbs="[{ title: 'Payslip', href: '/payroll' }]">
    <div class="p-4 md:p-6">
      <!-- Payslip Area -->
      <div v-if="currentSlip" ref="printArea" class="bg-white rounded-lg shadow p-4 md:p-6 print:bg-white">

        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
          <h1 class="text-xl md:text-2xl font-bold">Slip Gaji Karyawan</h1>
          <span class="text-sm text-gray-500 hidden print:block">
            Tanggal Cetak: {{ new Date().toLocaleDateString('id-ID') }}
          </span>
        </div>

        <!-- Employee Info -->
        <div class="grid grid-cols-2 gap-4 mb-6 bg-gray-50 p-3 rounded">
          <div>
            <p class="text-sm font-medium">{{ currentSlip.employee?.name || '-' }}</p>
            <p class="text-xs text-gray-500">Nama Karyawan</p>
          </div>
          <div>
            <p class="text-sm font-medium">{{ currentSlip.activity_role?.name || '-' }}</p>
            <p class="text-xs text-gray-500">Bagian</p>
          </div>
        </div>

        <!-- Salary Table -->
        <div class="mb-4">
          <h2 class="font-semibold mb-2">Komponen Gaji</h2>
          <table class="w-full text-sm border border-gray-200 rounded">
            <tbody>
              <tr class="border-b">
                <td class="px-3 py-2">Upah Produksi</td>
                <td class="px-3 py-2 text-right">{{ formatRp(currentSlip.total_upah) }}</td>
              </tr>
              <tr class="border-b">
                <td class="px-3 py-2">Uang Makan</td>
                <td class="px-3 py-2 text-right">{{ formatRp(currentSlip.uang_makan) }}</td>
              </tr>
              <tr class="border-b">
                <td class="px-3 py-2">Lembur</td>
                <td class="px-3 py-2 text-right">{{ formatRp(currentSlip.lembur) }}</td>
              </tr>
              <tr class="font-semibold bg-gray-50">
                <td class="px-3 py-2">Total Penghasilan</td>
                <td class="px-3 py-2 text-right">{{ formatRp(currentSlip.net_gaji) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Deductions -->
        <div class="mb-4">
          <h2 class="font-semibold mb-2">Potongan</h2>
          <table class="w-full text-sm border border-gray-200 rounded">
            <tbody>
              <tr class="border-b">
                <td class="px-3 py-2">Potongan</td>
                <td class="px-3 py-2 text-right">-{{ formatRp(currentSlip.potongan) }}</td>
              </tr>
              <tr>
                <td class="px-3 py-2">Sisa Kasbon</td>
                <td class="px-3 py-2 text-right">{{ formatRp(currentSlip.sisa_kasbon) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total -->
        <div class="flex justify-end mt-4 text-lg font-bold text-indigo-600">
          Total Diterima: {{ formatRp(net) }}
        </div>
      </div>

      <!-- Actions -->
      <div v-if="currentSlip" class="flex gap-2 mt-4 no-print">
        <Button @click="printSlip" class="bg-green-600 text-white rounded hover:bg-green-700">
          <PrinterIcon  class="h-4 w-4"/> Print
        </Button>
        <Button @click="exportPdf" class="bg-blue-600 text-white rounded hover:bg-blue-700">
          <FileBadgeIcon  class="h-4 w-4"/> Export PDF
        </Button>
        <Button variant="outline" @click="router.visit('/payroll')" class="bg-gray-500 text-white rounded hover:bg-gray-600">
          Back
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
  .print\:bg-white {
    background-color: white !important;
  }
  body {
    margin: 0;
  }
}
</style>
