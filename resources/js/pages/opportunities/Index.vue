<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Eye, Filter, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/tenant/opportunities';
import type { BreadcrumbItem } from '@/types';
import type {
    Grade,
    LeadSource,
    Opportunity,
    OpportunityStatus,
    PaginatedOpportunities,
    School,
    SchoolYear,
    TenantUser,
} from '@/types/crm';

const props = defineProps<{
    school: School;
    opportunities: PaginatedOpportunities;
    grades: Grade[];
    schoolYears: SchoolYear[];
    leadSources: LeadSource[];
    responsibleUsers: TenantUser[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: '#' },
];

const localFilters = ref<{
    status: OpportunityStatus | '';
    grade_id: string;
    school_year_id: string;
    responsible_user_id: string;
    per_page: number;
}>({
    status: '',
    grade_id: '',
    school_year_id: '',
    responsible_user_id: '',
    per_page: 10,
});

const hasActiveFilter = computed(
    () =>
        !!(
            localFilters.value.status ||
            localFilters.value.grade_id ||
            localFilters.value.school_year_id ||
            localFilters.value.responsible_user_id
        ),
);

const filterCount = computed(() =>
    [
        localFilters.value.status ? 1 : 0,
        localFilters.value.grade_id ? 1 : 0,
        localFilters.value.school_year_id ? 1 : 0,
        localFilters.value.responsible_user_id ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

function updateStatus(value: string): void {
    localFilters.value.status = value as OpportunityStatus | '';
}

function updateGrade(value: string): void {
    localFilters.value.grade_id = value === 'all' ? '' : value;
}

function updateSchoolYear(value: string): void {
    localFilters.value.school_year_id = value === 'all' ? '' : value;
}

function updateResponsibleUser(value: string): void {
    localFilters.value.responsible_user_id = value === 'all' ? '' : value;
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.post(
        index.post().url,
        {
            status: localFilters.value.status,
            grade_id: localFilters.value.grade_id,
            school_year_id: localFilters.value.school_year_id,
            responsible_user_id: localFilters.value.responsible_user_id,
            per_page: localFilters.value.per_page,
        },
        { preserveScroll: true },
    );
}

function isTerminalStatus(status: OpportunityStatus): boolean {
    return status === 'matricula' || status === 'recusado';
}

const statusLabels: Record<OpportunityStatus, string> = {
    cadastro_inicial: 'Cadastro Inicial',
    agendamento: 'Agendamento',
    visita: 'Visita',
    matricula: 'Matrícula',
    recusado: 'Recusado',
};

const statusClasses: Record<OpportunityStatus, string> = {
    cadastro_inicial:
        'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20',
    agendamento:
        'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20',
    visita: 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/20',
    matricula:
        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
    recusado:
        'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
};

// Delete
const showDeleteModal = ref(false);
const opportunityToDelete = ref<Opportunity | null>(null);

function openDeleteModal(opportunity: Opportunity): void {
    opportunityToDelete.value = opportunity;
    showDeleteModal.value = true;
}

function handleDeleteSuccess(): void {
    showDeleteModal.value = false;
    opportunityToDelete.value = null;
    router.reload({ preserveUrl: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Oportunidades" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Oportunidades" />

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
                                                <SelectItem
                                                    value="cadastro_inicial"
                                                    >Cadastro
                                                    Inicial</SelectItem
                                                >
                                                <SelectItem value="agendamento"
                                                    >Agendamento</SelectItem
                                                >
                                                <SelectItem value="visita"
                                                    >Visita</SelectItem
                                                >
                                                <SelectItem value="matricula"
                                                    >Matrícula</SelectItem
                                                >
                                                <SelectItem value="recusado"
                                                    >Recusado</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-grade"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Série/Turma
                                        </Label>
                                        <Select
                                            :default-value="
                                                localFilters.grade_id || ''
                                            "
                                            @update:model-value="updateGrade"
                                        >
                                            <SelectTrigger
                                                id="filter-grade"
                                                class="h-8"
                                            >
                                                <SelectValue
                                                    placeholder="Todas"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all"
                                                    >Todas</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="grade in props.grades"
                                                    :key="grade.uuid"
                                                    :value="grade.uuid"
                                                >
                                                    {{ grade.nome }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-school-year"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Ano Letivo
                                        </Label>
                                        <Select
                                            :default-value="
                                                localFilters.school_year_id ||
                                                ''
                                            "
                                            @update:model-value="
                                                updateSchoolYear
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-school-year"
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
                                                    v-for="sy in props.schoolYears"
                                                    :key="sy.uuid"
                                                    :value="sy.uuid"
                                                >
                                                    {{ sy.nome }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label
                                            for="filter-responsible"
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Responsável
                                        </Label>
                                        <Select
                                            :default-value="
                                                localFilters.responsible_user_id ||
                                                ''
                                            "
                                            @update:model-value="
                                                updateResponsibleUser
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-responsible"
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
                                                    v-for="user in props.responsibleUsers"
                                                    :key="user.uuid"
                                                    :value="user.uuid"
                                                >
                                                    {{ user.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div
                                        class="flex items-center justify-end gap-2 pt-1"
                                    >
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
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nova Oportunidade
                </Link>

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
                                Aluno
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Responsável
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Série/Turma
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Ano Letivo
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="px-3 pb-3 text-left font-medium text-muted-foreground"
                            >
                                Atribuído a
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
                            v-for="opportunity in props.opportunities.data"
                            :key="opportunity.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ opportunity.student?.nome ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.guardian?.nome ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.grade?.nome ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.school_year?.nome ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                    :class="statusClasses[opportunity.status]"
                                >
                                    {{ statusLabels[opportunity.status] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.responsible_user?.name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            show({
                                                opportunity: opportunity.uuid,
                                            }).url
                                        "
                                        class="rounded p-1 hover:bg-muted"
                                        title="Ver detalhes"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        v-if="
                                            !isTerminalStatus(
                                                opportunity.status,
                                            )
                                        "
                                        :href="
                                            edit({
                                                opportunity: opportunity.uuid,
                                            }).url
                                        "
                                        class="rounded p-1 hover:bg-muted"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="
                                            !isTerminalStatus(
                                                opportunity.status,
                                            )
                                        "
                                        class="rounded p-1 text-destructive hover:bg-muted"
                                        title="Excluir"
                                        @click="openDeleteModal(opportunity)"
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
                    v-if="props.opportunities.data.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <BookOpen class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma oportunidade encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar os filtros aplicados.'
                                : 'Comece cadastrando uma nova oportunidade.'
                        }}
                    </p>
                    <Link
                        v-if="!hasActiveFilter"
                        :href="create(props.school.uuid).url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Oportunidade
                    </Link>
                </div>
            </div>

            <TablePagination :paginator="props.opportunities" />
        </div>

        <!-- Delete confirmation modal -->
        <ConfirmDeleteModal
            v-if="opportunityToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir a oportunidade de ${opportunityToDelete.student?.nome ?? 'este aluno'}?`"
            :action="
                destroy({
                    school_uuid: props.school.uuid,
                    opportunity: opportunityToDelete.uuid,
                }).url
            "
            success-message="Oportunidade excluída com sucesso."
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
