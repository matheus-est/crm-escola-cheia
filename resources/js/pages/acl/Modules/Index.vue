<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LayoutGrid, Shield, UserCog, Users } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index } from '@/routes/modules';
import { type BreadcrumbItem } from '@/types';

interface Module {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string;
    url: string | null;
    description: string | null;
    is_active: boolean;
    show_in_menu: boolean;
    order: number;
    actions_count: number;
    created_at: string;
    can_view: boolean;
    can_edit: boolean;
    can_delete: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedModules {
    data: Module[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Filters {
    per_page: number | 'all';
}

const props = defineProps<{
    modules: PaginatedModules;
    filters: Filters;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Módulos', href: index().url },
];

const iconMap: Record<string, any> = { LayoutGrid, Users, UserCog, Shield };
const getIcon = (iconName: string) => iconMap[iconName] || LayoutGrid;

const localFilters = ref({
    per_page: String(props.filters.per_page ?? 10),
});

function updatePerPage(value: string) {
    localFilters.value.per_page = value;
    router.post(
        index().url,
        {
            per_page: value,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Módulos" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Módulos" />
            </div>

            <!-- Toolbar -->
            <div class="flex items-center justify-end">
                <PerPageSelect
                    :model-value="localFilters.per_page"
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
                                Módulo
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Slug
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                URL
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Ações
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Menu
                            </th>
                            <th
                                class="px-3 pb-3 text-right font-medium text-muted-foreground"
                            >
                                Editar
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="module in modules.data"
                            :key="module.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="getIcon(module.icon)"
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <span class="font-medium text-foreground">{{
                                        module.name
                                    }}</span>
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ module.slug }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ module.url ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    {{ module.actions_count }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        module.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950 dark:text-red-400 dark:ring-red-500/20'
                                    "
                                >
                                    {{ module.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        module.show_in_menu
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'bg-muted/60 text-muted-foreground ring-border/50'
                                    "
                                >
                                    {{ module.show_in_menu ? 'Sim' : 'Não' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end">
                                    <Link
                                        v-if="module.can_edit"
                                        :href="
                                            edit({ module_uuid: module.uuid })
                                                .url
                                        "
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                    >
                                        <UserCog class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="modules.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <LayoutGrid class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhum módulo encontrado
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Os módulos do sistema aparecerão aqui.
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination :paginator="props.modules" />
        </div>
    </AppLayout>
</template>
