import { defineStore } from 'pinia'
import axios from 'axios'
import { ref } from 'vue'

interface ActivityGroup {
  id: string
  name: string
  icon: string
  color: string
  bg_color: string
  sorting: number
}

export const useActivityGroupStore = defineStore('activityGroup', () => {
  // 🧩 STATE
  const activityGroups = ref<ActivityGroup[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // 🔥 ACTION
  const fetchActivityGroups = async () => {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/user-activity-group')

      // Axios otomatis parse JSON ke response.data
      const data = response.data.data

      if (Array.isArray(data)) {
        // Urutkan berdasarkan field sorting
        activityGroups.value = data.sort((a, b) => a.sorting - b.sorting)
      } else {
        throw new Error('Invalid response format')
      }
    } catch (err: any) {
      console.error('❌ Failed to fetch activity groups:', err)
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to fetch activity groups'
    } finally {
      isLoading.value = false
    }
  }

  // 🔁 RETURN STATE & ACTION
  return {
    activityGroups,
    isLoading,
    error,
    fetchActivityGroups,
  }
})
