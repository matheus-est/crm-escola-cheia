<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarCog,
    Filter,
    Pencil,
    Plus,
    RotateCcw,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import EventTypeFormDialog from '@/components/EventTypeFormDialog.vue';
import EventTypeToggleDialog from '@/components/EventTypeToggleDialog.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/tenant/settings/event_types/index';
import type { BreadcrumbItem } from '@/types';
import type { EventType, PaginatedEventTypes } from '@/types/crm';

interface Filters {
    name: string;
    is_active: string;
    per_page: number;
}

const props = defineProps<{
    event_types: PaginatedEventTypes;
    filters: Filters;
}>();

const toast = useToast();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Tipos de Evento', href: index().url },
];

const localFilters = ref<Filters>({
    name: props.filters.name || '',
    is_active: props.filters.is_active || '',
    per_page: Number(props.filters.per_page ?? 10),
});

const filterCount = computed(() =>
    [
        localFilters.value.name ? 1 : 0,
        localFilters.value.is_active ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.visit(index().url, {
        data: {
            name: localFilters.value.name,
            is_active: localFilters.value.is_active,
            per_page: localFilters.value.per_page,
        },
        preserveUrl: true,
    });
}

function clearFilters(): void {
    router.visit(index().url);
}

// — Dialog form (create / edit) —
type FormMode = 'create' | 'edit';

const formMode = ref<FormMode>('create');
const editingEventType = ref<EventType | null>(null);
const showDialog = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingEventType.value = null;
    showDialog.value = true;
}

function openEditForm(eventType: EventType): void {
    formMode.value = 'edit';
    editingEventType.value = eventType;
    showDialog.value = true;
}

function handleDialogSuccess(): void {
    showDialog.value = false;
    editingEventType.value = null;
    toast.success(
        formMode.value === 'create'
            ? 'Tipo de evento criado com sucesso.'
            : 'Tipo de evento atualizado com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

// — Toggle active —
const showToggleDialog = ref(false);
const toggleTarget = ref<EventType | null>(null);

function openToggleDialog(eventType: EventType): void {
    toggleTarget.value = eventType;
    showToggleDialog.value = true;
}

function handleToggleSuccess(): void {
    showToggleDialog.value = false;
    toggleTarget.value = null;
    router.reload({ preserveUrl: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tipos de Evento" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Tipos de Evento" />

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
                                            for="filter-name"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Nome
                                        </Label>
                                        <Input
                                            id="filter-name"
                                            v-model="localFilters.name"
                                            placeholder="Buscar por nome..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-is_active"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Status
                                        </Label>
                                        <Select
                                            v-model="localFilters.is_active"
                                        >
                                            <SelectTrigger
                                                id="filter-is_active"
                                                class="h-8"
                                            >
                                                <SelectValue
                                                    placeholder="Todos"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="1">
                                                    Ativo
                                                </SelectItem>
                                                <SelectItem value="0">
                                                    Inativo
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
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
                <Button
                    class="inline-flex items-center gap-2"
                    @click="openCreateForm"
                >
                    <Plus class="h-4 w-4" />
                    Novo Tipo de Evento
                </Button>

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
                                Nome
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Status
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
                            v-for="eventType in props.event_types.data"
                            :key="eventType.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ eventType.name }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    v-if="eventType.is_active"
                                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20"
                                >
                                    Ativo
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-500/10 ring-inset dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20"
                                >
                                    Inativo
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                        @click="openEditForm(eventType)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="eventType.is_active"
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Inativar"
                                        @click="openToggleDialog(eventType)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="rounded p-1 text-green-600 hover:bg-muted"
                                        title="Reativar"
                                        @click="openToggleDialog(eventType)"
                                    >
                                        <RotateCcw class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="props.event_types.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <CalendarCog class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum tipo de evento encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando um novo tipo de evento.'
                        }}
                    </p>
                    <Button
                        v-if="!hasActiveFilter"
                        class="mt-4 inline-flex items-center gap-2"
                        @click="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Novo Tipo de Evento
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.event_types" />
        </div>

        <!-- Create / Edit Dialog -->
        <EventTypeFormDialog
            v-model:open="showDialog"
            :mode="formMode"
            :event-type="editingEventType"
            @success="handleDialogSuccess"
        />

        <!-- Toggle Active Dialog -->
        <EventTypeToggleDialog
            v-model:open="showToggleDialog"
            :event-type="toggleTarget"
            @success="handleToggleSuccess"
        />
    </AppLayout>
</template>
