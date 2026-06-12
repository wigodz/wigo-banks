<script setup>
import { SidebarNav } from '@/components/organisms';
import { useAuth } from '@/composables/useAuth';

const { user } = useAuth();
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <SidebarNav />

        <div class="flex flex-1 flex-col">
            <header class="border-b bg-card px-6 py-4">
                <p class="text-sm text-muted-foreground">
                    Bem-vindo(a),
                    <span class="font-semibold text-foreground">{{ user?.name }}</span>
                </p>
            </header>

            <main class="flex-1 px-6 py-6">
                <slot />
            </main>

            <footer class="border-t px-6 py-4 text-center text-xs text-muted-foreground">
                © {{ new Date().getFullYear() }} Wigo Banks. Todos os direitos reservados.
            </footer>
        </div>
    </div>
</template>
