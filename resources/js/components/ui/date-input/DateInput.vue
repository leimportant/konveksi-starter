<script setup lang="ts">
import { cn } from '@/lib/utils';
import { useVModel } from '@vueuse/core';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    defaultValue?: string;
    modelValue?: string | null;
    class?: HTMLAttributes['class'];
    min?: string;
    max?: string;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <input
        type="date"
        v-model="modelValue"
        :min="min"
        :max="max"
        :class="
            cn(
                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&::-webkit-calendar-picker-indicator]:text-foreground [&::-webkit-calendar-picker-indicator]:dark:invert',
                props.class,
            )
        "
    />
</template>