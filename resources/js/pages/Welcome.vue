<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { IconArrowDownCircle, IconArrowUpCircle, IconDollar, IconExchange } from '@/components/atoms/icons';
import { AppLogo } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

defineProps({
    auth: {
        type: Object,
        default: () => ({}),
    },
});

const features = [
    {
        icon: IconDollar,
        title: 'Depósitos',
        description: 'Adicione saldo à sua conta de forma rápida e acompanhe a confirmação em tempo real.',
    },
    {
        icon: IconExchange,
        title: 'Transferências',
        description: 'Envie e receba valores entre contas com poucos cliques, sem burocracia.',
    },
    {
        icon: null,
        title: 'Movimentações',
        description: 'Acompanhe seu extrato completo, com entradas e saídas sempre organizadas.',
    },
];

const transactions = [
    { description: 'Salário', date: 'Hoje, 09:14', amount: 'R$ 4.200,00', type: 'income' },
    { description: 'Aluguel', date: 'Ontem, 18:02', amount: 'R$ 1.350,00', type: 'expense' },
    { description: 'Transferência recebida', date: '08 jun', amount: 'R$ 320,00', type: 'income' },
];
</script>

<template>
    <Head title="Bem-vindo" />

    <div class="flex min-h-svh flex-col bg-background">
        <section class="relative overflow-hidden">
            <div
                class="pointer-events-none absolute -top-32 -left-32 size-96 rounded-full bg-primary/10 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute top-1/3 -right-24 size-80 rounded-full bg-income/10 blur-3xl"
                aria-hidden="true"
            />

            <div class="relative mx-auto grid max-w-6xl gap-12 px-6 py-16 sm:py-24 lg:grid-cols-2 lg:items-center lg:gap-8">
                <div class="flex flex-col items-start gap-6 text-left">
                    <div class="animate-fade-up flex items-center gap-2" style="animation-delay: 0ms">
                        <AppLogo />
                    </div>

                    <span
                        class="animate-fade-up rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold tracking-wide text-primary uppercase"
                        style="animation-delay: 60ms"
                    >
                        Gestão financeira pessoal
                    </span>

                    <h1
                        class="animate-fade-up max-w-lg text-4xl font-bold tracking-tight text-foreground sm:text-5xl"
                        style="animation-delay: 120ms"
                    >
                        Seu dinheiro, sob seu controle.
                    </h1>

                    <p class="animate-fade-up max-w-md text-lg text-muted-foreground" style="animation-delay: 180ms">
                        Faça depósitos, transferências e acompanhe cada movimentação da sua conta
                        em um só lugar — com clareza e sem complicação.
                    </p>

                    <div class="animate-fade-up flex flex-wrap items-center gap-3" style="animation-delay: 240ms">
                        <Button v-if="auth?.user" size="lg" as-child>
                            <Link :href="dashboard()">Ir para o painel</Link>
                        </Button>
                        <template v-else>
                            <Button size="lg" as-child>
                                <Link :href="login()">Entrar</Link>
                            </Button>
                            <Button size="lg" variant="outline" as-child>
                                <Link :href="register()">Criar conta</Link>
                            </Button>
                        </template>
                    </div>
                </div>

                <div class="animate-fade-up flex justify-center lg:justify-end" style="animation-delay: 300ms">
                    <div class="animate-float w-full max-w-sm space-y-3 rounded-2xl bg-card p-4 shadow-card sm:p-5">
                        <p class="text-sm font-medium text-muted-foreground">Saldo disponível</p>
                        <p class="font-mono text-4xl font-bold tracking-tight text-foreground tabular-nums">
                            R$ 12.480,50
                        </p>

                        <div class="space-y-2 pt-2">
                            <div
                                v-for="transaction in transactions"
                                :key="transaction.description"
                                class="flex items-center justify-between gap-4 rounded-lg bg-background px-3 py-2.5"
                            >
                                <div class="flex items-center gap-3">
                                    <component
                                        :is="transaction.type === 'income' ? IconArrowUpCircle : IconArrowDownCircle"
                                        :class="[
                                            'size-7',
                                            transaction.type === 'income' ? 'text-income' : 'text-expense',
                                        ]"
                                    />
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ transaction.description }}</p>
                                        <p class="text-xs text-muted-foreground">{{ transaction.date }}</p>
                                    </div>
                                </div>
                                <p
                                    :class="[
                                        'font-mono text-sm font-semibold tabular-nums',
                                        transaction.type === 'income' ? 'text-income' : 'text-expense',
                                    ]"
                                >
                                    {{ transaction.type === 'income' ? '+' : '-' }}{{ transaction.amount }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-border bg-card px-6 py-16 sm:py-24">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-2xl space-y-2 text-center">
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                        Tudo o que você precisa para cuidar do seu dinheiro
                    </h2>
                    <p class="text-muted-foreground">
                        Centralize depósitos, transferências e o histórico das suas movimentações
                        com total clareza.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-3">
                    <Card
                        v-for="feature in features"
                        :key="feature.title"
                        class="transition-transform hover:-translate-y-1"
                    >
                        <CardHeader>
                            <div class="mb-2 flex items-center gap-2">
                                <div
                                    v-if="feature.icon"
                                    class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <component :is="feature.icon" class="size-5" />
                                </div>
                                <div v-else class="flex items-center -space-x-2">
                                    <IconArrowUpCircle class="size-7 text-income" />
                                    <IconArrowDownCircle class="size-7 text-expense" />
                                </div>
                            </div>
                            <CardTitle>{{ feature.title }}</CardTitle>
                            <CardDescription>{{ feature.description }}</CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </div>
        </section>

        <footer class="border-t border-border px-6 py-8">
            <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-4 text-sm text-muted-foreground sm:flex-row">
                <div class="flex items-center gap-2">
                    <AppLogo />
                </div>
                <p>&copy; {{ new Date().getFullYear() }} Wigo Banks. Todos os direitos reservados.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fade-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0) rotate(-1.5deg);
    }
    50% {
        transform: translateY(-12px) rotate(-0.5deg);
    }
}

.animate-fade-up {
    animation: fade-up 0.6s ease-out both;
}

.animate-float {
    animation: float 7s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .animate-fade-up,
    .animate-float {
        animation: none;
    }
}
</style>
