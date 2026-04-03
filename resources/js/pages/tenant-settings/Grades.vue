<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Construction } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Turmas', href: '#' }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Turmas" />

        <div class="space-y-6">
            <Heading title="Turmas" />

            <div
                class="flex flex-col items-center justify-center rounded-lg border border-dashed py-20 text-center"
            >
                <div class="mb-4 rounded-full bg-muted/50 p-5">
                    <Construction class="h-10 w-10 text-muted-foreground/50" />
                </div>
                <p class="text-base font-semibold text-foreground">
                    Em desenvolvimento
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Esta funcionalidade estará disponível em breve.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
