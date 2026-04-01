<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Filter,
    Plus,
    Trash2,
    UserPen,
    RotateCcw,
    ChevronUp,
    ChevronDown,
    ChevronsUpDown,
    Users,
    FileDown,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import ConfirmRestoreModal from '@/components/ConfirmRestoreModal.vue';
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
    index,
    create,
    edit,
    clear_filters,
    confirmDelete,
    exportMethod as exportUser,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    role: Role | null;
    created_at: string;
    deleted_at: string | null;
    is_deletable: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    name: string;
    email: string;
    role_id: string;
    status: 'active' | 'trashed';
    sort_by: 'name' | 'email' | '';
    sort_dir: 'asc' | 'desc';
    per_page: number | 'all';
}

const props = defineProps<{
    users: PaginatedUsers;
    roles: Role[];
    filters: Filters;
    isCurrentUserMaster: boolean;
}>();

const page = usePage();
const permissions = computed(
    () => (page.props.auth?.permissions as string[]) ?? [],
);
const canEdit = computed(() => permissions.value.includes('users_edit'));
const canDelete = computed(() => permissions.value.includes('users_delete'));
const canAdd = computed(() => permissions.value.includes('users_add'));
const canRestore = computed(() => permissions.value.includes('users_restore'));
const canExportData = computed(() =>
    permissions.value.includes('users_export_data'),
);
const isMaster = computed(() => props.isCurrentUserMaster);

const users = computed(() => props.users.data);
const roles = computed(() => props.roles);
const filters = computed(() => props.filters);

const isTrashed = computed(() => filters.value.status === 'trashed');

const localFilters = ref({
    name: filters.value.name || '',
    email: filters.value.email || '',
    role_id: filters.value.role_id || 'all',
    status: filters.value.status || 'active',
    sort_by: filters.value.sort_by || 'name',
    sort_dir: filters.value.sort_dir || 'asc',
    per_page: String(filters.value.per_page ?? 10),
});

const filterCount = computed(() =>
    [
        filters.value.name ? 1 : 0,
        filters.value.email ? 1 : 0,
        filters.value.role_id ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function toggleSort(column: 'name' | 'email'): void {
    const newDir =
        localFilters.value.sort_by === column &&
        localFilters.value.sort_dir === 'asc'
            ? 'desc'
            : 'asc';
    localFilters.value.sort_by = column;
    localFilters.value.sort_dir = newDir;
    applyFilters();
}

function getSortIcon(column: 'name' | 'email'): 'asc' | 'desc' | 'none' {
    if (localFilters.value.sort_by !== column) return 'none';
    return localFilters.value.sort_dir === 'asc' ? 'asc' : 'desc';
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = value;
    applyFilters();
}

function updateRole(value: string): void {
    localFilters.value.role_id = value;
}
function updateStatus(value: string): void {
    localFilters.value.status = value as 'active' | 'trashed';
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            name: localFilters.value.name,
            email: localFilters.value.email,
            role_id:
                localFilters.value.role_id === 'all'
                    ? ''
                    : localFilters.value.role_id,
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
const userToDelete = ref<User | null>(null);

function openDeleteModal(user: User): void {
    userToDelete.value = user;
    showDeleteModal.value = true;
}

function handleDeleteSuccess(): void {
    showDeleteModal.value = false;
    userToDelete.value = null;
    router.reload({ preserveScroll: true });
}

// — Restore —
const showRestoreModal = ref(false);
const userToRestore = ref<User | null>(null);

function openRestoreModal(user: User): void {
    userToRestore.value = user;
    showRestoreModal.value = true;
}

function handleRestoreSuccess(): void {
    showRestoreModal.value = false;
    userToRestore.value = null;
    router.reload({ preserveScroll: true });
}

// — Export —
function exportUserData(userUuid: string): void {
    window.open(exportUser({ user_uuid: userUuid }).url, '_blank');
}

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Usuários', href: index().url },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Usuários" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Usuários" />

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
                                hasActiveFilter || filters.status !== 'active'
                                    ? 'border-primary/40 bg-primary/5 text-primary dark:bg-primary/10'
                                    : 'border-border bg-background hover:bg-muted/50'
                            "
                        >
                            <Filter class="h-4 w-4 shrink-0" />
                            <span>Filtrar</span>
                            <span
                                v-if="
                                    hasActiveFilter ||
                                    filters.status !== 'active'
                                "
                                class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs text-primary-foreground"
                            >
                                {{
                                    filterCount +
                                    (filters.status !== 'active' ? 1 : 0)
                                }}
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
                                            for="filter-status"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >Status</Label
                                        >
                                        <Select
                                            :default-value="localFilters.status"
                                            @update:model-value="updateStatus"
                                        >
                                            <SelectTrigger
                                                id="filter-status"
                                                class="h-8"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active"
                                                    >Ativos</SelectItem
                                                >
                                                <SelectItem value="trashed"
                                                    >Deletados</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-name"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >Nome</Label
                                        >
                                        <Input
                                            id="filter-name"
                                            v-model="localFilters.name"
                                            placeholder="Buscar por nome..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-email"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >Email</Label
                                        >
                                        <Input
                                            id="filter-email"
                                            v-model="localFilters.email"
                                            placeholder="Buscar por email..."
                                            class="h-8"
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-role"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >Função</Label
                                        >
                                        <Select
                                            :default-value="
                                                localFilters.role_id || 'all'
                                            "
                                            @update:model-value="updateRole"
                                        >
                                            <SelectTrigger
                                                id="filter-role"
                                                class="h-8"
                                            >
                                                <SelectValue
                                                    placeholder="Todas as funções"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all"
                                                    >Todas as
                                                    funções</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="role in roles"
                                                    :key="role.id"
                                                    :value="String(role.id)"
                                                >
                                                    {{ role.name }}
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
                                            class="h-8 text-muted-foreground"
                                            as-child
                                        >
                                            <Link :href="clear_filters().url"
                                                >Limpar filtros</Link
                                            >
                                        </Button>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            class="h-8"
                                            >Aplicar</Button
                                        >
                                    </div>
                                </form>
                            </div>
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>

            <div class="flex items-center justify-between">
                <Link
                    v-if="canAdd && !isTrashed"
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Novo Usuário
                </Link>
                <div v-else />

                <PerPageSelect
                    :model-value="String(localFilters.per_page)"
                    @update:model-value="updatePerPage"
                />
            </div>

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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 transition-colors hover:text-foreground"
                                    @click="toggleSort('email')"
                                >
                                    Email
                                    <ChevronUp
                                        v-if="getSortIcon('email') === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            getSortIcon('email') === 'desc'
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
                                Perfil
                            </th>
                            <th
                                v-if="isTrashed"
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Excluído em
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Criado em
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
                            v-for="user in users"
                            :key="user.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ user.name }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ user.email }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    {{ user.role?.name ?? 'Sem função' }}
                                </span>
                            </td>
                            <td
                                v-if="isTrashed"
                                class="px-3 py-3 text-xs text-muted-foreground"
                            >
                                {{
                                    user.deleted_at
                                        ? new Date(
                                              user.deleted_at,
                                          ).toLocaleDateString('pt-BR')
                                        : '—'
                                }}
                            </td>
                            <td class="px-3 py-3 text-xs text-muted-foreground">
                                {{
                                    new Date(
                                        user.created_at,
                                    ).toLocaleDateString('pt-BR')
                                }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <template v-if="!isTrashed">
                                        <Link
                                            v-if="canEdit"
                                            :href="edit(user.uuid).url"
                                            class="rounded p-1 hover:bg-muted"
                                            title="Editar"
                                        >
                                            <UserPen class="h-4 w-4" />
                                        </Link>
                                        <button
                                            v-if="
                                                canDelete && user.is_deletable
                                            "
                                            class="rounded p-1 text-destructive hover:bg-muted"
                                            title="Excluir"
                                            @click="openDeleteModal(user)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-if="canRestore"
                                            class="rounded p-1 text-green-600 hover:bg-muted"
                                            title="Reativar"
                                            @click="openRestoreModal(user)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                        </button>
                                    </template>
                                    <button
                                        v-if="isMaster && canExportData"
                                        class="rounded p-1 text-blue-600 hover:bg-muted"
                                        title="Exportar Dados"
                                        @click="exportUserData(user.uuid)"
                                    >
                                        <FileDown class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="users.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <Users class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        {{
                            isTrashed
                                ? 'Nenhum usuário deletado'
                                : 'Nenhum usuário encontrado'
                        }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            isTrashed
                                ? 'Usuários excluídos aparecerão aqui.'
                                : hasActiveFilter
                                  ? 'Tente ajustar os filtros aplicados.'
                                  : 'Comece adicionando um novo usuário.'
                        }}
                    </p>
                    <Link
                        v-if="canAdd && !isTrashed && !hasActiveFilter"
                        :href="create().url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Novo Usuário
                    </Link>
                </div>
            </div>

            <TablePagination :paginator="props.users" />
        </div>

        <ConfirmDeleteModal
            v-if="userToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir o usuário ${userToDelete.name}?`"
            :action="confirmDelete(userToDelete.uuid).url"
            success-message="Usuário excluído com sucesso."
            @success="handleDeleteSuccess"
        />

        <ConfirmRestoreModal
            v-if="userToRestore"
            v-model:open="showRestoreModal"
            :user-uuid="userToRestore.uuid"
            :user-name="userToRestore.name"
            success-message="Usuário reativado com sucesso."
            @success="handleRestoreSuccess"
        />
    </AppLayout>
</template>
