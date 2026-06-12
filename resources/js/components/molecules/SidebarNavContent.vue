<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    IconExchange,
    IconHistory,
    IconHome,
    IconLogout,
} from '@/components/atoms/icons';
import AppLogo from '@/components/molecules/AppLogo.vue';
import { useAuth } from '@/composables/useAuth';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn } from '@/lib/utils';
import { dashboard, historico, transferencias } from '@/routes';

const emit = defineEmits<{
    navigate: [];
}>();

const { logout } = useAuth();
const { isCurrentUrl } = useCurrentUrl();

const links = [
    { label: 'Dashboard', href: dashboard(), icon: IconHome },
    { label: 'Transferências', href: transferencias(), icon: IconExchange },
    { label: 'Histórico', href: historico(), icon: IconHistory },
];
</script>

<template>
    <Link :href="dashboard()" class="flex items-center gap-2 px-2" @click="emit('navigate')">
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
            @click="emit('navigate')"
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
</template>
