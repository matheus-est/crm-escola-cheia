<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Filter, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import TablePagination from '@/components/TablePagination.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, index } from '@/routes/tenant/events/index';
import type { BreadcrumbItem } from '@/types';
import type { Event, PaginatedEvents } from '@/types/crm';

const props = defineProps<{
    events: PaginatedEvents;
    filters: {
        title?: string;
    };
}>();

const toast = useToast();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Eventos', href: '#' }];

const localFilters = ref({
    title: props.filters.title ?? '',
    per_page: 10,
});

const hasActiveFilter = computed(() => !!localFilters.value.title);

const filterCount = computed(() =>
    [localFilters.value.title ? 1 : 0].reduce((a, b) => a + b, 0),
);

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            title: localFilters.value.title,
            per_page: localFilters.value.per_page,
        },
        { preserveUrl: true },
    );
}

function clearFilters(): void {
    router.visit(index().url);
}

function formatDate(event: Event): string {
    if (event.has_no_date) return 'Sem data';
    if (!event.event_date) return '—';
    return new Date(event.event_date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Delete
const showDeleteConfirm = ref(false);
const eventToDelete = ref<Event | null>(null);

function openDeleteConfirm(event: Event): void {
    eventToDelete.value = event;
    showDeleteConfirm.value = true;
}

function cancelDelete(): void {
    showDeleteConfirm.value = false;
    eventToDelete.value = null;
}

function confirmDelete(): void {
    if (!eventToDelete.value) return;

    router.delete(destroy({ event: eventToDelete.value.uuid }).url, {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            eventToDelete.value = null;
            toast.success('Evento excluído com sucesso.');
            router.reload({ preserveUrl: true });
        },
        onError: () => {
            toast.error('Erro ao excluir o evento.');
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Eventos" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Eventos" />

                <Accordion
                    type="single"
                    collapsible
                    class="w-72"
                    defaultValue="closed"
                >
                    <AccordionItem value="filter" class="border-none">
                        <AccordionTrigger
                            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:no-underline"
                            :class="
                                hasActiveFilter
                                    ? 'border-primary/40 bg-primary/5 text-primary dark:bg-primary/10'
                                    : 'border-border bg-background hover:bg-muted/50'
                            "
                        >
                            <Filter class="h-4 w-4 shrink-0" />
                            <span>Filtrar</span>
                            <span
                                v-if="hasActiveFilter"
                                class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs text-primary-foreground"
                            >
                                {{ filterCount }}
                            </span>
                        </AccordionTrigger>

                        <AccordionContent class="pt-2">
                            <div
                                class="rounded-lg border bg-card p-4 shadow-sm"
                            >
                                <form
                                    class="space-y-4"
                                    @submit.prevent="applyFilters"
                                >
                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-title"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Título
                                        </Label>
                                        <Input
                                            id="filter-title"
                                            v-model="localFilters.title"
                                            placeholder="Buscar por título..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div
                                        class="flex items-center justify-between gap-2 pt-1"
                                    >
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            type="button"
                                            class="h-8 text-muted-foreground"
                                            @click="clearFilters"
                                        >
                                            Limpar filtros
                                        </Button>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            class="h-8"
                                        >
                                            Aplicar
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>

            <!-- Table header actions -->
            <div class="flex items-center justify-between">
                <Link
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Novo Evento
                </Link>

                <PerPageSelect
                    :model-value="String(localFilters.per_page)"
                    @update:model-value="updatePerPage"
                />
            </div>

            <!-- Table -->
            <div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Título
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Data do Evento
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Local
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Capacidade Máx.
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Oportunidades
                            </th>
                            <th
                                class="px-3 pb-3 text-right font-medium text-muted-foreground"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="event in props.events.data"
                            :key="event.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ event.title }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ formatDate(event) }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ event.location ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ event.max_capacity ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ event.opportunities_count ?? 0 }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="edit({ event: event.uuid }).url"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(event)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="props.events.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <CalendarDays
                            class="h-8 w-8 text-muted-foreground/50"
                        />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum evento encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando um novo evento.'
                        }}
                    </p>
                    <Link
                        v-if="!hasActiveFilter"
                        :href="create().url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Novo Evento
                    </Link>
                </div>
            </div>

            <TablePagination :paginator="props.events" />
        </div>

        <!-- Inline delete confirmation dialog -->
        <div
            v-if="showDeleteConfirm && eventToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg"
            >
                <h3 class="text-base font-semibold">Confirmar Exclusão</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tem certeza que deseja excluir o evento
                    <span class="font-medium text-foreground">{{
                        eventToDelete.title
                    }}</span
                    >? Esta ação não pode ser desfeita.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <Button variant="outline" @click="cancelDelete">
                        Cancelar
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Excluir
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
