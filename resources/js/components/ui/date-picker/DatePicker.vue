<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null
  },
  mode: {
    type: String,
    default: 'single'
  },
  placeholder: {
    type: String,
    default: 'Select date'
  }
});

const emit = defineEmits(['update:modelValue']);

const date = ref(props.modelValue);

const updateDate = (value: any) => {
  date.value = value;
  emit('update:modelValue', value);
};
</script>

<template>
  <div class="relative">
    <input
      type="text"
      :value="date"
      :placeholder="placeholder"
      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
      readonly
    />
    <div class="absolute right-3 top-2.5">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
    </div>
  </div>
</template>