<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { AppLogo } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

defineProps({
    auth: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head title="Bem-vindo" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-8 bg-background p-6 text-center"
    >
        <div class="flex items-center gap-2">
            <AppLogo />
        </div>

        <div class="max-w-xl space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight">Wigo Banks</h1>
            <p class="text-muted-foreground">
                Gerencie seu dinheiro com recebimentos, transferências e
                controle total das suas finanças em um só lugar.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <Button v-if="auth?.user" as-child>
                <Link :href="dashboard()">Ir para o painel</Link>
            </Button>
            <template v-else>
                <Button as-child>
                    <Link :href="login()">Entrar</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="register()">Criar conta</Link>
                </Button>
            </template>
        </div>
    </div>
</template>
