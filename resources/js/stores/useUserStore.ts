import { defineStore } from 'pinia';
import axios from 'axios';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  active: boolean;
}

interface UserForm {
  name: string;
  email: string;
  password?: string;
  role: string;
  active: boolean;
}

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [] as User[],
    loading: false,
    error: null as string | null,
    loaded: false,
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
    async fetchUsers() {
      this.loading = true;
      try {
        const response = await axios.get('/api/users');
        this.users = response.data.data || [];
        this.loaded = true;
      } catch (error: any) {
        this.error = error.response?.data?.message || 'Failed to load users';
        throw error;
      } finally {
        this.loading = false;
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
