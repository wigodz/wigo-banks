<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';

defineProps<{
    label: string;
    value?: string;
    loading?: boolean;
}>();
</script>

<template>
    <Card class="gap-2">
        <CardHeader>
            <p class="text-sm font-medium text-muted-foreground">
                {{ label }}
            </p>
        </CardHeader>
        <CardContent>
            <Skeleton v-if="loading" class="h-10 w-40" />
            <p v-else class="font-mono text-4xl font-bold tracking-tight text-foreground tabular-nums">
                {{ value }}
            </p>
        </CardContent>
    </Card>
</template>
