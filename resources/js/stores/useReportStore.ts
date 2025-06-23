import { defineStore } from 'pinia';
import axios from 'axios';
import { ref } from 'vue';


interface ProductionSummaryItem {
  model_id: number;
  id: number;
  description: string;
  estimation_price_pcs: number;
  estimation_qty: number;
   start_date: string;
  end_date: string;
  activity_role_id: number;
  size_id: string;
  qty: number;
  activities: Record<number, {
    name: string;
    qty: number;
  }>;
  subtotal_qty: number;
}

export const useReportStore = defineStore('report', () => {
  const salesSummary = ref<any[]>([]);
  const productionSummary = ref<ProductionSummaryItem[]>([]);
  const loading = ref(false);
  const error = ref<any>(null);

  async function fetchSalesSummary(startDate: string, endDate: string, searchKey: string = '') {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/reports/sales-summary', {
        params: { start_date: startDate, end_date: endDate, search_key: searchKey },
      });
      salesSummary.value = response.data;
    } catch (err: any) {
      error.value = err;
      console.error('Error fetching sales summary:', err);
    } finally {
      loading.value = false;
    }
  }

  async function fetchProductionSummary(startDate: string, endDate: string, searchKey: string = '') {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/reports/production-summary', {
        params: { start_date: startDate, end_date: endDate, search_key: searchKey },
      });

      console.log('Production Summary Response:', response.data);
      productionSummary.value = response.data.data;
    } catch (err: any) {
      error.value = err;
      console.error('Error fetching production summary:', err);
    } finally {
      loading.value = false;
    }
  }

  return {
    salesSummary,
    productionSummary,
    loading,
    error,
    fetchSalesSummary,
    fetchProductionSummary,
  };
});