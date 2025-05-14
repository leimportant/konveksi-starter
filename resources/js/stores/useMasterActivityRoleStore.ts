import { defineStore } from 'pinia';
import axios from 'axios';

interface ActivityRole {
    id: number;
    name: string;
    created_at?: string;
    updated_at?: string;
}

interface FetchParams {
    search?: string;
    sort_field?: string;
    sort_order?: 'asc' | 'desc';
    per_page?: number;
    page?: number;
}

export const useMasterActivityRoleStore = defineStore('masterActivityRole', {
    state: () => ({
        items: [] as ActivityRole[],
        loading: false,
        error: null as string | null,
        total: 0,
        currentPage: 1,
        lastPage: 1,
    }),

    actions: {
        async fetchActivityRoles(params: FetchParams = {}) {
            try {
                this.loading = true;
                const response = await axios.get('/api/activity-roles', { params });
                this.items = response.data.data;
                this.total = response.data.total;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to fetch activity roles';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async getActivityRoleById(id: number) {
            this.loading = true;
            const response = await axios.get(`/api/activity-roles/${id}`);
            return response.data;
        },
        async createActivityRole(data: Omit<ActivityRole, 'id' | 'created_at' | 'updated_at'>) {
            try {
                this.loading = true;
                const response = await axios.post('/api/activity-roles', data);
                await this.fetchActivityRoles({ page: this.currentPage });
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to create activity role';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateActivityRole(id: number, data: Partial<ActivityRole>) {
            try {
                this.loading = true;
                const response = await axios.put(`/api/activity-roles/${id}`, data);
                const index = this.items.findIndex(item => item.id === id);
                if (index !== -1) {
                    this.items[index] = response.data.data;
                }
                return response.data;
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to update activity role';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteActivityRole(id: number) {
            try {
                this.loading = true;
                await axios.delete(`/api/activity-roles/${id}`);
                this.items = this.items.filter(item => item.id !== id);
            } catch (error: any) {
                this.error = error.response?.data?.message || 'Failed to delete activity role';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});