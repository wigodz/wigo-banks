<script setup lang="ts">
import { onMounted, ref } from 'vue';
import StatCard from '@/components/molecules/StatCard.vue';
import { formatCurrency } from '@/lib/format';
import { summary as summaryRoute } from '@/routes/wallet';

const received = ref<number | null>(null);
const sent = ref<number | null>(null);
const balance = ref<number | null>(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch(summaryRoute.url(), {
            headers: { Accept: 'application/json' },
        });
        const { data } = await response.json();

        received.value = data.received;
        sent.value = data.sent;
        balance.value = data.balance;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard
            label="Recebido no último mês"
            :value="formatCurrency(received ?? 0)"
            :loading="loading"
        />
        <StatCard
            label="Enviado no último mês"
            :value="formatCurrency(sent ?? 0)"
            :loading="loading"
        />
        <StatCard
            label="Saldo disponível"
            :value="formatCurrency(balance ?? 0)"
            :loading="loading"
        />
    </div>
</template>
