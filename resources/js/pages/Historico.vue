<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    TransactionFilters,
    TransactionHistoryTable,
} from '@/components/molecules';
import type { TransactionFilterValues } from '@/components/molecules/TransactionFilters.vue';
import { PageHeader } from '@/components/organisms';

const filters = ref<TransactionFilterValues>({
    operation_type: '',
    type: '',
    date_from: '',
    date_to: '',
    receiver: '',
});

function onFilterUpdate(value: TransactionFilterValues) {
    filters.value = value;
}
</script>

<template>
    <Head title="Histórico" />

    <PageHeader
        title="Histórico"
        description="Suas movimentações financeiras"
    />

    <div class="mt-6">
        <TransactionHistoryTable :filters="filters">
            <template #actions>
                <TransactionFilters @update="onFilterUpdate" />
            </template>
        </TransactionHistoryTable>
    </div>
</template>
