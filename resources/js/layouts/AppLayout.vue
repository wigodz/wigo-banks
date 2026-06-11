<script setup>
import { Link } from '@inertiajs/vue3';
import { AppLogo } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/composables/useAuth';
import { home } from '@/routes';

const { user, logout } = useAuth();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3"
            >
                <Link :href="home()" class="flex items-center gap-2">
                    <AppLogo />
                </Link>

                <div class="flex items-center gap-4">
                    <span v-if="user" class="text-sm text-muted-foreground">
                        {{ user.name }}
                    </span>
                    <Button variant="outline" size="sm" @click="logout">
                        Sair
                    </Button>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
            <slot />
        </main>
    </div>
</template>
