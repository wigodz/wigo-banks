<script setup>
import { Link } from '@inertiajs/vue3';
import {
    IconExchange,
    IconHistory,
    IconHome,
    IconLogout,
} from '@/components/atoms/icons';
import { AppLogo } from '@/components/molecules';
import { useAuth } from '@/composables/useAuth';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { dashboard, historico, transferencias } from '@/routes';

const { logout } = useAuth();
const { isCurrentUrl } = useCurrentUrl();

const links = [
    { label: 'Dashboard', href: dashboard(), icon: IconHome },
    { label: 'Transferências', href: transferencias(), icon: IconExchange },
    { label: 'Histórico', href: historico(), icon: IconHistory },
];
</script>

<template>
    <aside class="flex w-64 flex-col gap-6 border-r bg-card px-4 py-6">
        <Link :href="dashboard()" class="flex items-center gap-2 px-2">
            <AppLogo />
        </Link>

        <nav class="flex flex-1 flex-col gap-1">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                :class="
                    cn(
                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                        isCurrentUrl(link.href)
                            ? 'bg-accent text-accent-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    )
                "
            >
                <component :is="link.icon" class="size-5" />
                {{ link.label }}
            </Link>
        </nav>

        <button
            type="button"
            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
            @click="logout"
        >
            <IconLogout class="size-5" />
            Sair
        </button>
    </aside>
</template>
