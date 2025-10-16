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
  const items = ref<ActivityGroup[]>([])
  const total = ref(0)
  const loaded = ref(false)
  const loading = ref(false)
  const currentPage = ref(1)
  const filterName = ref('')
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // 🔹 Fetch dengan pagination dan filter
  const fetchActivity = async (page = 1, perPage = 10) => {
    loading.value = true
    loaded.value = false
    currentPage.value = page

    try {
      const params = {
        page,
        perPage,
        search: filterName.value,
      }

      const response = await axios.get('/api/activity-group', { params })
      const data = response.data

      items.value = data.data
      total.value = data.total || 0
      loaded.value = true
    } catch (err) {
      console.error('❌ Failed to fetch activity groups:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // 🔹 Assign activity groups ke role tertentu
const assignToRole = async (roleId: number, selectedGroups: string[]) => {
  try {
    isLoading.value = true
    error.value = null

    const response = await axios.post(`/api/roles/${roleId}/activity-groups`, {
      activity_groups: selectedGroups,
    })

    return response.data
  } catch (err: any) {
    console.error('❌ Failed to assign activity groups:', err)
    error.value =
      err.response?.data?.message ||
      err.message ||
      'Failed to assign activity groups'
    throw err
  } finally {
    isLoading.value = false
  }
}


  // 🔹 Fetch semua (tanpa pagination)
  const fetchActivityGroups = async () => {
    isLoading.value = true
    error.value = null

    try {
      const response = await axios.get('/api/user-activity-group')
      const data = response.data.data

      if (Array.isArray(data)) {
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
    // state
    activityGroups,
    items,
    total,
    loaded,
    loading,
    currentPage,
    filterName,
    isLoading,
    error,
    // actions
    fetchActivity,
    fetchActivityGroups,
    assignToRole
  }
})
