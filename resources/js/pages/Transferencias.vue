<script setup>
import { Head } from '@inertiajs/vue3';
import { DepositDialog, TransferDialog, WalletSummaryCards, WalletTransactionsTable, WithdrawalDialog } from '@/components/molecules';
import { PageHeader } from '@/components/organisms';

function onWalletUpdated() {
    window.location.reload();
}
</script>

<template>
    <Head title="Transferências" />

    <PageHeader title="Transferências" description="Envie e receba valores entre contas" />

    <div class="mt-6 flex flex-col gap-6">
        <WalletSummaryCards />

        <div class="flex flex-wrap gap-3">
            <DepositDialog @confirmed="onWalletUpdated" />
            <WithdrawalDialog @confirmed="onWalletUpdated" />
            <TransferDialog @confirmed="onWalletUpdated" />
        </div>

        <WalletTransactionsTable />
    </div>
</template>
