<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { BalanceCard, BalanceChart } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { transferencias } from '@/routes';
</script>

<template>
    <Head title="Painel" />

    <div class="flex flex-col gap-6">
        <Card>
            <CardHeader>
                <CardTitle>Saldo nos últimos 7 dias</CardTitle>
            </CardHeader>
            <CardContent>
                <BalanceChart />
            </CardContent>
        </Card>

        <div class="grid gap-4 sm:grid-cols-2">
            <BalanceCard />

            <Card class="justify-center">
                <CardContent class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-foreground">Transferências</p>
                        <p class="text-sm text-muted-foreground">
                            Realize saque ou movimente o dinheiro entre contas
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
