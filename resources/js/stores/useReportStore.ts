import axios from 'axios';
import { defineStore } from 'pinia';
import { ref, Ref } from 'vue';

interface OmsetPerPayment {
    tanggal: string;
    payment_method: string;
    total_omset: string | number;
}

interface OmsetPerCustomer {
    tanggal: string;
    customer_id: number;
    customer_name: string;
    payment_method: string;
    total_omset: string | number;
    is_summary?: boolean;
    price: number;
    qty: number;
    total: number;
    product_id: number;
    product: string;
    customer: string;
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
    activities: Record<
        number,
        {
            name: string;
            qty: number;
        }
    >;
    subtotal_qty: number;
    total: number;
}

interface ProductionDetailItem {
    model_id: number;
    id: number;
    staff_name: string;
    activity_role_name: string;
    description: string;
    variant: string;
    model_name: string;
    estimation_price_pcs: number;
    estimation_qty: number;
    start_date: string;
    end_date: string;
    activity_role_id: number;
    size_id: string;
    qty: number;
    unit_price: number;
    created_at: string;
    activities: Record<
        number,
        {
            name: string;
            qty: number;
            remark: string | null; // ✅ remark bisa null
        }
    >;
    subtotal_qty: number;
    total: number;
    is_summary?: boolean;
}

interface ReportStoreState {
    salesSummary: Ref<any[]>;
    omsetSummary: Ref<OmsetPerPayment[]>;
    omsetSummaryPerCustomer: Ref<OmsetPerCustomer[]>;
    productionSummary: Ref<ProductionSummaryItem[]>;
    productionDetailItem: Ref<ProductionDetailItem[]>;
    loading: Ref<boolean>;
    error: Ref<any>;
    currentPage: Ref<number>;
    lastPage: Ref<number>;
    totalOmsetRecords: Ref<number>;
    totalRecords: Ref<number>;
    perPage: Ref<number>;
}

interface ReportStoreActions {
    fetchSalesSummary: (startDate: string, endDate: string, searchKey?: string) => Promise<void>;
    fetchProductionSummary: (startDate: string, endDate: string, searchKey?: string) => Promise<void>;
    fetchProductionDetail: (startDate: string, endDate: string, searchKey?: string, page?: number, searchModel?: string, perPage?: number) => Promise<void>;
    fetchOmsetPerPayment: (startDate: string, endDate: string, page?: number, perPage?: number) => Promise<void>;
    fetchOmsetPerCustomer: (customerId: number, searchKey: string, startDate: string, endDate: string, page?: number, itemsPerPage?: number) => Promise<void>;
}

type ReportStore = ReportStoreState & ReportStoreActions;

export const useReportStore = defineStore('report', (): ReportStore => {
    const salesSummary = ref<any[]>([]);
    const omsetSummary = ref<OmsetPerPayment[]>([]);
    const omsetSummaryPerCustomer = ref<OmsetPerCustomer[]>([]);
    const productionSummary = ref<ProductionSummaryItem[]>([]);
    const productionDetailItem = ref<ProductionDetailItem[]>([]);
    const loading = ref(false);
    const error = ref<any>(null);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const totalOmsetRecords = ref(0);
    const totalRecords = ref(0);
    const perPage = ref(50);

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

    async function fetchOmsetPerPayment(startDate: string, endDate: string, page: number = 1, itemsPerPage: number = 50) {
        loading.value = true;
        error.value = null;
        try {
            const response = await axios.get<PaginatedResponse<OmsetPerPayment>>('/api/reports/omset-per-payment', {
                params: { start_date: startDate, end_date: endDate, page, per_page: itemsPerPage },
            });
            console.log('Omset Summary Response:', response.data);
            omsetSummary.value = response.data.data;
            currentPage.value = response.data.current_page;
            lastPage.value = response.data.last_page;
            totalOmsetRecords.value = response.data.total;
            perPage.value = response.data.per_page;
        } catch (err: any) {
            error.value = err;
            console.error('Error fetching omzet per payment:', err);
        } finally {
            loading.value = false;
        }
    }

    async function fetchProductionDetail(startDate: string, endDate: string, searchKey: string = '', page: number = 1, searchModel: string = '', itemsPerPage: number = 50,) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/reports/production-detail', {
                params: {
                    start_date: startDate,
                    end_date: endDate,
                    search_key: searchKey,
                    search_model: searchModel,
                    page: page,
                    per_page: itemsPerPage,
                },
            });

            console.log('Production Detail Response:', response.data);
            productionDetailItem.value = response.data.data; // isi data per halaman
            currentPage.value = response.data.pagination.current_page; // update current page
            lastPage.value = response.data.pagination.last_page; // update last page
            totalRecords.value = response.data.pagination.total; // update total records
            perPage.value = response.data.pagination.per_page; // update per page
        } catch (err: any) {
            error.value = err;
            console.error('Error fetching production summary:', err);
        } finally {
            loading.value = false;
        }
    }

   async function fetchOmsetPerCustomer(
  customerId: number,
  searchKey: string,
  startDate: string,
  endDate: string,
  page: number = 1,
  itemsPerPage: number = 50
) {
  loading.value = true;
  error.value = null;
  try {
    const response = await axios.get<PaginatedResponse<OmsetPerCustomer>>(
      '/api/reports/omset-per-customer',
      {
        params: {
          customer_id: customerId,
          searchKey: searchKey,
          start_date: startDate,
          end_date: endDate,
          page,
          per_page: itemsPerPage,
        },
      }
    );

    console.log('Omset per customer Response:', response.data.data);

    // ✅ Perhatikan bahwa semua properti ada di dalam response.data
    omsetSummaryPerCustomer.value = response.data.data;
    currentPage.value = response.data.current_page; // update current page
        lastPage.value = response.data.last_page; // update last page
        totalRecords.value = response.data.total; // update total records
        perPage.value = response.data.per_page;
} catch (err: any) {
    error.value = err;
    console.error('Error fetching omzet per customer:', err);
  } finally {
    loading.value = false;
  }
}

    return {
        salesSummary,
        omsetSummary,
        omsetSummaryPerCustomer,
        productionSummary,
        productionDetailItem,
        loading,
        error,
        currentPage,
        lastPage,
        totalOmsetRecords,
        totalRecords,
        perPage,
        fetchSalesSummary,
        fetchProductionSummary,
        fetchProductionDetail,
        fetchOmsetPerPayment,
        fetchOmsetPerCustomer,
    };
});
