<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Filter,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index, store, update, destroy } from '@/routes/tenant/grades';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedGrades, School, Segment } from '@/types/crm';

interface GradeItem {
    uuid: string;
    name: string;
    order: number;
    segment_id: number;
    segment?: Segment;
    created_at: string;
    updated_at: string;
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
    router.post(
        index.post().url,
        {
            name: localFilters.value.name,
            segment_id: localFilters.value.segment_id,
            sort_by: localFilters.value.sort_by,
            sort_dir: localFilters.value.sort_dir,
            per_page: localFilters.value.per_page,
        },
        { preserveScroll: true },
    );
}

function clearFilters(): void {
    router.visit(index().url);
}

// — Inline form (create / edit) —
type FormMode = 'create' | 'edit';

const formMode = ref<FormMode>('create');
const editingGrade = ref<GradeItem | null>(null);
const showForm = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingGrade.value = null;
    showForm.value = true;
}

function openEditForm(grade: GradeItem): void {
    formMode.value = 'edit';
    editingGrade.value = grade;
    showForm.value = true;
}

function closeForm(): void {
    showForm.value = false;
    editingGrade.value = null;
}

function handleFormSuccess(): void {
    closeForm();
    toast.success(
        formMode.value === 'create'
            ? 'Turma criada com sucesso.'
            : 'Turma atualizada com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

function handleFormError(errors: any): void {
    if (errors.segment_uuid) {
        toast.error(
            'Por favor, selecione um segmento oblíquo e tente novamente.',
        );
    } else {
        toast.error('Verifique os campos e tente novamente.');
    }
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
                                    @submit.prevent="applyFilters"
                                    class="space-y-4"
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
                                                <SelectItem value="all"
                                                    >Todos</SelectItem
                                                >
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
                    v-if="!showForm || formMode !== 'create'"
                    class="inline-flex items-center gap-2"
                    @click="openCreateForm"
                >
                    <Plus class="h-4 w-4" />
                    Nova Turma/Série
                </Button>
                <div v-else />

                <PerPageSelect
                    :model-value="String(localFilters.per_page)"
                    @update:model-value="updatePerPage"
                />
            </div>

            <!-- Inline Form -->
            <div v-if="showForm" class="rounded-md border">
                <div
                    class="flex items-center justify-between border-b px-6 py-4"
                >
                    <h3 class="text-sm font-semibold">
                        {{
                            formMode === 'create'
                                ? 'Nova Turma/Série'
                                : 'Editar Turma/Série'
                        }}
                    </h3>
                    <button
                        type="button"
                        class="rounded p-1 hover:bg-muted"
                        @click="closeForm"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Create Form -->
                <Form
                    v-if="formMode === 'create'"
                    method="post"
                    :action="store().url"
                    class="space-y-0"
                    v-slot="{ errors, processing }"
                    @success="handleFormSuccess"
                    @error="handleFormError"
                >
                    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="grade-name">Nome</Label>
                            <Input
                                id="grade-name"
                                name="name"
                                placeholder="Ex: 1º Ano A"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="segment_uuid">Segmento</Label>
                            <Select name="segment_uuid" required>
                                <SelectTrigger id="segment_uuid">
                                    <SelectValue
                                        placeholder="Selecione o segmento"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="segment in props.segments"
                                        :key="segment.uuid"
                                        :value="segment.uuid"
                                    >
                                        {{ segment.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.segment_uuid" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="order">Ordem (Opcional)</Label>
                            <Input
                                id="order"
                                type="number"
                                name="order"
                                min="0"
                                placeholder="Ex: 5"
                            />
                            <InputError :message="errors.order" />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            @click="closeForm"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            Criar Turma
                        </Button>
                    </div>
                </Form>

                <!-- Edit Form -->
                <Form
                    v-else-if="formMode === 'edit' && editingGrade"
                    method="put"
                    :action="
                        update({
                            grade: editingGrade.uuid,
                        }).url
                    "
                    class="space-y-0"
                    v-slot="{ errors, processing }"
                    @success="handleFormSuccess"
                    @error="handleFormError"
                >
                    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="edit-grade-name">Nome</Label>
                            <Input
                                id="edit-grade-name"
                                name="name"
                                :default-value="editingGrade.name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-segment_uuid">Segmento</Label>
                            <Select
                                name="segment_uuid"
                                :default-value="
                                    props.segments.find(
                                        (s) =>
                                            s.uuid ===
                                            editingGrade?.segment?.uuid,
                                    )?.uuid
                                "
                                required
                            >
                                <SelectTrigger id="edit-segment_uuid">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="segment in props.segments"
                                        :key="segment.uuid"
                                        :value="segment.uuid"
                                    >
                                        {{ segment.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.segment_uuid" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-order">Ordem</Label>
                            <Input
                                id="edit-order"
                                type="number"
                                name="order"
                                min="0"
                                :default-value="editingGrade.order?.toString()"
                            />
                            <InputError :message="errors.order" />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            @click="closeForm"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            Salvar Alterações
                        </Button>
                    </div>
                </Form>
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
                                        type="button"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                        @click="openEditForm(grade)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
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
                        v-if="!hasActiveFilter && !showForm"
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
