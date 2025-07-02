import { cva, type VariantProps } from 'class-variance-authority';

export { default as DateInput } from './DateInput.vue';

export const dateInputVariants = cva(
    'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
    {
        variants: {
            variant: {
                default: 'border-input hover:border-gray-400 focus:border-gray-500',
                error: 'border-destructive focus:border-destructive',
                success: 'border-success focus:border-success',
            },
            size: {
                default: 'h-10',
                sm: 'h-8 text-xs px-2',
                lg: 'h-12  px-4',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export type DateInputVariants = VariantProps<typeof dateInputVariants>;