import { defineStore } from 'pinia';
import axios from 'axios';

interface ProductPrice {
    id: number;
    product_id: number | undefined;
    effective_date: string;
    end_date: string;
    cost_price: number;
    is_active: boolean;
    price_types?: Array<{
        price_type_id: number | undefined
        uom_id: string | undefined
        size_id: string | undefined
        price: number
    }>;
    product?: {
        id: number;
        name: string;
    };
}

// interface ProductPriceType {
//     id: number;
//     product_price_id: number;
//     price_type_id: number;
//     uom_id: string;
//     size_id: string;
//     price_type?: {
//         id: number;
//         name: string;
//     };
//     uom?: {
//         id: string;
//         name: string;
//     };
//     size?: {
//         id: string;
//         name: string;
//     };
// }
interface Product {
    id: number;
    name: string;
}

interface PriceType {
    id: number;
    name: string;
}

interface Uom {
    id: string;
    name: string;
    created_by: number;
    updated_by: number;
    deleted_by: number | null;
}

interface Size {
    id: string;
    name: string;
}

interface PaginatedResponse<T> {
    current_page: number;
    data: T[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}



export const useProductPriceStore = defineStore('productPrice', {
    state: () => ({
        productPrices: [] as ProductPrice[],
        products: [] as Product[],
        priceTypes: [] as PriceType[],
        uoms: [] as Uom[],
        sizes: [] as Size[],
        loading: false,
        loaded: false
    }),

    actions: {
        async fetchAll() {
            if (this.loaded) return;

            this.loading = true;
            try {
                const [
                    productPricesResponse,
                    productsResponse,
                    priceTypesResponse,
                    uomsResponse,
                    sizesResponse
                ] = await Promise.all([
                    axios.get<PaginatedResponse<ProductPrice>>('/api/product-prices'),
                    axios.get<PaginatedResponse<Product>>('/api/products'), // ✅ ini penting
                    axios.get<PaginatedResponse<PriceType>>('/api/price-types'),
                    axios.get<PaginatedResponse<Uom>>('/api/uoms'),
                    axios.get<PaginatedResponse<Size>>('/api/sizes')
                ]);

                this.productPrices = productPricesResponse.data.data;
                this.products = productsResponse.data.data;
                this.priceTypes = priceTypesResponse.data.data; // Changed to access .data property
                this.uoms = uomsResponse.data.data;
                this.sizes = sizesResponse.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch data:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createProductPrice(data: Partial<ProductPrice>) {
            try {
                const response = await axios.post('/api/product-prices', data);
                this.productPrices.unshift(response.data);
                return response.data;
            } catch (error) {
                console.error('Failed to create product price:', error);
                throw error;
            }
        },

        async updateProductPrice(id: number, data: Partial<ProductPrice>) {
            try {
                const response = await axios.put(`/api/product-prices/${id}`, data);
                const index = this.productPrices.findIndex(item => item.id === id);
                if (index !== -1) {
                    this.productPrices[index] = response.data;
                }
                return response.data;
            } catch (error) {
                console.error('Failed to update product price:', error);
                throw error;
            }
        },

        async deleteProductPrice(id: number) {
            try {
                await axios.delete(`/api/product-prices/${id}`);
                const index = this.productPrices.findIndex(item => item.id === id);
                if (index !== -1) {
                    this.productPrices.splice(index, 1);
                }
            } catch (error) {
                console.error('Failed to delete product price:', error);
                throw error;
            }
        }
    }
});