<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { PageHeader } from '@/components/organisms';
import api from '@/lib/api';

const me = ref(null);
const error = ref(null);

onMounted(async () => {
    try {
        const response = await api.get('/me');
        me.value = response.data.data;
    } catch {
        error.value = 'Não foi possível carregar os dados do usuário.';
    }
});
</script>

<template>
    <Head title="Painel" />

    <PageHeader title="Painel" description="Visão geral da sua conta" />

    <div class="mt-6">
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
        <div v-else-if="me" class="space-y-1 text-sm">
            <p><span class="font-medium">ID:</span> {{ me.id }}</p>
            <p><span class="font-medium">Nome:</span> {{ me.name }}</p>
            <p><span class="font-medium">E-mail:</span> {{ me.email }}</p>
        </div>
        <p v-else class="text-sm text-muted-foreground">Carregando...</p>
    </div>
</template>
