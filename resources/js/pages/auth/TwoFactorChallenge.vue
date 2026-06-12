<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Heading, InputError, TextLink } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { confirm } from '@/routes/two-factor';

const form = useForm({
    code: '',
});

const digits = ref(Array(6).fill(''));
const inputs = ref([]);

function setInput(el, index) {
    inputs.value[index] = el;
}

function syncCode() {
    form.code = digits.value.join('');
}

function onInput(index, event) {
    const value = event.target.value.replace(/[^a-zA-Z0-9]/g, '').slice(-1).toUpperCase();
    digits.value[index] = value;
    event.target.value = value;

    if (value && index < digits.value.length - 1) {
        inputs.value[index + 1]?.focus();
    }

    syncCode();
}

function onKeydown(index, event) {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        inputs.value[index - 1]?.focus();
    }
}

function onPaste(event) {
    const pasted = event.clipboardData?.getData('text').replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, digits.value.length);

    if (!pasted) {
        return;
    }

    event.preventDefault();

    pasted.split('').forEach((char, index) => {
        digits.value[index] = char;

        if (inputs.value[index]) {
            inputs.value[index].value = char;
        }
    });

    syncCode();
    inputs.value[Math.min(pasted.length, digits.value.length - 1)]?.focus();
}

function reset() {
    digits.value = Array(6).fill('');
    inputs.value.forEach((input) => {
        if (input) {
            input.value = '';
        }
    });
    inputs.value[0]?.focus();
}

function submit() {
    form.post(confirm().url, {
        onError: reset,
    });
}
</script>

<template>
    <Head title="Verificação em duas etapas" />

    <Heading
        title="Verificação em duas etapas"
        description="Enviamos um código de 6 dígitos para o seu e-mail. Informe-o abaixo para continuar."
    />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <div class="flex justify-between gap-2" @paste="onPaste">
                <input
                    v-for="(digit, index) in digits"
                    :key="index"
                    :ref="(el) => setInput(el, index)"
                    type="text"
                    inputmode="text"
                    maxlength="1"
                    autocomplete="one-time-code"
                    :autofocus="index === 0"
                    :aria-invalid="!!form.errors.code"
                    :value="digit"
                    class="h-12 w-12 rounded-md border border-input bg-transparent text-center text-lg font-semibold text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20"
                    @input="onInput(index, $event)"
                    @keydown="onKeydown(index, $event)"
                >
            </div>
            <InputError :message="form.errors.code" />
        </div>

        <Button type="submit" class="w-full" :disabled="form.processing">
            <Spinner v-if="form.processing" />
            Confirmar código
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            <TextLink :href="login()">Voltar para o login</TextLink>
        </div>
    </form>
</template>
