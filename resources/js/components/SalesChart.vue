<script lang="ts">
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { ref, onMounted, computed, Ref } from 'vue'
import type { ChartOptions } from 'chart.js'
import axios from 'axios'

interface SalesDataItem {
  sale_date: string;
  product_id: number;
  product_name: string;
  total_sold: number;
}

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'SalesChart',
  components: {
    Bar
  },
  setup() {
    const salesData: Ref<SalesDataItem[]> = ref([])
    const loading = ref(true)
    const error = ref<Error | null>(null)

    // Auto-set startDate to 30 days ago, endDate to today
    const today = new Date()
    const thirtyDaysAgo = new Date(today)
    thirtyDaysAgo.setDate(today.getDate() - 30)

    const formatDate = (date: Date) => date.toISOString().slice(0, 10)

    const startDate = ref(formatDate(thirtyDaysAgo))
    const endDate = ref(formatDate(today))

    const getRandomColor = () =>
      '#' + Math.floor(Math.random() * 0xffffff).toString(16).padStart(6, '0')

    const productColors: Record<string, string> = {}

    const fetchSalesData = async () => {
      loading.value = true
      try {
        const response = await axios.get('api/dashboard/sales', {
          params: {
            startDate: startDate.value,
            endDate: endDate.value
          }
        })

        console.log('Sales data response:', response.data.data)

        if (Array.isArray(response.data?.data)) {
          salesData.value = response.data.data.map((item: any) => ({
            ...item,
            total_sold: Number(item.total_sold)
          }))
        } else {
          salesData.value = []
          console.warn('sales_data is not an array:', response.data?.sales_data)
        }
      } catch (err: any) {
        console.error(err)
        error.value = err
      } finally {
        loading.value = false
      }
    }

    onMounted(fetchSalesData)

    const chartData = computed(() => {
      const dates = Array.from(new Set(salesData.value.map(item => item.sale_date))).sort()
      const productNames = Array.from(new Set(salesData.value.map(item => item.product_name)))

      const datasets = productNames.map(productName => {
        if (!productColors[productName]) {
          productColors[productName] = getRandomColor()
        }

        const data = dates.map(date => {
          const match = salesData.value.find(
            item => item.sale_date === date && item.product_name === productName
          )
          return match ? match.total_sold : 0
        })

        return {
          label: productName,
          backgroundColor: productColors[productName],
          data
        }
      })

      return {
        labels: dates,
        datasets
      }
    })

    const chartOptions: ChartOptions<'bar'> = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top'
        },
        title: {
          display: true,
          text: 'Sales Summary by Product'
        }
      },
      scales: {
        x: { stacked: true },
        y: { stacked: true }
      }
    }

    return {
      chartData,
      chartOptions,
      loading,
      error,
      startDate,
      endDate,
      fetchSalesData
    }
  }
}
</script>

<template>
  <div>
    <div v-if="loading">Loading sales data...</div>
    <div v-else-if="error" class="text-red-500">{{ error.message }}</div>
    <div v-else>
      <div class="flex gap-2 justify-end mb-4">
        <input type="date" v-model="startDate" class="border rounded px-2 py-1" />
        <input type="date" v-model="endDate" class="border rounded px-2 py-1" />
        <button @click="fetchSalesData" class="bg-blue-500 text-white px-3 py-1 rounded">
          Load
        </button>
      </div>

      <div style="height: 400px; width: 100%;">
        <Bar id="sales-chart" :options="chartOptions" :data="chartData" />
      </div>
    </div>
  </div>
</template>

<style scoped>
#sales-chart {
  width: 100%;
  height: 100% !important;
}
</style>
