<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { InputError, TextLink } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Entrar na sua conta',
        description: 'Informe seu e-mail e senha para continuar',
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(store().url, {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Entrar" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Senha</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Senha"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" v-model="form.remember" />
                    <span>Lembrar de mim</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                Entrar
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Não tem uma conta?
            <TextLink :href="register()" class="ml-1">Criar conta</TextLink>
        </div>
    </form>
</template>
