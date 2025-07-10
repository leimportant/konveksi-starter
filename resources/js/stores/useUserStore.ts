import { defineStore } from 'pinia';
import axios from 'axios';

interface User {
  id: number;
  name: string;
  email: string;
  phone_number: string;
  employee_status?: string;
  location_id?: number | null;
  location?: Location;
  roles?: Role[];
  active: boolean;
}

interface Location {
  id: number;
  name: string;
}

interface Role {
  id: number;
  name: string;
}

interface UserForm {
  name: string;
  email: string;
  phone_number?: string;
  location_id?: number | null;
  role?: number | null;
  active: boolean;
}

interface State {
  users: User[];
  total: number;
  loaded: boolean;
  loading: boolean; // optional, if you want to track loading state
  currentPage: number;
  lastPage: number;
  error: null | string;
  filterName: string;
}

export const useUserStore = defineStore('user', {
   state: (): State => ({
        users: [],
        total: 0,
        loaded: false,
        loading: false, // optional, if you want to track loading state
        currentPage: 1,
        lastPage: 1,
        error: null,
        filterName: '',
    }),

  getters: {
    // Optional: You can add computed values like filtered users or user count if needed
    activeUsers(state): User[] {
      return state.users.filter(user => user.active);
    },
    inactiveUsers(state): User[] {
      return state.users.filter(user => !user.active);
    }
  },

  actions: {
    // Fetch users from the API
    async fetchUsers(page = 1, perPage = 10) {
      this.loading = true;
      try {
        const response = await axios.get('/api/users', {
            params: {
                page,
                perPage,
                name: this.filterName,  // pakai filterName kalau ada
            }
            });

            this.users = response.data.data;
            this.total = response.data.total;
            this.currentPage = page;
            this.lastPage = response.data.last_page || 1;
            this.loaded = true;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to load users';
        throw error;
      } finally {
        this.loading = false;
      }
    },
     setFilter(field: string, value: string) {
          if (field === 'name') {
          this.filterName = value;
          this.currentPage = 1;
          this.fetchUsers(1);
          }
      },
    // Create a new user
    async createUser(userData: UserForm) {
      this.loading = true;
      try {
        const response = await axios.post('/api/users', userData);
        // After creation, re-fetch users to get the latest list
        await this.fetchUsers();
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to create user';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Update an existing user
    async updateUser(id: number, userData: Partial<UserForm>) {
      this.loading = true;
      try {
        const response = await axios.put(`/api/users/${id}`, userData);
        // After updating, re-fetch users
        await this.fetchUsers();
        return response.data;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to update user';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Delete a user
    async deleteUser(id: number) {
      this.loading = true;
      try {
        await axios.delete(`/api/users/${id}`);
        // Remove the deleted user from local state
        this.users = this.users.filter(user => user.id !== id);
        return true;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to delete user';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
