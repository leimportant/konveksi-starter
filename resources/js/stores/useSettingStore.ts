import { defineStore } from 'pinia';
import axios from 'axios';

interface Setting {
    id: number;
    company_id: string;
    name: string;
    key: string;
    value: string;
}

interface SettingState {
    settings: Setting[];
}

export const useSettingStore = defineStore('setting', {
    state: (): SettingState => ({
        settings: [],
    }),
    actions: {
        async fetchSettings() {
            try {
                const response = await axios.get('/api/settings');
                this.settings = response.data;
            } catch (error) {
                console.error('Error fetching settings:', error);
            }
        },
        async updateSetting(id: number, value: string) {
            try {
                const response = await axios.put(`/api/settings/${id}`, { value });
                const index = this.settings.findIndex(setting => setting.id === id);
                if (index !== -1) {
                    this.settings[index] = response.data;
                }
                return response.data;
            } catch (error) {
                console.error(`Error updating setting with ID ${id}:`, error);
                throw error;
            }
        },
    },
});