<script setup lang="ts">
import { SlidersHorizontal } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import http from '@/lib/http';
import { recipients as recipientsRoute } from '@/routes/wallet';

const ALL = 'all';

type Recipient = { hash: string; name: string };

export type TransactionFilterValues = {
    operation_type: string;
    type: string;
    date_from: string;
    date_to: string;
    receiver: string;
};

const emit = defineEmits<{
    update: [filters: TransactionFilterValues];
}>();

const operationTypeOptions = [
    { value: '1', label: 'Depósito' },
    { value: '2', label: 'Transferência' },
    { value: '4', label: 'Saque' },
    { value: '3', label: 'Reversão de movimentação' },
];

const movementTypeOptions = [
    { value: '1', label: 'Entrada' },
    { value: '0', label: 'Saída' },
];

const open = ref(false);
const operationType = ref(ALL);
const movementType = ref(ALL);
const dateFrom = ref('');
const dateTo = ref('');
const receiver = ref(ALL);

const recipients = ref<Recipient[]>([]);
const loadingRecipients = ref(false);

onMounted(async () => {
    loadingRecipients.value = true;

    try {
        const { data } = await http.get(recipientsRoute.url());
        recipients.value = data.data.recipients;
    } finally {
        loadingRecipients.value = false;
    }
});

function toValue(value: string) {
    return value === ALL ? '' : value;
}

function apply() {
    emit('update', {
        operation_type: toValue(operationType.value),
        type: toValue(movementType.value),
        date_from: dateFrom.value,
        date_to: dateTo.value,
        receiver: toValue(receiver.value),
    });

    open.value = false;
}

function clear() {
    operationType.value = ALL;
    movementType.value = ALL;
    dateFrom.value = '';
    dateTo.value = '';
    receiver.value = ALL;
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button variant="outline">
                <SlidersHorizontal />
                Filtrar
            </Button>
        </SheetTrigger>

        <SheetContent class="w-full sm:max-w-sm">
            <SheetHeader>
                <SheetTitle>Filtros</SheetTitle>
            </SheetHeader>

            <form
                class="flex flex-1 flex-col gap-4 overflow-y-auto px-4"
                @submit.prevent="apply"
            >
                <div class="flex flex-col gap-2">
                    <Label for="filter-operation-type">Tipo de operação</Label>
                    <Select v-model="operationType">
                        <SelectTrigger
                            id="filter-operation-type"
                            class="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Todos</SelectItem>
                            <SelectItem
                                v-for="option in operationTypeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="filter-movement-type">Movimentação</Label>
                    <Select v-model="movementType">
                        <SelectTrigger id="filter-movement-type" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Todas</SelectItem>
                            <SelectItem
                                v-for="option in movementTypeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="filter-date-from">Data de</Label>
                    <Input
                        id="filter-date-from"
                        v-model="dateFrom"
                        type="date"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="filter-date-to">Data até</Label>
                    <Input id="filter-date-to" v-model="dateTo" type="date" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="filter-receiver">Recebedor</Label>
                    <Select v-model="receiver">
                        <SelectTrigger id="filter-receiver" class="w-full">
                            <SelectValue
                                :placeholder="
                                    loadingRecipients
                                        ? 'Carregando...'
                                        : 'Todos'
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL">Todos</SelectItem>
                            <SelectItem
                                v-for="recipient in recipients"
                                :key="recipient.hash"
                                :value="recipient.hash"
                            >
                                {{ recipient.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </form>

            <SheetFooter>
                <Button type="button" @click="apply">Filtrar</Button>
                <Button type="button" variant="outline" @click="clear">
                    Limpar
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
