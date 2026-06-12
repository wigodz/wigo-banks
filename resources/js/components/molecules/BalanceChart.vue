<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatCurrency } from '@/lib/format';
import { balanceHistory } from '@/routes/wallet';

const data = ref<{ date: string; balance: number }[]>([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch(balanceHistory.url(), {
            headers: { Accept: 'application/json' },
        });
        const { data: payload } = await response.json();

        data.value = payload.history;
    } finally {
        loading.value = false;
    }
});

const width = 600;
const height = 220;
const paddingX = 16;
const paddingTop = 24;
const labelHeight = 28;
const chartHeight = height - paddingTop - labelHeight;
const chartWidth = width - paddingX * 2;

const points = computed(() => {
    const values = data.value.map((d) => d.balance);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const lastIndex = data.value.length - 1 || 1;

    return data.value.map((d, i) => ({
        x: paddingX + (i / lastIndex) * chartWidth,
        y: paddingTop + chartHeight - ((d.balance - min) / range) * chartHeight,
        ...d,
    }));
});

const linePath = computed(() =>
    points.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' '),
);

const areaPath = computed(() => {
    if (!points.value.length) {
        return '';
    }

    const first = points.value[0];
    const last = points.value[points.value.length - 1];

    return `${linePath.value} L ${last.x} ${paddingTop + chartHeight} L ${first.x} ${paddingTop + chartHeight} Z`;
});

function formatDayLabel(date: string) {
    return new Date(`${date}T00:00:00`).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    });
}
</script>

<template>
    <Skeleton v-if="loading" class="h-56 w-full" />
    <div v-else class="relative h-56 w-full">
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            preserveAspectRatio="none"
            class="h-full w-full"
        >
            <defs>
                <linearGradient id="balance-area" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--color-primary)" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="var(--color-primary)" stop-opacity="0" />
                </linearGradient>
            </defs>

            <path :d="areaPath" fill="url(#balance-area)" />
            <path
                :d="linePath"
                fill="none"
                stroke="var(--color-primary)"
                stroke-width="2.5"
                stroke-linejoin="round"
                stroke-linecap="round"
            />

            <g v-for="point in points" :key="point.date">
                <circle :cx="point.x" :cy="point.y" r="3.5" fill="var(--color-primary)" />
                <text
                    :x="point.x"
                    :y="height - 6"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[10px] font-medium uppercase"
                >
                    {{ formatDayLabel(point.date) }}
                </text>
            </g>
        </svg>

        <TooltipProvider>
            <Tooltip v-for="point in points" :key="point.date">
                <TooltipTrigger as-child>
                    <span
                        class="absolute size-4 -translate-x-1/2 -translate-y-1/2 cursor-pointer rounded-full"
                        :style="{
                            left: `${(point.x / width) * 100}%`,
                            top: `${(point.y / height) * 100}%`,
                        }"
                    />
                </TooltipTrigger>
                <TooltipContent>
                    <p class="font-medium">{{ formatDayLabel(point.date) }}</p>
                    <p class="font-mono">{{ formatCurrency(point.balance) }}</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
