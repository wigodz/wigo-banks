<script setup lang="ts">
import { IconArrowDownCircle, IconArrowUpCircle } from '@/components/atoms/icons';
import { cn } from '@/lib/utils';

defineProps<{
    description: string;
    date: string;
    amount: string;
    type: 'income' | 'expense';
}>();
</script>

<template>
    <div class="flex items-center justify-between gap-4 rounded-lg bg-card px-4 py-3 shadow-card">
        <div class="flex items-center gap-3">
            <component
                :is="type === 'income' ? IconArrowUpCircle : IconArrowDownCircle"
                :class="cn('size-8', type === 'income' ? 'text-income' : 'text-expense')"
            />
            <div>
                <p class="text-sm font-medium text-foreground">{{ description }}</p>
                <p class="text-xs text-muted-foreground">{{ date }}</p>
            </div>
        </div>
        <p
            :class="
                cn(
                    'font-mono text-sm font-semibold tabular-nums',
                    type === 'income' ? 'text-income' : 'text-expense',
                )
            "
        >
            {{ type === 'income' ? '+' : '-' }}{{ amount }}
        </p>
    </div>
</template>
