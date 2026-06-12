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
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/format';
import http from '@/lib/http';
import deposits from '@/routes/wallet/deposits';

const emit = defineEmits<{
    confirmed: [];
}>();

const open = ref(false);
const step = ref<'amount' | 'confirm'>('amount');
const amountCents = ref(0);
const amountDisplay = computed(() => formatCurrency(amountCents.value));
const errors = ref<{ amount?: string }>({});
const submitting = ref(false);

watch(open, (value) => {
    if (!value) {
        step.value = 'amount';
        amountCents.value = 0;
        errors.value = {};
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

    if (amountCents.value > 9999999) {
        errors.value.amount = 'O valor do depósito não pode ser igual ou superior a R$ 100.000,00';

        return;
    }

    step.value = 'confirm';
}

async function submitDeposit() {
    errors.value = {};
    submitting.value = true;

    try {
        await http.post(deposits.store().url, { amount: amountCents.value });

        open.value = false;
        toast.success('Depósito realizado com sucesso');
        emit('confirmed');
    } catch (error) {
        const message = isAxiosError(error) ? error.response?.data?.errors?.amount?.[0] : undefined;
        errors.value.amount = message ?? 'Não foi possível realizar o depósito';
        step.value = 'amount';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">Depositar</Button>
        </DialogTrigger>

        <DialogContent>
            <template v-if="step === 'amount'">
                <DialogHeader>
                    <DialogTitle>Realizar depósito</DialogTitle>
                    <DialogDescription>Informe o valor que deseja depositar na sua conta.</DialogDescription>
                </DialogHeader>

                <form class="flex flex-col gap-2" @submit.prevent="continueToConfirmation">
                    <Label for="deposit-amount">Valor</Label>
                    <Input
                        id="deposit-amount"
                        type="text"
                        inputmode="numeric"
                        :model-value="amountDisplay"
                        placeholder="R$ 0,00"
                        :aria-invalid="!!errors.amount"
                        autofocus
                        @update:model-value="onAmountInput"
                    />
                    <InputError :message="errors.amount" />

                    <DialogFooter class="mt-4">
                        <Button type="submit">Continuar</Button>
                    </DialogFooter>
                </form>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>Confirme o depósito</DialogTitle>
                    <DialogDescription>Confira o valor antes de concluir o depósito.</DialogDescription>
                </DialogHeader>

                <div class="flex flex-col gap-2">
                    <p class="text-muted-foreground text-sm">Valor a depositar</p>
                    <p class="font-mono text-2xl font-semibold">{{ amountDisplay }}</p>
                    <InputError :message="errors.amount" />
                </div>

                <DialogFooter class="mt-4 gap-2">
                    <Button variant="outline" :disabled="submitting" @click="step = 'amount'">Voltar</Button>
                    <Button :disabled="submitting" @click="submitDeposit">
                        <Spinner v-if="submitting" />
                        Confirmar depósito
                    </Button>
                </DialogFooter>
            </template>
        </DialogContent>
    </Dialog>
</template>
