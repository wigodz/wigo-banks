<script setup>
import { Head } from '@inertiajs/vue3';
import { WalletSummaryCards, WithdrawalDialog } from '@/components/molecules';
import { PageHeader } from '@/components/organisms';
import { Button } from '@/components/ui/button';

function onWithdrawalConfirmed() {
    window.location.reload();
}
</script>

<template>
    <Head title="Transferências" />

    <PageHeader title="Transferências" description="Envie e receba valores entre contas" />

    <div class="mt-6 flex flex-col gap-6">
        <WalletSummaryCards />

        <div class="flex flex-wrap gap-3">
            <Button variant="outline" disabled>Depositar</Button>
            <WithdrawalDialog @confirmed="onWithdrawalConfirmed" />
            <Button variant="outline" disabled>Transferir</Button>
        </div>
    </div>
</template>
