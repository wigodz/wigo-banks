<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';

defineProps<{
    label?: string;
    balance: string;
}>();
</script>

<template>
    <Card class="gap-2">
        <CardHeader>
            <p class="text-sm font-medium text-muted-foreground">
                {{ label ?? 'Saldo disponível' }}
            </p>
        </CardHeader>
        <CardContent>
            <p class="font-mono text-4xl font-bold tracking-tight text-foreground tabular-nums">
                {{ balance }}
            </p>
        </CardContent>
    </Card>
</template>
