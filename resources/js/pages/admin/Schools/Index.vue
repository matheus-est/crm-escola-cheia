<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import { Building2, Pencil, Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import Heading from '@/components/Heading.vue'
import TablePagination from '@/components/TablePagination.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import AppLayout from '@/layouts/AppLayout.vue'
import { create, destroy, edit, index } from '@/routes/admin/schools'
import type { AppPageProps, BreadcrumbItem } from '@/types'
import type { PaginatedSchools, School, SchoolStatus } from '@/types/crm'

const props = defineProps<{
    schools: PaginatedSchools
}>()

const page = usePage<AppPageProps>()

const isMaster = computed(
    () => (page.props.auth.user as { role?: { name: string } | null }).role?.name === 'Master',
)

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
]

const filterRazaoSocial = ref('')
const filterStatus = ref<'' | SchoolStatus>('')

function applyFilters() {
    router.get(
        index().url,
        {
            razao_social: filterRazaoSocial.value,
            status: filterStatus.value,
        },
        { preserveState: true, replace: true },
    )
}

function onStatusChange(value: string) {
    filterStatus.value = value as '' | SchoolStatus
    applyFilters()
}

// Delete confirmation
const schoolToDelete = ref<School | null>(null)
const showDeleteConfirm = ref(false)

function openDeleteConfirm(school: School) {
    schoolToDelete.value = school
    showDeleteConfirm.value = true
}

function cancelDelete() {
    schoolToDelete.value = null
    showDeleteConfirm.value = false
}

function confirmDelete() {
    if (!schoolToDelete.value) return

    router.delete(destroy({ school: schoolToDelete.value.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            schoolToDelete.value = null
            showDeleteConfirm.value = false
        },
    })
}

function goToEdit(school: School) {
    router.visit(edit({ school: school.id }).url)
}

function goToCreate() {
    router.visit(create().url)
}

function statusLabel(status: SchoolStatus): string {
    return status === 'active' ? 'Ativo' : 'Inativo'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Escolas" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading title="Escolas" description="Gerencie as escolas cadastradas no sistema." />
                <Button @click="goToCreate" class="flex items-center gap-2">
                    <Plus class="h-4 w-4" />
                    Nova Escola
                </Button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label for="filter-razao-social" class="text-xs font-medium text-muted-foreground uppercase">
                        Razão Social
                    </Label>
                    <Input
                        id="filter-razao-social"
                        v-model="filterRazaoSocial"
                        placeholder="Buscar por razão social..."
                        class="h-8 w-64"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="filter-status" class="text-xs font-medium text-muted-foreground uppercase">
                        Status
                    </Label>
                    <Select :default-value="filterStatus || 'all'" @update:model-value="onStatusChange">
                        <SelectTrigger id="filter-status" class="h-8 w-40">
                            <SelectValue placeholder="Todos" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="active">Ativo</SelectItem>
                            <SelectItem value="inactive">Inativo</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Button size="sm" class="h-8" @click="applyFilters">
                    Filtrar
                </Button>
            </div>

            <!-- Table -->
            <div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="px-3 pb-3 text-left font-medium text-muted-foreground">
                                Razão Social
                            </th>
                            <th class="px-3 pb-3 text-left font-medium text-muted-foreground">
                                CNPJ
                            </th>
                            <th class="px-3 pb-3 text-left font-medium text-muted-foreground">
                                Slug
                            </th>
                            <th class="px-3 pb-3 text-left font-medium text-muted-foreground">
                                Status
                            </th>
                            <th class="px-3 pb-3 text-right font-medium text-muted-foreground">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="school in props.schools.data"
                            :key="school.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ school.razao_social }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground font-mono text-xs">
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
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                        @click="goToEdit(school)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="isMaster"
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteConfirm(school)"
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
                    <p class="text-sm font-medium text-foreground">Nenhuma escola encontrada</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Comece cadastrando uma nova escola.
                    </p>
                    <Button class="mt-4" @click="goToCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Nova Escola
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.schools" />
        </div>

        <!-- Delete confirmation dialog -->
        <div
            v-if="showDeleteConfirm && schoolToDelete"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        >
            <div class="w-full max-w-md rounded-lg bg-background p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-foreground">Confirmar Exclusão</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Tem certeza que deseja excluir a escola
                    <strong>{{ schoolToDelete.razao_social }}</strong>? Esta ação não pode ser desfeita.
                </p>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <Button variant="outline" @click="cancelDelete">Cancelar</Button>
                    <Button variant="destructive" @click="confirmDelete">Excluir</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
