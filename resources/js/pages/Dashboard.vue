<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { BalanceCard, BalanceChart } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';
import { transferencias } from '@/routes';

const balanceHistory = Array.from({ length: 7 }, (_, index) => {
    const date = new Date();
    date.setDate(date.getDate() - (6 - index));

    return {
        date: date.toISOString().slice(0, 10),
        balance: [482000, 475000, 510000, 498000, 530000, 521000, 548000][index],
    };
});

const currentBalance = balanceHistory[balanceHistory.length - 1].balance;
</script>

<template>
    <Head title="Painel" />

    <div class="flex flex-col gap-6">
        <Card>
            <CardHeader>
                <CardTitle>Saldo nos últimos 7 dias</CardTitle>
            </CardHeader>
            <CardContent>
                <BalanceChart :data="balanceHistory" />
            </CardContent>
        </Card>

        <div class="grid gap-4 sm:grid-cols-2">
            <BalanceCard :balance="formatCurrency(currentBalance)" />

            <Card class="justify-center">
                <CardContent class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-foreground">Transferências</p>
                        <p class="text-sm text-muted-foreground">
                            Envie dinheiro para outras contas
                        </p>
                    </div>
                    <Button as-child>
                        <Link :href="transferencias()">Transferir</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
