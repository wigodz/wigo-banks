<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    data: { date: string; balance: number }[];
}>();

const width = 600;
const height = 220;
const paddingX = 16;
const paddingTop = 24;
const labelHeight = 28;
const chartHeight = height - paddingTop - labelHeight;
const chartWidth = width - paddingX * 2;

const points = computed(() => {
    const values = props.data.map((d) => d.balance);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const lastIndex = props.data.length - 1 || 1;

    return props.data.map((d, i) => ({
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

function formatWeekday(date: string) {
    return new Date(`${date}T00:00:00`)
        .toLocaleDateString('pt-BR', { weekday: 'short' })
        .replace('.', '');
}
</script>

<template>
    <svg
        :viewBox="`0 0 ${width} ${height}`"
        preserveAspectRatio="none"
        class="h-56 w-full"
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
                {{ formatWeekday(point.date) }}
            </text>
        </g>
    </svg>
</template>
