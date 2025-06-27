import { defineStore } from 'pinia';
import axios from 'axios';
import { ref, Ref } from 'vue';

interface OmsetPerPayment {
  tanggal: string;
  payment_method: string;
  total_omset: string | number;
}


interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

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

interface ReportStoreState {
  salesSummary: Ref<any[]>;
  omsetSummary: Ref<OmsetPerPayment[]>;
  productionSummary: Ref<ProductionSummaryItem[]>;
  loading: Ref<boolean>;
  error: Ref<any>;
  currentPage: Ref<number>;
  lastPage: Ref<number>;
  totalOmsetRecords: Ref<number>;
  perPage: Ref<number>;
}

interface ReportStoreActions {
  fetchSalesSummary: (startDate: string, endDate: string, searchKey?: string) => Promise<void>;
  fetchProductionSummary: (startDate: string, endDate: string, searchKey?: string) => Promise<void>;
  fetchOmsetPerPayment: (startDate: string, endDate: string, page?: number, perPage?: number) => Promise<void>;
}

type ReportStore = ReportStoreState & ReportStoreActions;

export const useReportStore = defineStore('report', (): ReportStore => {
  const salesSummary = ref<any[]>([]);
  const omsetSummary = ref<OmsetPerPayment[]>([]);
  const productionSummary = ref<ProductionSummaryItem[]>([]);
  const loading = ref(false);
  const error = ref<any>(null);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const totalOmsetRecords = ref(0);
  const perPage = ref(10);

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

  async function fetchOmsetPerPayment(startDate: string, endDate: string, page: number = 1, perPage: number = 10) {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get<PaginatedResponse<OmsetPerPayment>>('/api/reports/omset-per-payment', {
        params: { start_date: startDate, end_date: endDate, page, per_page: perPage },
      });
      console.log('Omset Summary Response:', response.data);
      omsetSummary.value = response.data.data;
      currentPage.value = response.data.current_page;
      lastPage.value = response.data.last_page;
      totalOmsetRecords.value = response.data.total;
    } catch (err: any) {
      error.value = err;
      console.error('Error fetching omzet per payment:', err);
    } finally {
      loading.value = false;
    }
  }

  return {
    salesSummary,
    omsetSummary,
    productionSummary,
    loading,
    error,
    currentPage,
    lastPage,
    totalOmsetRecords,
    perPage,
    fetchSalesSummary,
    fetchProductionSummary,
    fetchOmsetPerPayment
  };
});