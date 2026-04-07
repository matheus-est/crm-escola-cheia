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
import { index, store, update, destroy } from '@/routes/tenant/school_years';
import type { BreadcrumbItem } from '@/types';
import type {
    PaginatedSchoolYears,
    School,
    SchoolYear,
    SchoolYearStatus,
} from '@/types/crm';

interface Filters {
    nome: string;
    status: SchoolYearStatus | '';
    sort_by: 'nome' | 'inicio' | 'fim' | 'status' | '';
    sort_dir: 'asc' | 'desc';
    per_page: number;
}

const props = defineProps<{
    school: School;
    schoolYears: PaginatedSchoolYears;
    filters: Filters;
}>();

const toast = useToast();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Anos Letivos', href: index().url },
];

const filters = computed(() => props.filters);

const localFilters = ref<Filters>({
    nome: filters.value.nome || '',
    status: (filters.value.status as SchoolYearStatus) || '',
    sort_by: (filters.value.sort_by as Filters['sort_by']) || 'nome',
    sort_dir: filters.value.sort_dir || 'asc',
    per_page: Number(filters.value.per_page ?? 10),
});

const filterCount = computed(() =>
    [filters.value.nome ? 1 : 0, filters.value.status ? 1 : 0].reduce(
        (a, b) => a + b,
        0,
    ),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function toggleSort(column: 'nome' | 'inicio' | 'fim' | 'status'): void {
    const newDir =
        localFilters.value.sort_by === column &&
        localFilters.value.sort_dir === 'asc'
            ? 'desc'
            : 'asc';
    localFilters.value.sort_by = column;
    localFilters.value.sort_dir = newDir;
    applyFilters();
}

function getSortIcon(
    column: 'nome' | 'inicio' | 'fim' | 'status',
): 'asc' | 'desc' | 'none' {
    if (localFilters.value.sort_by !== column) return 'none';
    return localFilters.value.sort_dir === 'asc' ? 'asc' : 'desc';
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function updateFilterStatus(value: string): void {
    localFilters.value.status = value as SchoolYearStatus | '';
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            nome: localFilters.value.nome,
            status: localFilters.value.status,
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
const editingSchoolYear = ref<SchoolYear | null>(null);
const showForm = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingSchoolYear.value = null;
    showForm.value = true;
}

function openEditForm(schoolYear: SchoolYear): void {
    formMode.value = 'edit';
    editingSchoolYear.value = schoolYear;
    showForm.value = true;
}

function closeForm(): void {
    showForm.value = false;
    editingSchoolYear.value = null;
}

function handleFormSuccess(): void {
    closeForm();
    toast.success(
        formMode.value === 'create'
            ? 'Ano letivo criado com sucesso.'
            : 'Ano letivo atualizado com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

function handleFormError(): void {
    toast.error('Verifique os campos e tente novamente.');
}

// — Delete —
const showDeleteConfirm = ref(false);
const schoolYearToDelete = ref<SchoolYear | null>(null);

function openDeleteConfirm(schoolYear: SchoolYear): void {
    schoolYearToDelete.value = schoolYear;
    showDeleteConfirm.value = true;
}

function cancelDelete(): void {
    showDeleteConfirm.value = false;
    schoolYearToDelete.value = null;
}

function confirmDelete(): void {
    if (!schoolYearToDelete.value) return;

    router.delete(
        destroy({
            schoolYear: schoolYearToDelete.value.uuid,
        }).url,
        {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                schoolYearToDelete.value = null;
                toast.success('Ano letivo excluído com sucesso.');
                router.reload({ preserveUrl: true });
            },
            onError: () => {
                toast.error('Erro ao excluir o ano letivo.');
            },
        },
    );
}

// — Badge helpers —
function statusLabel(status: SchoolYearStatus): string {
    const labels: Record<SchoolYearStatus, string> = {
        ativo: 'Ativo',
        encerrado: 'Encerrado',
        planejamento: 'Planejamento',
    };
    return labels[status];
}

function statusClass(status: SchoolYearStatus): string {
    const classes: Record<SchoolYearStatus, string> = {
        ativo: 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
        encerrado:
            'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
        planejamento:
            'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
    };
    return classes[status];
}

function formatDate(dateStr: string): string {
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Anos Letivos" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Anos Letivos" />

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
                                            for="filter-nome"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Nome
                                        </Label>
                                        <Input
                                            id="filter-nome"
                                            v-model="localFilters.nome"
                                            placeholder="Buscar por nome..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-status"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Status
                                        </Label>
                                        <Select
                                            :default-value="
                                                localFilters.status || ''
                                            "
                                            @update:model-value="
                                                updateFilterStatus
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-status"
                                                class="h-8"
                                            >
                                                <SelectValue
                                                    placeholder="Todos"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value=""
                                                    >Todos</SelectItem
                                                >
                                                <SelectItem value="ativo"
                                                    >Ativo</SelectItem
                                                >
                                                <SelectItem value="planejamento"
                                                    >Planejamento</SelectItem
                                                >
                                                <SelectItem value="encerrado"
                                                    >Encerrado</SelectItem
                                                >
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
                    Novo Ano Letivo
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
                                ? 'Novo Ano Letivo'
                                : 'Editar Ano Letivo'
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
                    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-4">
                        <div class="grid gap-2">
                            <Label for="nome">Nome</Label>
                            <Input
                                id="nome"
                                name="nome"
                                placeholder="Ex: 2025"
                                required
                            />
                            <InputError :message="errors.nome" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="inicio">Início</Label>
                            <Input
                                id="inicio"
                                type="date"
                                name="inicio"
                                required
                            />
                            <InputError :message="errors.inicio" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="fim">Fim</Label>
                            <Input id="fim" type="date" name="fim" required />
                            <InputError :message="errors.fim" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select name="status" required>
                                <SelectTrigger id="status">
                                    <SelectValue
                                        placeholder="Selecione o status"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="planejamento"
                                        >Planejamento</SelectItem
                                    >
                                    <SelectItem value="ativo">Ativo</SelectItem>
                                    <SelectItem value="encerrado"
                                        >Encerrado</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.status" />
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
                            Criar Ano Letivo
                        </Button>
                    </div>
                </Form>

                <!-- Edit Form -->
                <Form
                    v-else-if="formMode === 'edit' && editingSchoolYear"
                    method="put"
                    :action="
                        update({
                            schoolYear: editingSchoolYear.uuid,
                        }).url
                    "
                    class="space-y-0"
                    v-slot="{ errors, processing }"
                    @success="handleFormSuccess"
                    @error="handleFormError"
                >
                    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-4">
                        <div class="grid gap-2">
                            <Label for="edit-nome">Nome</Label>
                            <Input
                                id="edit-nome"
                                name="nome"
                                :default-value="editingSchoolYear.nome"
                                placeholder="Ex: 2025"
                                required
                            />
                            <InputError :message="errors.nome" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-inicio">Início</Label>
                            <Input
                                id="edit-inicio"
                                type="date"
                                name="inicio"
                                :default-value="editingSchoolYear.inicio"
                                required
                            />
                            <InputError :message="errors.inicio" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-fim">Fim</Label>
                            <Input
                                id="edit-fim"
                                type="date"
                                name="fim"
                                :default-value="editingSchoolYear.fim"
                                required
                            />
                            <InputError :message="errors.fim" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-status">Status</Label>
                            <Select
                                name="status"
                                :default-value="editingSchoolYear.status"
                                required
                            >
                                <SelectTrigger id="edit-status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="planejamento"
                                        >Planejamento</SelectItem
                                    >
                                    <SelectItem value="ativo">Ativo</SelectItem>
                                    <SelectItem value="encerrado"
                                        >Encerrado</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.status" />
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
                                    @click="toggleSort('nome')"
                                >
                                    Nome
                                    <ChevronUp
                                        v-if="getSortIcon('nome') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('nome') === 'desc'
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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('inicio')"
                                >
                                    Início
                                    <ChevronUp
                                        v-if="getSortIcon('inicio') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('inicio') === 'desc'
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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('fim')"
                                >
                                    Fim
                                    <ChevronUp
                                        v-if="getSortIcon('fim') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('fim') === 'desc'
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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('status')"
                                >
                                    Status
                                    <ChevronUp
                                        v-if="getSortIcon('status') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('status') === 'desc'
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
                            v-for="schoolYear in props.schoolYears.data"
                            :key="schoolYear.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ schoolYear.nome }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ formatDate(schoolYear.inicio) }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ formatDate(schoolYear.fim) }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="statusClass(schoolYear.status)"
                                >
                                    {{ statusLabel(schoolYear.status) }}
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
                                        @click="openEditForm(schoolYear)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(schoolYear)"
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
                    v-if="props.schoolYears.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum ano letivo encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando um novo ano letivo.'
                        }}
                    </p>
                    <Button
                        v-if="!hasActiveFilter && !showForm"
                        class="mt-4 inline-flex items-center gap-2"
                        @click="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Novo Ano Letivo
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.schoolYears" />
        </div>

        <!-- Delete confirmation inline dialog -->
        <div
            v-if="showDeleteConfirm && schoolYearToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg"
            >
                <h3 class="text-base font-semibold">Confirmar Exclusão</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tem certeza que deseja excluir o ano letivo
                    <span class="font-medium text-foreground">{{
                        schoolYearToDelete.nome
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
