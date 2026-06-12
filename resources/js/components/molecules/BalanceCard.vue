<script setup lang="ts">
import { onMounted, ref } from 'vue';
import StatCard from '@/components/molecules/StatCard.vue';
import { formatCurrency } from '@/lib/format';
import { balance as balanceRoute } from '@/routes/wallet';

defineProps<{
    label?: string;
}>();

const balance = ref<number | null>(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch(balanceRoute.url(), {
            headers: { Accept: 'application/json' },
        });
        const { data } = await response.json();

        balance.value = data.balance;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <StatCard
        :label="label ?? 'Saldo disponível'"
        :value="formatCurrency(balance ?? 0)"
        :loading="loading"
    />
</template>
