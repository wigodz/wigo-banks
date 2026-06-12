<script setup>
import { Link } from '@inertiajs/vue3';
import { IconArrowUpCircle, IconDollar, IconExchange } from '@/components/atoms/icons';
import { AppLogo } from '@/components/molecules';
import { Card, CardContent } from '@/components/ui/card';
import { home } from '@/routes';

const highlights = [
    { icon: IconDollar, label: 'Depósitos instantâneos' },
    { icon: IconExchange, label: 'Transferências sem custo' },
    { icon: IconArrowUpCircle, label: 'Extrato sempre atualizado' },
];
</script>

<template>
    <div class="grid min-h-svh lg:grid-cols-2">
        <div class="relative hidden flex-col justify-between overflow-hidden bg-foreground p-10 text-background lg:flex">
            <div
                class="pointer-events-none absolute -top-24 -left-24 size-80 rounded-full bg-primary/30 blur-3xl"
                aria-hidden="true"
            />
            <div
                class="pointer-events-none absolute bottom-0 right-0 size-96 rounded-full bg-income/20 blur-3xl"
                aria-hidden="true"
            />

            <Link :href="home()" class="relative z-10 flex items-center gap-2">
                <AppLogo />
            </Link>

            <div class="relative z-10 max-w-md space-y-6">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Seu dinheiro, sob seu controle.
                </h1>
                <p class="text-background/70">
                    Acompanhe seu saldo, faça transferências e mantenha o controle das suas
                    movimentações financeiras em um só lugar.
                </p>

                <ul class="space-y-3">
                    <li
                        v-for="highlight in highlights"
                        :key="highlight.label"
                        class="flex items-center gap-3 text-sm font-medium text-background/90"
                    >
                        <span class="flex size-9 items-center justify-center rounded-lg bg-background/10 text-primary">
                            <component :is="highlight.icon" class="size-5" />
                        </span>
                        {{ highlight.label }}
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-background/50">
                &copy; {{ new Date().getFullYear() }} Wigo Banks. Todos os direitos reservados.
            </p>
        </div>

        <div class="flex flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <Link :href="home()" class="flex items-center gap-2 self-center font-medium lg:hidden">
                <AppLogo />
            </Link>

            <div class="w-full max-w-md">
                <Card>
                    <CardContent class="p-6 sm:p-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
