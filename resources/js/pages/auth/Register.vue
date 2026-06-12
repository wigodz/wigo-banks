<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Heading, InputError, TextLink } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(store().url, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Criar conta" />

    <Heading title="Criar conta" description="Preencha os dados abaixo para criar sua conta" />

    <form class="flex flex-col gap-6" @submit.prevent="submit">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nome</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Seu nome"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-mail</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
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
                    autocomplete="new-password"
                    placeholder="Senha"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar senha</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="Confirmar senha"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                Criar conta
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Já tem uma conta?
            <TextLink :href="login()" class="ml-1">Entrar</TextLink>
        </div>
    </form>
</template>
