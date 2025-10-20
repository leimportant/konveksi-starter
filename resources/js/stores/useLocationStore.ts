import { defineStore } from 'pinia';
import axios from 'axios';

interface Location {
    id: string;
    name: string;
}

interface State {
  items: Location[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  filterName: string;
}

export const useLocationStore = defineStore('location', {
     state: (): State => ({
        items: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        currentPage: 1,
        lastPage: 1,
        filterName: '',
    }),

    actions: {
        async fetchLocations(page = 1, perPage = 50) {
            this.loading = true;
            try {
                const response = await axios.get('/api/locations', {
                params: {
                    page,
                    perPage,
                    name: this.filterName,  // pakai filterName kalau ada
                }
                });

                this.items = response.data.data;
                this.total = response.data.total;
                this.currentPage = page;
                this.lastPage = response.data.last_page || 1;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Locations', error);
            }
        },
          setFilter(field: string, value: string) {
            if (field === 'name') {
            this.filterName = value;
            this.currentPage = 1;
            this.fetchLocations(1);
            }
        },
        async createLocation(id: string, name: string) {
            try {
                await axios.post('/api/locations', { id, name });
                await this.fetchLocations();
            } catch (error) {
                throw error;
            }
        },

        async updateLocation(id: string, name: string) {
            try {
                await axios.put(`/api/locations/${id}`, { name });
                await this.fetchLocations();
            } catch (error) {
                throw error;
            }
        },

        async deleteLocation(id: string) {
            try {
                await axios.delete(`/api/locations/${id}`);
                await this.fetchLocations();
            } catch (error) {
                throw error;
            }
        }
    },
});