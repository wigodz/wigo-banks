<script setup lang="ts">
import { isAxiosError } from 'axios';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/molecules/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import http from '@/lib/http';
import wallet, { balance as balanceRoute, recipients as recipientsRoute } from '@/routes/wallet';

type Recipient = { hash: string; name: string };

const emit = defineEmits<{
    confirmed: [];
}>();

const open = ref(false);
const step = ref<'amount' | 'confirm'>('amount');
const amountCents = ref(0);
const amountDisplay = computed(() => formatCurrency(amountCents.value));
const receiver = ref<string>('');
const recipients = ref<Recipient[]>([]);
const loadingRecipients = ref(false);
const balanceCents = ref(0);
const errors = ref<{ amount?: string; receiver?: string }>({});
const submitting = ref(false);

const selectedRecipient = computed(() => recipients.value.find((recipient) => recipient.hash === receiver.value));

watch(open, async (value) => {
    if (!value) {
        step.value = 'amount';
        amountCents.value = 0;
        receiver.value = '';
        errors.value = {};

        return;
    }

    loadingRecipients.value = true;

    try {
        const [recipientsResponse, balanceResponse] = await Promise.all([
            http.get(recipientsRoute.url()),
            http.get(balanceRoute.url()),
        ]);

        recipients.value = recipientsResponse.data.data.recipients;
        balanceCents.value = balanceResponse.data.data.balance;
    } finally {
        loadingRecipients.value = false;
    }
});

function onAmountInput(value: string | number) {
    const digits = String(value).replace(/\D/g, '');
    amountCents.value = digits ? parseInt(digits, 10) : 0;
}

function continueToConfirmation() {
    errors.value = {};

    if (amountCents.value <= 0) {
        errors.value.amount = 'Informe um valor maior que zero';

        return;
    }

    if (amountCents.value > balanceCents.value) {
        errors.value.amount = 'O valor da transferência não pode exceder o saldo disponível';

        return;
    }

    if (!receiver.value) {
        errors.value.receiver = 'Selecione quem irá receber a transferência';

        return;
    }

    step.value = 'confirm';
}

async function submitTransfer() {
    errors.value = {};
    submitting.value = true;

    try {
        await http.post(wallet.transfers.store().url, { amount: amountCents.value, receiver: receiver.value });

        open.value = false;
        toast.success('Transferência realizada com sucesso');
        emit('confirmed');
    } catch (error) {
        const data = isAxiosError(error) ? error.response?.data?.errors : undefined;
        errors.value.amount = data?.amount?.[0];
        errors.value.receiver = data?.receiver?.[0];

        if (!errors.value.amount && !errors.value.receiver) {
            errors.value.amount = 'Não foi possível realizar a transferência';
        }

        step.value = 'amount';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">Transferir</Button>
        </DialogTrigger>

        <DialogContent>
            <template v-if="step === 'amount'">
                <DialogHeader>
                    <DialogTitle>Realizar transferência</DialogTitle>
                    <DialogDescription>Informe o valor e selecione quem irá receber a transferência.</DialogDescription>
                </DialogHeader>

                <form class="flex flex-col gap-2" @submit.prevent="continueToConfirmation">
                    <Label for="transfer-amount">Valor</Label>
                    <Input
                        id="transfer-amount"
                        type="text"
                        inputmode="numeric"
                        :model-value="amountDisplay"
                        placeholder="R$ 0,00"
                        :aria-invalid="!!errors.amount"
                        autofocus
                        @update:model-value="onAmountInput"
                    />
                    <InputError :message="errors.amount" />

                    <Label for="transfer-receiver" class="mt-2">Destinatário</Label>
                    <Select v-model="receiver">
                        <SelectTrigger id="transfer-receiver" class="w-full" :aria-invalid="!!errors.receiver">
                            <SelectValue :placeholder="loadingRecipients ? 'Carregando...' : 'Selecione um destinatário'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="recipient in recipients" :key="recipient.hash" :value="recipient.hash">
                                {{ recipient.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.receiver" />

                    <DialogFooter class="mt-4">
                        <Button type="submit">Continuar</Button>
                    </DialogFooter>
                </form>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>Confirme a transferência</DialogTitle>
                    <DialogDescription>Confira o valor e o destinatário antes de concluir a transferência.</DialogDescription>
                </DialogHeader>

                <div class="flex flex-col gap-2">
                    <p class="text-muted-foreground text-sm">Valor a transferir</p>
                    <p class="font-mono text-2xl font-semibold">{{ amountDisplay }}</p>

                    <p class="text-muted-foreground mt-2 text-sm">Destinatário</p>
                    <p class="text-lg font-semibold">{{ selectedRecipient?.name }}</p>

                    <InputError :message="errors.amount" />
                    <InputError :message="errors.receiver" />
                </div>

                <DialogFooter class="mt-4 gap-2">
                    <Button variant="outline" :disabled="submitting" @click="step = 'amount'">Voltar</Button>
                    <Button :disabled="submitting" @click="submitTransfer">
                        <Spinner v-if="submitting" />
                        Confirmar transferência
                    </Button>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
