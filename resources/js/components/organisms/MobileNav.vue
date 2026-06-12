<script setup lang="ts">
import { Menu } from '@lucide/vue';
import { ref } from 'vue';
import { SidebarNavContent } from '@/components/molecules';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

const open = ref(false);
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button variant="ghost" size="icon" class="lg:hidden">
                <Menu class="size-5" />
                <span class="sr-only">Abrir menu</span>
            </Button>
        </SheetTrigger>

        <SheetContent side="left" class="w-64 bg-card">
            <SheetHeader>
                <SheetTitle class="sr-only">Menu</SheetTitle>
            </SheetHeader>

            <div class="flex flex-1 flex-col gap-6 px-4 pb-6">
                <SidebarNavContent @navigate="open = false" />
            </div>
        </SheetContent>
    </Sheet>
</template>
