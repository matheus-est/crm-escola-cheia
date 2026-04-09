<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Building2,
    ChevronDown,
    ChevronUp,
    ChevronsUpDown,
    Filter,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import {
    clearFilters,
    confirmDelete,
    create,
    edit,
    index,
} from '@/routes/admin/schools';
import type { BreadcrumbItem } from '@/types';
import type { PaginatedSchools, School, SchoolStatus } from '@/types/crm';

interface Filters {
    legal_name: string;
    unit: string;
    cnpj: string;
    status: '' | SchoolStatus;
    sort_by: 'legal_name' | 'cnpj' | 'slug' | 'status' | '';
    sort_dir: 'asc' | 'desc';
    per_page: number;
}

const props = defineProps<{
    schools: PaginatedSchools;
    filters: Filters;
    isMaster: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
];

const filters = computed(() => props.filters);

const localFilters = ref<Filters>({
    legal_name: filters.value.legal_name || '',
    unit: filters.value.unit || '',
    cnpj: filters.value.cnpj || '',
    status: (filters.value.status as SchoolStatus) || '',
    sort_by: (filters.value.sort_by as Filters['sort_by']) || 'legal_name',
    sort_dir: filters.value.sort_dir || 'asc',
    per_page: Number(filters.value.per_page ?? 10),
});

const filterCount = computed(() =>
    [
        filters.value.legal_name ? 1 : 0,
        filters.value.unit ? 1 : 0,
        filters.value.cnpj ? 1 : 0,
        filters.value.status ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function toggleSort(column: 'legal_name' | 'cnpj' | 'slug' | 'status'): void {
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
    column: 'legal_name' | 'cnpj' | 'slug' | 'status',
): 'asc' | 'desc' | 'none' {
    if (localFilters.value.sort_by !== column) return 'none';
    return localFilters.value.sort_dir === 'asc' ? 'asc' : 'desc';
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function updateStatus(value: string): void {
    localFilters.value.status = value as SchoolStatus | '';
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            legal_name: localFilters.value.legal_name,
            unit: localFilters.value.unit,
            cnpj: localFilters.value.cnpj,
            status: localFilters.value.status,
            sort_by: localFilters.value.sort_by,
            sort_dir: localFilters.value.sort_dir,
            per_page: localFilters.value.per_page,
        },
        { preserveScroll: true },
    );
}

// — Delete —
const showDeleteModal = ref(false);
const schoolToDelete = ref<School | null>(null);

function openDeleteModal(school: School): void {
    schoolToDelete.value = school;
    showDeleteModal.value = true;
}

function handleDeleteSuccess(): void {
    showDeleteModal.value = false;
    schoolToDelete.value = null;
    router.reload({ preserveScroll: true });
}

function statusLabel(status: SchoolStatus): string {
    return status === 'active' ? 'Ativo' : 'Inativo';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Escolas" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Escolas" />

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
                                            for="filter-razao-social"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Razão Social
                                        </Label>
                                        <Input
                                            id="filter-razao-social"
                                            v-model="localFilters.legal_name"
                                            placeholder="Buscar por razão social..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-unit"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Unidade
                                        </Label>
                                        <Input
                                            id="filter-unit"
                                            v-model="localFilters.unit"
                                            placeholder="Buscar por unidade..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-cnpj"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            CNPJ
                                        </Label>
                                        <Input
                                            id="filter-cnpj"
                                            v-model="localFilters.cnpj"
                                            placeholder="Buscar por CNPJ..."
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
                                            @update:model-value="updateStatus"
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
                                                <SelectItem value="all"
                                                    >Todos</SelectItem
                                                >
                                                <SelectItem value="active"
                                                    >Ativo</SelectItem
                                                >
                                                <SelectItem value="inactive"
                                                    >Inativo</SelectItem
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
                                            class="h-8 text-muted-foreground"
                                            as-child
                                        >
                                            <Link :href="clearFilters().url"
                                                >Limpar filtros</Link
                                            >
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
                    v-if="props.isMaster"
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nova Escola
                </Link>
                <div v-else />

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
                                    @click="toggleSort('legal_name')"
                                >
                                    Razão Social
                                    <ChevronUp
                                        v-if="
                                            getSortIcon('legal_name') === 'asc'
                                        "
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('legal_name') === 'desc'
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
                                    @click="toggleSort('cnpj')"
                                >
                                    CNPJ
                                    <ChevronUp
                                        v-if="getSortIcon('cnpj') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('cnpj') === 'desc'
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
                                    @click="toggleSort('slug')"
                                >
                                    Slug
                                    <ChevronUp
                                        v-if="getSortIcon('slug') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('slug') === 'desc'
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
                            v-for="school in props.schools.data"
                            :key="school.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ school.legal_name }}
                            </td>
                            <td
                                class="px-3 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ school.cnpj }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ school.slug }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        school.status === 'active'
                                            ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20'
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20'
                                    "
                                >
                                    {{ statusLabel(school.status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            edit({ school: school.uuid }).url
                                        "
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="props.isMaster"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteModal(school)"
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
                    v-if="props.schools.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <Building2 class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma escola encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando uma nova escola.'
                        }}
                    </p>
                    <Link
                        v-if="props.isMaster && !hasActiveFilter"
                        :href="create().url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Escola
                    </Link>
                </div>
            </div>

            <TablePagination :paginator="props.schools" />
        </div>

        <!-- Delete confirmation modal -->
        <ConfirmDeleteModal
            v-if="schoolToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir a escola ${schoolToDelete.legal_name}?`"
            :action="confirmDelete({ school: schoolToDelete.uuid }).url"
            success-message="Escola excluída com sucesso."
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
