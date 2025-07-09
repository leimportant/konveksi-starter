import { defineStore } from 'pinia';
import axios from 'axios';
import type { NavItem } from '@/types/index.d';

export interface Role {
  id: number;
  name: string;
  menus?: NavItem[];
}

export const useRoleStore = defineStore('role', {
    state: () => ({
        items: [] as Role[],
        loaded: false,
    }),

    actions: {
        async fetchRoles() {
            if (this.loaded) return;

            try {
                const response = await axios.get('/api/roles');
                this.items = response.data.data;
                this.loaded = true;
            } catch (error) {
                console.error('Failed to fetch Roles', error);
            }
        },

        async createRole(name: string) {
            try {
                await axios.post('/api/roles', { name });
                await this.fetchRoles();
            } catch (error) {
                throw error;
            }
        },

        async updateRole(id: number, name: string) {
            try {
                await axios.put(`/api/roles/${id}`, { name });
                await this.fetchRoles();
            } catch (error) {
                throw error;
            }
        },

        async deleteRole(id: number) {
            try {
                await axios.delete(`/api/roles/${id}`);
                await this.fetchRoles();
            } catch (error) {
                throw error;
            }
        },

        async assignMenusToRole(roleId: number, menuIds: number[]) {
            try {
                await axios.post(`/api/roles/assign-menus`, { role_id: roleId, menus: menuIds });
                //  await this.fetchRoles();
            } catch (error) {
                throw error;
            }
        }
    },
});