<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Filter,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import GradeFormDialog from '@/components/GradeFormDialog.vue';
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
import { usePermission } from '@/composables/usePermission';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index } from '@/routes/tenant/grades';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedGrades, School, Segment } from '@/types/crm';

interface GradeItem {
    uuid: string;
    name: string;
    order: number;
    segment_id: number;
    segment?: Segment | null;
}

interface Filters {
    name: string;
    segment_id: string;
    sort_by: 'name' | 'order' | '';
    sort_dir: 'asc' | 'desc';
    per_page: number;
}

const props = defineProps<{
    school: School;
    grades: PaginatedGrades;
    segments: Segment[];
    filters: Filters;
}>();

const toast = useToast();

const { can } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Turmas/Séries', href: index().url },
];

const filters = computed(() => props.filters);

const localFilters = ref<Filters>({
    name: filters.value.name || '',
    segment_id: filters.value.segment_id || '',
    sort_by: (filters.value.sort_by as Filters['sort_by']) || 'order',
    sort_dir: filters.value.sort_dir || 'asc',
    per_page: Number(filters.value.per_page ?? 10),
});

const filterCount = computed(() =>
    [filters.value.name ? 1 : 0, filters.value.segment_id ? 1 : 0].reduce(
        (a, b) => a + b,
        0,
    ),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function toggleSort(column: 'name' | 'order'): void {
    const newDir =
        localFilters.value.sort_by === column &&
        localFilters.value.sort_dir === 'asc'
            ? 'desc'
            : 'asc';
    localFilters.value.sort_by = column;
    localFilters.value.sort_dir = newDir;
    applyFilters();
}

function getSortIcon(column: 'name' | 'order'): 'asc' | 'desc' | 'none' {
    if (localFilters.value.sort_by !== column) return 'none';
    return localFilters.value.sort_dir === 'asc' ? 'asc' : 'desc';
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function updateFilterSegment(value: string): void {
    localFilters.value.segment_id = value === 'all' ? '' : value;
}

function applyFilters(): void {
    router.get(index().url, { ...localFilters.value }, { preserveUrl: true });
}

function clearFilters(): void {
    router.visit(index().url);
}

// — Dialog form (create / edit) —
type FormMode = 'create' | 'edit';

const formMode = ref<FormMode>('create');
const editingGrade = ref<GradeItem | null>(null);
const showDialog = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingGrade.value = null;
    showDialog.value = true;
}

function openEditForm(grade: GradeItem): void {
    formMode.value = 'edit';
    editingGrade.value = grade;
    showDialog.value = true;
}

function handleDialogSuccess(): void {
    showDialog.value = false;
    editingGrade.value = null;
    toast.success(
        formMode.value === 'create'
            ? 'Turma criada com sucesso.'
            : 'Turma atualizada com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

// — Delete —
const showDeleteConfirm = ref(false);
const gradeToDelete = ref<GradeItem | null>(null);

function openDeleteConfirm(grade: GradeItem): void {
    gradeToDelete.value = grade;
    showDeleteConfirm.value = true;
}

function cancelDelete(): void {
    showDeleteConfirm.value = false;
    gradeToDelete.value = null;
}

function confirmDelete(): void {
    if (!gradeToDelete.value) return;

    router.delete(
        destroy({
            grade: gradeToDelete.value.uuid,
        }).url,
        {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                gradeToDelete.value = null;
                toast.success('Turma excluída com sucesso.');
                router.reload({ preserveUrl: true });
            },
            onError: () => {
                toast.error('Erro ao excluir a turma.');
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Turmas/Séries" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Turmas/Séries" />

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
                                            Nome da Turma
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
                                            for="filter-segment"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Segmento
                                        </Label>
                                        <Select
                                            :default-value="
                                                localFilters.segment_id || 'all'
                                            "
                                            @update:model-value="
                                                updateFilterSegment
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-segment"
                                                class="h-8"
                                            >
                                                <SelectValue
                                                    placeholder="Todos"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Todos
                                                </SelectItem>
                                                <SelectItem
                                                    v-for="segment in props.segments"
                                                    :key="segment.uuid"
                                                    :value="segment.uuid"
                                                >
                                                    {{ segment.name }}
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
                    v-if="can('grades', 'add')"
                    class="inline-flex items-center gap-2"
                    @click="openCreateForm"
                >
                    <Plus class="h-4 w-4" />
                    Nova Turma/Série
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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('name')"
                                >
                                    Nome da Turma
                                    <ChevronUp
                                        v-if="getSortIcon('name') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('name') === 'desc'
                                        "
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronsUpDown
                                        v-else
                                        class="h-3.5 w-3.5 opacity-30"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Segmento
                            </th>
                            <th
                                class="px-3 pb-3 text-center font-medium text-muted-foreground"
                            >
                                <button
                                    type="button"
                                    class="mx-auto inline-flex items-center justify-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('order')"
                                >
                                    Ordem
                                    <ChevronUp
                                        v-if="getSortIcon('order') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('order') === 'desc'
                                        "
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronsUpDown
                                        v-else
                                        class="h-3.5 w-3.5 opacity-30"
                                    />
                                </button>
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
                            v-for="grade in props.grades.data"
                            :key="grade.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ grade.name }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                <span
                                    v-if="grade.segment"
                                    class="rounded bg-muted px-2 py-1 text-xs font-semibold"
                                >
                                    {{ grade.segment.name }}
                                </span>
                                <span
                                    v-else
                                    class="text-muted-foreground opacity-50"
                                    >—</span
                                >
                            </td>
                            <td
                                class="px-3 py-3 text-center text-muted-foreground"
                            >
                                {{ grade.order ?? 0 }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        v-if="can('grades', 'edit')"
                                        type="button"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                        @click="openEditForm(grade)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="can('grades', 'delete')"
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(grade)"
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
                    v-if="props.grades.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma turma/série encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando uma nova turma.'
                        }}
                    </p>
                    <Button
                        v-if="!hasActiveFilter && can('grades', 'add')"
                        class="mt-4 inline-flex items-center gap-2"
                        @click="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Turma/Série
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.grades" />
        </div>

        <!-- Create / Edit Dialog -->
        <GradeFormDialog
            v-model:open="showDialog"
            :mode="formMode"
            :grade="editingGrade"
            :segments="props.segments"
            @success="handleDialogSuccess"
        />

        <!-- Delete confirmation inline dialog -->
        <div
            v-if="showDeleteConfirm && gradeToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg"
            >
                <h3 class="text-base font-semibold">Confirmar Exclusão</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tem certeza que deseja excluir a turma
                    <span class="font-medium text-foreground">{{
                        gradeToDelete.name
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
