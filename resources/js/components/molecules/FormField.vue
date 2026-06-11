<script setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps({
    id: { type: String, required: true },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="grid gap-2">
        <Label v-if="label" :for="id">{{ label }}</Label>
        <Input
            :id="id"
            :type="type"
            :placeholder="placeholder"
            :model-value="modelValue"
            :aria-invalid="!!error"
            @update:model-value="$emit('update:modelValue', $event)"
        />
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
