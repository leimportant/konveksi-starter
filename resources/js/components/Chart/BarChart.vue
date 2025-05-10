<script setup lang="ts">
import BaseBarChart from '@/components/Chart/BaseBarChart.vue';
import { ref, computed } from 'vue';

// Filters
const startDate = ref('');
const endDate = ref('');
const status = ref('');

// Sample raw data
const rawData = [
  { label: 'Jan', value: 30, date: '2025-01-10', status: 'completed' },
  { label: 'Feb', value: 50, date: '2025-02-15', status: 'pending' },
  { label: 'Mar', value: 40, date: '2025-03-20', status: 'completed' },
];

// Filtered and formatted for chart
const chartData = computed(() => {
  const filtered = rawData.filter(item => {
    const itemDate = new Date(item.date);
    const start = startDate.value ? new Date(startDate.value) : null;
    const end = endDate.value ? new Date(endDate.value) : null;
    const matchDate =
      (!start || itemDate >= start) && (!end || itemDate <= end);
    const matchStatus =
      !status.value || item.status === status.value;
    return matchDate && matchStatus;
  });

  return {
    labels: filtered.map(item => item.label),
    datasets: [
      {
        label: 'Orders',
        data: filtered.map(item => item.value),
        backgroundColor: '#38bdf8',
      },
    ],
  };
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex gap-4">
      <input type="date" v-model="startDate" class="input" />
      <input type="date" v-model="endDate" class="input" />
      <select v-model="status" class="input">
        <option value="">All</option>
        <option value="completed">Completed</option>
        <option value="pending">Pending</option>
      </select>
    </div>

    <BaseBarChart :chart-data="chartData" :height="300" />
  </div>
</template>
