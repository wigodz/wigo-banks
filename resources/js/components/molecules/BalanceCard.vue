<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
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
    <Card class="gap-2">
        <CardHeader>
            <p class="text-sm font-medium text-muted-foreground">
                {{ label ?? 'Saldo disponível' }}
            </p>
        </CardHeader>
        <CardContent>
            <Skeleton v-if="loading" class="h-10 w-40" />
            <p v-else class="font-mono text-4xl font-bold tracking-tight text-foreground tabular-nums">
                {{ formatCurrency(balance ?? 0) }}
            </p>
        </CardContent>
    </Card>
</template>
