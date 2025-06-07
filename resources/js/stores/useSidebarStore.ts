import { defineStore } from 'pinia'

// Sidebar store to manage collapsed state (with persistence)
export const useSidebarStore = defineStore('sidebar', {
  state: () => ({
    isCollapsed: false
  }),

  actions: {
    toggleSidebar() {
      this.isCollapsed = !this.isCollapsed
    }
  },

  // This requires pinia-plugin-persistedstate
  persist: true
})
