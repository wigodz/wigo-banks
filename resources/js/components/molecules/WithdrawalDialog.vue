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
import withdrawals from '@/routes/wallet/withdrawals';

const emit = defineEmits<{
    confirmed: [];
}>();

const open = ref(false);
const step = ref<'amount' | 'code'>('amount');
const amountCents = ref(0);
const amountDisplay = computed(() => formatCurrency(amountCents.value));
const code = ref('');
const errors = ref<{ amount?: string; code?: string }>({});
const submitting = ref(false);

watch(open, (value) => {
    if (!value) {
        step.value = 'amount';
        amountCents.value = 0;
        code.value = '';
        errors.value = {};
    }
});

function onAmountInput(value: string | number) {
    const digits = String(value).replace(/\D/g, '');
    amountCents.value = digits ? parseInt(digits, 10) : 0;
}

async function submitAmount() {
    errors.value = {};
    submitting.value = true;

    try {
        await http.post(withdrawals.store().url, { amount: amountCents.value });

        step.value = 'code';
        toast.success('Código de confirmação enviado para o seu e-mail');
    } catch (error) {
        const message = isAxiosError(error) ? error.response?.data?.errors?.amount?.[0] : undefined;
        errors.value.amount = message ?? 'Não foi possível solicitar o saque';
    } finally {
        submitting.value = false;
    }
}

async function submitCode() {
    errors.value = {};
    submitting.value = true;

    try {
        await http.post(withdrawals.confirm().url, { code: code.value });

        open.value = false;
        toast.success('Saque confirmado com sucesso');
        emit('confirmed');
    } catch (error) {
        const message = isAxiosError(error) ? error.response?.data?.errors?.code?.[0] : undefined;
        errors.value.code = message ?? 'Código de confirmação inválido';
        code.value = '';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">Sacar</Button>
        </DialogTrigger>

        <DialogContent>
            <template v-if="step === 'amount'">
                <DialogHeader>
                    <DialogTitle>Solicitar saque</DialogTitle>
                    <DialogDescription>
                        Informe o valor que deseja sacar. Enviaremos um código de confirmação para o seu e-mail.
                    </DialogDescription>
                </DialogHeader>

                <form class="flex flex-col gap-2" @submit.prevent="submitAmount">
                    <Label for="withdrawal-amount">Valor</Label>
                    <Input
                        id="withdrawal-amount"
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
                        <Button type="submit" :disabled="submitting">
                            <Spinner v-if="submitting" />
                            Continuar
                        </Button>
                    </DialogFooter>
                </form>
            </template>

            <template v-else>
                <DialogHeader>
                    <DialogTitle>Confirme o saque</DialogTitle>
                    <DialogDescription>
                        Enviamos um código de 12 caracteres para o seu e-mail. Informe-o abaixo para confirmar o saque.
                    </DialogDescription>
                </DialogHeader>

                <form class="flex flex-col gap-2" @submit.prevent="submitCode">
                    <Label for="withdrawal-code">Código de confirmação</Label>
                    <Input
                        id="withdrawal-code"
                        v-model="code"
                        type="text"
                        maxlength="12"
                        class="font-mono uppercase"
                        :aria-invalid="!!errors.code"
                        autofocus
                    />
                    <InputError :message="errors.code" />

                    <DialogFooter class="mt-4">
                        <Button type="submit" :disabled="submitting">
                            <Spinner v-if="submitting" />
                            Confirmar saque
                        </Button>
                    </DialogFooter>
                </form>
            </template>
        </DialogContent>
    </Dialog>
</template>
