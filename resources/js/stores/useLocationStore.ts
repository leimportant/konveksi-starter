import { defineStore } from 'pinia';
import axios from 'axios';

interface Location {
    id: string;
    name: string;
}

export const useLocationStore = defineStore('location', {
    state: () => ({
        items: [] as Location[],
        loaded: false,
    }),

    actions: {
        async fetchLocations() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/locations');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Locations', error);
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