<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { DoorOpen, Filter, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import RoomDeleteDialog from '@/components/RoomDeleteDialog.vue';
import RoomFormDialog from '@/components/RoomFormDialog.vue';
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
import { index } from '@/routes/tenant/settings/rooms/index';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedRooms, Room } from '@/types/crm';

interface Filters {
    name: string;
    per_page: number;
}

const props = defineProps<{
    rooms: PaginatedRooms;
    filters: Filters;
}>();

const toast = useToast();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Salas', href: index().url },
];

const localFilters = ref<Filters>({
    name: props.filters.name || '',
    per_page: Number(props.filters.per_page ?? 10),
});

const filterCount = computed(() =>
    [localFilters.value.name ? 1 : 0].reduce((a, b) => a + b, 0),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            name: localFilters.value.name,
            per_page: localFilters.value.per_page,
        },
        { preserveUrl: true },
    );
}

function clearFilters(): void {
    router.visit(index().url);
}

// — Dialog form (create / edit) —
type FormMode = 'create' | 'edit';

const formMode = ref<FormMode>('create');
const editingRoom = ref<Room | null>(null);
const showDialog = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingRoom.value = null;
    showDialog.value = true;
}

function openEditForm(room: Room): void {
    formMode.value = 'edit';
    editingRoom.value = room;
    showDialog.value = true;
}

function handleDialogSuccess(): void {
    showDialog.value = false;
    editingRoom.value = null;
    toast.success(
        formMode.value === 'create'
            ? 'Sala criada com sucesso.'
            : 'Sala atualizada com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

// — Delete —
const showDeleteConfirm = ref(false);
const roomToDelete = ref<Room | null>(null);

function openDeleteConfirm(room: Room): void {
    roomToDelete.value = room;
    showDeleteConfirm.value = true;
}

function handleDeleteSuccess(): void {
    showDeleteConfirm.value = false;
    roomToDelete.value = null;
    router.reload({ preserveUrl: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Salas" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Salas" />

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
                    Nova Sala
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
                                Capacidade
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Externa
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
                            v-for="room in props.rooms.data"
                            :key="room.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ room.name }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ room.capacity ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    v-if="room.is_external"
                                    class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20"
                                >
                                    Sim
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-500/10 ring-inset dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20"
                                >
                                    Não
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
                                        @click="openEditForm(room)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(room)"
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
                    v-if="props.rooms.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <DoorOpen class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma sala encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando uma nova sala.'
                        }}
                    </p>
                    <Button
                        v-if="!hasActiveFilter"
                        class="mt-4 inline-flex items-center gap-2"
                        @click="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Sala
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.rooms" />
        </div>

        <!-- Create / Edit Dialog -->
        <RoomFormDialog
            v-model:open="showDialog"
            :mode="formMode"
            :room="editingRoom"
            @success="handleDialogSuccess"
        />

        <!-- Delete dialog -->
        <RoomDeleteDialog
            v-model:open="showDeleteConfirm"
            :room="roomToDelete"
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
