<script lang="ts">
import { Line as LineChart } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { ref, onMounted, computed, Ref } from 'vue'
import type { ChartOptions } from 'chart.js'
import axios from 'axios'

interface SalesDataItem {
  sale_date: string
  total_amount: number
}

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale)

export default {
  name: 'SalesChartByAmount',
  components: {
    LineChart
  },
  setup() {
    const salesData: Ref<SalesDataItem[]> = ref([])
    const loading = ref(true)
    const error = ref<Error | null>(null)

    const today = new Date()
    const thirtyDaysAgo = new Date(today)
    thirtyDaysAgo.setDate(today.getDate() - 30)

    const formatDate = (date: Date) => date.toISOString().slice(0, 10)

    const startDate = ref(formatDate(thirtyDaysAgo))
    const endDate = ref(formatDate(today))

    const fetchSalesData = async () => {
      loading.value = true
      try {
        const response = await axios.get('api/dashboard/sales/amount', {
          params: {
            startDate: startDate.value,
            endDate: endDate.value
          }
        })

        if (Array.isArray(response.data?.data)) {
          salesData.value = response.data.data.map((item: any) => ({
            sale_date: item.sale_date,
            total_amount: Number(item.total_amount)
          }))
        } else {
          salesData.value = []
          console.warn('sales_data is not an array:', response.data?.data)
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
      const labels = salesData.value.map(item => item.sale_date)
      const data = salesData.value.map(item => item.total_amount)

      return {
        labels,
        datasets: [
          {
            label: 'Total Penjualan',
            data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            fill: true,
            tension: 0.4,
            pointRadius: 3
          }
        ]
      }
    })

    const chartOptions: ChartOptions<'line'> = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top'
        },
        title: {
          display: true,
          text: 'Sales Summary by Total Amount'
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              const value = context.raw as number
              return 'Rp. ' + value.toLocaleString('id-ID')
            }
          }
        }
      },
      scales: {
        x: {
          stacked: false
        },
        y: {
          stacked: false,
          ticks: {
            callback: function (value) {
              return 'Rp. ' + Number(value).toLocaleString('id-ID')
            }
          }
        }
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
        <LineChart id="sales-amount-chart" :options="chartOptions" :data="chartData" />
      </div>
    </div>
  </div>
</template>

<style scoped>
#sales-amount-chart {
  width: 100%;
  height: 100% !important;
}
</style>
