<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Plus,
    Shield,
    ShieldCheck,
    Trash2,
    UserCog,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Role {
    uuid: string;
    id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    permissions_count: number;
    users_count: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRoles {
    data: Role[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    name: string;
    sort_dir: 'asc' | 'desc';
    per_page: number | 'all';
}

const props = defineProps<{
    roles: PaginatedRoles;
    filters: Filters;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Perfis', href: '/acl/roles' },
];

const page = usePage();
const permissions = computed(
    () => (page.props.auth?.permissions as string[]) ?? [],
);
const canAdd = computed(() => permissions.value.includes('roles_add'));
const canEdit = computed(() => permissions.value.includes('roles_edit'));
const canDelete = computed(() => permissions.value.includes('roles_delete'));

const roles = computed(() => props.roles.data);

const localFilters = ref({
    name: props.filters.name || '',
    sort_dir: props.filters.sort_dir || 'asc',
    per_page: String(props.filters.per_page ?? 10),
});

function toggleSort() {
    localFilters.value.sort_dir =
        localFilters.value.sort_dir === 'asc' ? 'desc' : 'asc';
    applyFilters();
}

function applyFilters() {
    router.post(
        '/acl/roles',
        {
            name: localFilters.value.name,
            sort_dir: localFilters.value.sort_dir,
            per_page: localFilters.value.per_page,
        },
        { preserveScroll: true },
    );
}

function updatePerPage(value: string) {
    localFilters.value.per_page = value;
    applyFilters();
}

const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);

function openDeleteModal(role: Role) {
    roleToDelete.value = role;
    showDeleteModal.value = true;
}

function handleDeleteSuccess() {
    showDeleteModal.value = false;
    roleToDelete.value = null;
    router.reload({ preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Perfis" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <Heading title="Perfis" />
            </div>

            <div class="flex items-center justify-between">
                <Link
                    v-if="canAdd"
                    href="/acl/roles/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Novo Perfil
                </Link>
                <div v-else />

                <PerPageSelect
                    :model-value="localFilters.per_page"
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
                                    @click="toggleSort"
                                >
                                    Nome
                                    <ChevronUp
                                        v-if="localFilters.sort_dir === 'asc'"
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                    <ChevronDown
                                        v-else
                                        class="h-3.5 w-3.5 text-primary"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Descrição
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Permissões
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Usuários
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Padrão
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
                            v-for="role in roles"
                            :key="role.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ role.name }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ role.description ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <Link
                                    v-if="canEdit"
                                    :href="`/acl/roles/${role.uuid}/permissions`"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 transition-colors ring-inset hover:bg-muted"
                                    :title="`Gerenciar permissões (${role.permissions_count})`"
                                >
                                    <ShieldCheck class="h-3 w-3" />
                                    {{ role.permissions_count }}
                                </Link>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    <ShieldCheck class="h-3 w-3" />
                                    {{ role.permissions_count }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    {{ role.users_count }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    v-if="role.is_default"
                                    class="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary ring-1 ring-primary/20 ring-inset"
                                >
                                    Padrão
                                </span>
                                <span v-else class="text-muted-foreground/40"
                                    >—</span
                                >
                            </td>
                            <td class="px-3 py-3 text-xs text-muted-foreground">
                                {{
                                    new Date(
                                        role.created_at,
                                    ).toLocaleDateString('pt-BR')
                                }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Link
                                        v-if="canEdit"
                                        :href="`/acl/roles/${role.uuid}/edit`"
                                        title="Editar"
                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <UserCog class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="
                                            canDelete &&
                                            !role.is_default &&
                                            role.users_count === 0
                                        "
                                        title="Excluir"
                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-destructive transition-colors hover:bg-destructive/15 hover:text-destructive dark:hover:bg-destructive/25"
                                        @click="openDeleteModal(role)"
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
                    v-if="roles.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <Shield class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum perfil encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Comece criando um novo perfil de acesso.
                    </p>
                    <Link
                        v-if="canAdd"
                        href="/acl/roles/create"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Novo Perfil
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination :paginator="props.roles" />
        </div>

        <ConfirmDeleteModal
            v-if="roleToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir a função ${roleToDelete.name}?`"
            :action="`/acl/roles/${roleToDelete.uuid}/confirm-delete`"
            success-message="Perfil excluído com sucesso."
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
