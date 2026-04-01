<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderInput, Plus, Settings, type LucideIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import PerPageSelect from '@/components/PerPageSelect.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, create, edit } from '@/routes/menu-groups';
import { type BreadcrumbItem } from '@/types';

interface MenuGroup {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string | null;
    order: number;
    is_active: boolean;
    modules_count: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedMenuGroups {
    data: MenuGroup[];
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
    menuGroups: PaginatedMenuGroups;
    filters: Filters;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Grupo Menu', href: index().url },
];

const iconMap: Record<string, LucideIcon> = { FolderInput, Settings };
const getIcon = (iconName: string | null) =>
    iconName ? iconMap[iconName] || Settings : Settings;

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
        <Head :title="$t('menu_groups')" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading :title="$t('menu_groups')" />
            </div>

            <!-- Toolbar -->
            <div class="flex items-center justify-between">
                <Link
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    {{ $t('menu_groups.new') }}
                </Link>

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
                                {{ $t('menu_groups.table.name') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.slug') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.icon') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.order') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.modules') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.status') }}
                            </th>
                            <th
                                class="px-3 pb-3 text-right font-medium text-muted-foreground"
                            >
                                {{ $t('menu_groups.table.action') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="group in menuGroups.data"
                            :key="group.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="getIcon(group.icon)"
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <span class="font-medium text-foreground">{{
                                        group.name
                                    }}</span>
                                </div>
                            </td>
                            <td
                                class="px-3 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ group.slug }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ group.icon ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ group.order }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md bg-muted/60 px-2 py-0.5 text-xs font-medium text-muted-foreground ring-1 ring-border/50 ring-inset"
                                >
                                    {{ group.modules_count }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="
                                        group.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20'
                                            : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950 dark:text-red-400 dark:ring-red-500/20'
                                    "
                                >
                                    {{
                                        $t(
                                            group.is_active
                                                ? 'menu_groups.status.active'
                                                : 'menu_groups.status.inactive',
                                        )
                                    }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end">
                                    <Link
                                        :href="edit(group.uuid).url"
                                        class="rounded p-1 hover:bg-muted"
                                        :title="$t('menu_groups.table.action')"
                                    >
                                        <Settings class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="menuGroups.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <FolderInput class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        {{ $t('menu_groups.empty') }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ $t('menu_groups.empty.hint') }}
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination :paginator="props.menuGroups" />
        </div>
    </AppLayout>
</template>
