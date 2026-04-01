<script setup lang="ts">
import { router } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

defineProps<{
    paginator: Paginator;
}>();

function goToPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveScroll: true });
}

function rangeStart(paginator: Paginator): number {
    return (paginator.current_page - 1) * paginator.per_page + 1;
}

function rangeEnd(paginator: Paginator): number {
    return Math.min(
        paginator.current_page * paginator.per_page,
        paginator.total,
    );
}
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex items-center justify-between text-sm text-muted-foreground"
    >
        <span>
            Mostrando
            <span class="font-medium text-foreground"
                >{{ rangeStart(paginator) }}–{{ rangeEnd(paginator) }}</span
            >
            de
            <span class="font-medium text-foreground">{{
                paginator.total
            }}</span>
            registros
        </span>

        <div class="flex items-center gap-1">
            <button
                v-for="link in paginator.links"
                :key="link.label"
                :disabled="!link.url"
                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors"
                :class="[
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background hover:bg-muted',
                    !link.url
                        ? 'cursor-not-allowed opacity-40'
                        : 'cursor-pointer',
                ]"
                @click="goToPage(link.url)"
                v-html="link.label"
            />
        </div>
    </div>
</template>
