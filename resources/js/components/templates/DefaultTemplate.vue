<script setup></script>

<template>
    <div class="flex flex-1 flex-col gap-6 p-4">
        <header v-if="$slots.header">
            <slot name="header" />
        </header>
        <main class="flex-1">
            <slot />
        </main>
        <footer v-if="$slots.footer">
            <slot name="footer" />
        </footer>
    </div>
</template>
