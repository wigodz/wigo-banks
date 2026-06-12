<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDateTime } from '@/lib/format';
import { transactions as transactionsRoute } from '@/routes/wallet';

const MOVEMENT_TYPE_NEGATIVE = 0;

type Transaction = {
    hash: string;
    amount: number;
    type: number;
    operation_type: string;
    receiver: string;
    created_at: string;
};

const transactions = ref<Transaction[]>([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await fetch(transactionsRoute.url(), {
            headers: { Accept: 'application/json' },
        });
        const { data } = await response.json();

        transactions.value = data.transactions;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Últimas transações</CardTitle>
        </CardHeader>
        <CardContent>
            <Skeleton v-if="loading" class="h-40 w-full" />

            <p v-else-if="transactions.length === 0" class="text-sm text-muted-foreground">Nenhuma transação encontrada.</p>

            <Table v-else>
                <TableHeader>
                    <TableRow>
                        <TableHead>Hash</TableHead>
                        <TableHead>Valor</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Recebedor</TableHead>
                        <TableHead>Data</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="transaction in transactions" :key="transaction.hash">
                        <TableCell class="font-mono">{{ transaction.hash }}</TableCell>
                        <TableCell
                            class="font-mono tabular-nums"
                            :class="transaction.type === MOVEMENT_TYPE_NEGATIVE ? 'text-expense' : 'text-income'"
                        >
                            {{ transaction.type === MOVEMENT_TYPE_NEGATIVE ? '-' : '+' }}{{ formatCurrency(transaction.amount) }}
                        </TableCell>
                        <TableCell>{{ transaction.operation_type }}</TableCell>
                        <TableCell>{{ transaction.receiver }}</TableCell>
                        <TableCell>{{ formatDateTime(transaction.created_at) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </CardContent>
    </Card>
</template>
