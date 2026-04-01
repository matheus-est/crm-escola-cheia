<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(), {
    breadcrumbs: () => [],
});

// — Inline loading bar (no external deps) —
let bar: HTMLDivElement | null = null;
let ticker: ReturnType<typeof setTimeout> | null = null;

function createBar() {
    if (bar) return;
    bar = document.createElement('div');
    bar.style.cssText = [
        'position:fixed',
        'top:0',
        'left:0',
        'height:2px',
        'width:0%',
        'z-index:9999',
        'background:hsl(var(--primary))',
        'border-radius:0 2px 2px 0',
        'opacity:1',
        'transition:width 0.25s ease,opacity 0.3s ease',
        'pointer-events:none',
    ].join(';');
    document.body.appendChild(bar);
}

function start() {
    createBar();
    if (!bar) return;
    bar.style.width = '0%';
    bar.style.opacity = '1';
    requestAnimationFrame(() => {
        if (bar) bar.style.width = '50%';
    });
    ticker = setTimeout(() => {
        if (bar) bar.style.width = '75%';
    }, 400);
}

function finish() {
    if (ticker) clearTimeout(ticker);
    if (!bar) return;
    bar.style.width = '100%';
    setTimeout(() => {
        if (!bar) return;
        bar.style.opacity = '0';
        setTimeout(() => {
            bar?.remove();
            bar = null;
        }, 300);
    }, 100);
}

onMounted(() => {
    router.on('start', start);
    router.on('finish', finish);
});

onUnmounted(() => {
    bar?.remove();
    bar = null;
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
