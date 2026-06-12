<script setup>
import { MobileNav, SidebarNav } from '@/components/organisms';
import { useAuth } from '@/composables/useAuth';

const { user } = useAuth();
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <SidebarNav />

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center gap-3 border-b bg-card px-4 py-4 sm:px-6">
                <MobileNav />

                <p class="text-sm text-muted-foreground">
                    Bem-vindo(a),
                    <span class="font-semibold text-foreground">{{ user?.name }}</span>
                </p>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6">
                <slot />
            </main>

            <footer class="border-t px-4 py-4 text-center text-xs text-muted-foreground sm:px-6">
                © {{ new Date().getFullYear() }} Wigo Banks. Todos os direitos reservados.
            </footer>
        </div>
    </div>
</template>
