<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Filter,
    Megaphone,
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
import { usePermission } from '@/composables/usePermission';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store, update, destroy } from '@/routes/tenant/lead_sources';
import type { BreadcrumbItem } from '@/types';
import type { LeadSource, PaginatedLeadSources, School } from '@/types/crm';

interface Filters {
    nome: string;
    sort_by: 'name' | '';
    sort_dir: 'asc' | 'desc';
    per_page: number;
}

const props = defineProps<{
    school: School;
    leadSources: PaginatedLeadSources;
    filters: Filters;
}>();

const toast = useToast();

const { can } = usePermission();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Origens de Lead', href: index().url },
];

const filters = computed(() => props.filters);

const localFilters = ref<Filters>({
    name: filters.value.name || '',
    sort_by: (filters.value.sort_by as Filters['sort_by']) || 'name',
    sort_dir: filters.value.sort_dir || 'asc',
    per_page: Number(filters.value.per_page ?? 10),
});

const filterCount = computed(() => (filters.value.name ? 1 : 0));

const hasActiveFilter = computed(() => filterCount.value > 0);

function toggleSort(column: 'name'): void {
    const newDir =
        localFilters.value.sort_by === column &&
        localFilters.value.sort_dir === 'asc'
            ? 'desc'
            : 'asc';
    localFilters.value.sort_by = column;
    localFilters.value.sort_dir = newDir;
    applyFilters();
}

function getSortIcon(column: 'name'): 'asc' | 'desc' | 'none' {
    if (localFilters.value.sort_by !== column) return 'none';
    return localFilters.value.sort_dir === 'asc' ? 'asc' : 'desc';
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            name: localFilters.value.name,
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
const editingLeadSource = ref<LeadSource | null>(null);
const showForm = ref(false);

function openCreateForm(): void {
    formMode.value = 'create';
    editingLeadSource.value = null;
    showForm.value = true;
}

function openEditForm(leadSource: LeadSource): void {
    formMode.value = 'edit';
    editingLeadSource.value = leadSource;
    showForm.value = true;
}

function closeForm(): void {
    showForm.value = false;
    editingLeadSource.value = null;
}

function handleFormSuccess(): void {
    closeForm();
    toast.success(
        formMode.value === 'create'
            ? 'Origem de lead criada com sucesso.'
            : 'Origem de lead atualizada com sucesso.',
    );
    router.reload({ preserveUrl: true });
}

function handleFormError(): void {
    toast.error('Verifique os campos e tente novamente.');
}

// — Delete —
const showDeleteConfirm = ref(false);
const leadSourceToDelete = ref<LeadSource | null>(null);

function openDeleteConfirm(leadSource: LeadSource): void {
    leadSourceToDelete.value = leadSource;
    showDeleteConfirm.value = true;
}

function cancelDelete(): void {
    showDeleteConfirm.value = false;
    leadSourceToDelete.value = null;
}

function confirmDelete(): void {
    if (!leadSourceToDelete.value) return;

    router.delete(
        destroy({
            leadSource: leadSourceToDelete.value.uuid,
        }).url,
        {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                leadSourceToDelete.value = null;
                toast.success('Origem de lead excluída com sucesso.');
                router.reload({ preserveUrl: true });
            },
            onError: () => {
                toast.error('Erro ao excluir a origem de lead.');
            },
        },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Origens de Lead" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Origens de Lead" />

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
                    v-if="
                        (!showForm || formMode !== 'create') &&
                        can('lead_sources', 'add')
                    "
                    class="inline-flex items-center gap-2"
                    @click="openCreateForm"
                >
                    <Plus class="h-4 w-4" />
                    Nova Origem
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
                                ? 'Nova Origem de Lead'
                                : 'Editar Origem de Lead'
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
                            <Label for="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Ex: Google Ads"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="is_active">Status</Label>
                            <Select name="is_active" default-value="1">
                                <SelectTrigger id="is_active">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Ativo</SelectItem>
                                    <SelectItem value="0">Inativo</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.is_active" />
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
                            Criar Origem
                        </Button>
                    </div>
                </Form>

                <!-- Edit Form -->
                <Form
                    v-else-if="formMode === 'edit' && editingLeadSource"
                    method="put"
                    :action="
                        update({
                            leadSource: editingLeadSource.uuid,
                        }).url
                    "
                    class="space-y-0"
                    v-slot="{ errors, processing }"
                    @success="handleFormSuccess"
                    @error="handleFormError"
                >
                    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="edit-name">Nome</Label>
                            <Input
                                id="edit-name"
                                name="name"
                                :default-value="editingLeadSource.name"
                                placeholder="Ex: Google Ads"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-is_active">Status</Label>
                            <Select
                                name="is_active"
                                :default-value="
                                    editingLeadSource.is_active ? '1' : '0'
                                "
                            >
                                <SelectTrigger id="edit-is_active">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Ativo</SelectItem>
                                    <SelectItem value="0">Inativo</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.is_active" />
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
                                    Nome
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
                                Tipo
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
                            v-for="leadSource in props.leadSources.data"
                            :key="leadSource.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ leadSource.name }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    v-if="leadSource.is_system"
                                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-600/20 ring-inset dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20"
                                >
                                    Sistema
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    Personalizado
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        leadSource.is_active
                                            ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20'
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20'
                                    "
                                >
                                    {{
                                        leadSource.is_active
                                            ? 'Ativo'
                                            : 'Inativo'
                                    }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    v-if="!leadSource.is_system"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        v-if="can('lead_sources', 'edit')"
                                        type="button"
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                        @click="openEditForm(leadSource)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="can('lead_sources', 'delete')"
                                        type="button"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(leadSource)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    —
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="props.leadSources.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <Megaphone class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma origem de lead encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando uma nova origem.'
                        }}
                    </p>
                    <Button
                        v-if="
                            !hasActiveFilter &&
                            !showForm &&
                            can('lead_sources', 'add')
                        "
                        class="mt-4 inline-flex items-center gap-2"
                        @click="openCreateForm"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Origem
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.leadSources" />
        </div>

        <!-- Delete confirmation inline dialog -->
        <div
            v-if="showDeleteConfirm && leadSourceToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div
                class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg"
            >
                <h3 class="text-base font-semibold">Confirmar Exclusão</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tem certeza que deseja excluir a origem
                    <span class="font-medium text-foreground">{{
                        leadSourceToDelete.name
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
