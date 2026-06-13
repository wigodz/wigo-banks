<script setup lang="ts">
import { isAxiosError } from 'axios';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatCurrency, formatDateTime } from '@/lib/format';
import http from '@/lib/http';
import wallet, {
    balance as balanceRoute,
    history as historyRoute,
} from '@/routes/wallet';

const MOVEMENT_TYPE_NEGATIVE = 0;

type Transaction = {
    hash: string;
    amount: number;
    type: number;
    operation_type: string;
    reversed: boolean;
    reversible: boolean;
    receiver: string;
    created_at: string;
};

type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

const props = defineProps<{
    filters?: Record<string, string>;
}>();

const transactions = ref<Transaction[]>([]);
const pagination = ref<Pagination | null>(null);
const loading = ref(true);
const balanceCents = ref(0);

async function loadBalance() {
    const { data } = await http.get(balanceRoute.url());
    balanceCents.value = data.data.balance;
}

function blockedByBalance(transaction: Transaction) {
    return (
        transaction.type !== MOVEMENT_TYPE_NEGATIVE &&
        transaction.amount > balanceCents.value
    );
}

function canReverse(transaction: Transaction) {
    return transaction.reversible && !blockedByBalance(transaction);
}

function reverseDisabledReason(transaction: Transaction) {
    if (transaction.reversed) {
        return 'Transação já revertida';
    }

    if (blockedByBalance(transaction)) {
        return 'Saldo insuficiente para reverter';
    }

    return 'Você não pode reverter esta transação';
}

function buildQuery(page: number) {
    const query: Record<string, string | number> = { page };

    for (const [key, value] of Object.entries(props.filters ?? {})) {
        if (value !== '' && value != null) {
            query[key] = value;
        }
    }

    return query;
}

async function loadPage(page = 1) {
    loading.value = true;

    try {
        const response = await fetch(
            historyRoute.url({ query: buildQuery(page) }),
            {
                headers: { Accept: 'application/json' },
            },
        );
        const { data } = await response.json();

        transactions.value = data.transactions;
        pagination.value = data.pagination;
    } finally {
        loading.value = false;
    }
}

const confirmTarget = ref<Transaction | null>(null);
const reverting = ref(false);

function onDialogOpenChange(value: boolean) {
    if (!value) {
        confirmTarget.value = null;
    }
}

async function confirmReverse() {
    if (!confirmTarget.value) {
        return;
    }

    reverting.value = true;

    try {
        await http.post(wallet.reversals.store().url, {
            transaction: confirmTarget.value.hash,
        });

        toast.success('Transação revertida com sucesso');
        confirmTarget.value = null;
        await Promise.all([
            loadPage(pagination.value?.current_page ?? 1),
            loadBalance(),
        ]);
    } catch (error) {
        const message = isAxiosError(error)
            ? error.response?.data?.errors?.transaction?.[0]
            : undefined;

        toast.error(message ?? 'Não foi possível reverter a transação');
    } finally {
        reverting.value = false;
    }
}

watch(
    () => props.filters,
    () => loadPage(1),
    { deep: true },
);

onMounted(() => {
    loadPage();
    loadBalance();
});
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between gap-4">
            <CardTitle>Histórico de transações</CardTitle>
            <slot name="actions" />
        </CardHeader>
        <CardContent class="flex flex-col gap-4">
            <Skeleton v-if="loading" class="h-64 w-full" />

            <p
                v-else-if="transactions.length === 0"
                class="text-sm text-muted-foreground"
            >
                Nenhuma transação encontrada.
            </p>

            <TooltipProvider v-else>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Hash</TableHead>
                            <TableHead>Valor</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Revertido</TableHead>
                            <TableHead>Recebedor</TableHead>
                            <TableHead>Data de execução</TableHead>
                            <TableHead class="text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="transaction in transactions"
                            :key="transaction.hash"
                        >
                            <TableCell class="font-mono">{{
                                transaction.hash
                            }}</TableCell>
                            <TableCell
                                class="font-mono tabular-nums"
                                :class="
                                    transaction.type === MOVEMENT_TYPE_NEGATIVE
                                        ? 'text-expense'
                                        : 'text-income'
                                "
                            >
                                {{
                                    transaction.type === MOVEMENT_TYPE_NEGATIVE
                                        ? '-'
                                        : '+'
                                }}{{ formatCurrency(transaction.amount) }}
                            </TableCell>
                            <TableCell>{{
                                transaction.operation_type
                            }}</TableCell>
                            <TableCell>
                                <Badge
                                    :variant="
                                        transaction.reversed
                                            ? 'destructive'
                                            : 'secondary'
                                    "
                                >
                                    {{ transaction.reversed ? 'Sim' : 'Não' }}
                                </Badge>
                            </TableCell>
                            <TableCell>{{ transaction.receiver }}</TableCell>
                            <TableCell>{{
                                formatDateTime(transaction.created_at)
                            }}</TableCell>
                            <TableCell class="text-right">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span class="inline-flex">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                :disabled="
                                                    !canReverse(transaction)
                                                "
                                                @click="
                                                    confirmTarget = transaction
                                                "
                                                >Reverter</Button
                                            >
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        v-if="!canReverse(transaction)"
                                    >
                                        {{ reverseDisabledReason(transaction) }}
                                    </TooltipContent>
                                </Tooltip>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div
                    v-if="pagination"
                    class="flex flex-col items-center justify-between gap-3 sm:flex-row"
                >
                    <p class="text-sm text-muted-foreground">
                        Página {{ pagination.current_page }} de
                        {{ pagination.last_page }}
                        <span class="hidden sm:inline"
                            >· {{ pagination.total }} transações</span
                        >
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="pagination.current_page <= 1"
                            @click="loadPage(pagination.current_page - 1)"
                        >
                            Anterior
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="
                                pagination.current_page >= pagination.last_page
                            "
                            @click="loadPage(pagination.current_page + 1)"
                        >
                            Próxima
                        </Button>
                    </div>
                </div>
            </TooltipProvider>
        </CardContent>
    </Card>

    <Dialog :open="confirmTarget !== null" @update:open="onDialogOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Reverter transação</DialogTitle>
                <DialogDescription>
                    Esta ação criará lançamentos de reversão e não poderá ser
                    desfeita.
                </DialogDescription>
            </DialogHeader>

            <div v-if="confirmTarget" class="flex flex-col gap-1 text-sm">
                <p>
                    <span class="text-muted-foreground">Operação:</span>
                    {{ confirmTarget.operation_type }}
                </p>
                <p>
                    <span class="text-muted-foreground">Valor:</span>
                    {{ formatCurrency(confirmTarget.amount) }}
                </p>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    variant="outline"
                    :disabled="reverting"
                    @click="confirmTarget = null"
                >
                    Cancelar
                </Button>
                <Button
                    variant="destructive"
                    :disabled="reverting"
                    @click="confirmReverse"
                >
                    <Spinner v-if="reverting" />
                    Reverter
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
