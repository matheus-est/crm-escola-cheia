<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Eye,
    Filter,
    KanbanSquare,
    List,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Heading from '@/components/Heading.vue';
import KanbanBoard from '@/components/opportunities/KanbanBoard.vue';
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
    isTerminalStatus,
    statusClasses,
    statusLabels,
} from '@/lib/opportunityStatus';
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
    KanbanColumns,
    LeadSource,
    Opportunity,
    OpportunityStatus,
    PaginatedOpportunities,
    SchoolUnit,
    SchoolYear,
    Segment,
    TenantUser,
} from '@/types/crm';

const props = defineProps<{
    view: 'kanban' | 'list';
    opportunities: PaginatedOpportunities | null;
    kanban_columns: KanbanColumns | null;
    grades: Grade[];
    schoolYears: SchoolYear[];
    leadSources: LeadSource[];
    responsibleUsers: TenantUser[];
    segments: Segment[];
    schoolUnits: SchoolUnit[];
    filters?: {
        status?: string;
        grade_id?: string;
        school_year_id?: string;
        responsible_user_id?: string;
        lead_source_id?: string;
        segment_id?: string;
        school_unit_id?: string;
        registration_type?: string;
        date_from?: string;
        date_to?: string;
        student_cpf?: string;
        guardian_cpf?: string;
    };
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: '#' },
];

const localFilters = ref<{
    status: OpportunityStatus | '';
    grade_id: string;
    school_year_id: string;
    responsible_user_id: string;
    lead_source_id: string;
    segment_id: string;
    school_unit_id: string;
    registration_type: string;
    date_from: string;
    date_to: string;
    student_cpf: string;
    guardian_cpf: string;
    per_page: number;
}>({
    status: (props.filters?.status as OpportunityStatus | '') ?? '',
    grade_id: props.filters?.grade_id ?? '',
    school_year_id: props.filters?.school_year_id ?? '',
    responsible_user_id: props.filters?.responsible_user_id ?? '',
    lead_source_id: props.filters?.lead_source_id ?? '',
    segment_id: props.filters?.segment_id ?? '',
    school_unit_id: props.filters?.school_unit_id ?? '',
    registration_type: props.filters?.registration_type ?? '',
    date_from: props.filters?.date_from ?? '',
    date_to: props.filters?.date_to ?? '',
    student_cpf: props.filters?.student_cpf ?? '',
    guardian_cpf: props.filters?.guardian_cpf ?? '',
    per_page: 10,
});

const currentFilters = computed<Record<string, string | number | undefined>>(
    () => ({
        status: localFilters.value.status || undefined,
        grade_id: localFilters.value.grade_id || undefined,
        school_year_id: localFilters.value.school_year_id || undefined,
        responsible_user_id:
            localFilters.value.responsible_user_id || undefined,
        lead_source_id: localFilters.value.lead_source_id || undefined,
        segment_id: localFilters.value.segment_id || undefined,
        school_unit_id: localFilters.value.school_unit_id || undefined,
        registration_type: localFilters.value.registration_type || undefined,
        date_from: localFilters.value.date_from || undefined,
        date_to: localFilters.value.date_to || undefined,
        student_cpf: localFilters.value.student_cpf || undefined,
        guardian_cpf: localFilters.value.guardian_cpf || undefined,
        per_page: localFilters.value.per_page,
        view: props.view,
    }),
);

const hasActiveFilter = computed(
    () =>
        !!(
            localFilters.value.status ||
            localFilters.value.grade_id ||
            localFilters.value.school_year_id ||
            localFilters.value.responsible_user_id ||
            localFilters.value.lead_source_id ||
            localFilters.value.segment_id ||
            localFilters.value.school_unit_id ||
            localFilters.value.registration_type ||
            localFilters.value.date_from ||
            localFilters.value.date_to ||
            localFilters.value.student_cpf ||
            localFilters.value.guardian_cpf
        ),
);

const filterCount = computed(() =>
    [
        localFilters.value.status ? 1 : 0,
        localFilters.value.grade_id ? 1 : 0,
        localFilters.value.school_year_id ? 1 : 0,
        localFilters.value.responsible_user_id ? 1 : 0,
        localFilters.value.lead_source_id ? 1 : 0,
        localFilters.value.segment_id ? 1 : 0,
        localFilters.value.school_unit_id ? 1 : 0,
        localFilters.value.registration_type ? 1 : 0,
        localFilters.value.date_from ? 1 : 0,
        localFilters.value.date_to ? 1 : 0,
        localFilters.value.student_cpf ? 1 : 0,
        localFilters.value.guardian_cpf ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

function updateStatus(value: string): void {
    localFilters.value.status =
        value === 'all' ? '' : (value as OpportunityStatus | '');
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

function updateLeadSource(value: string): void {
    localFilters.value.lead_source_id = value === 'all' ? '' : value;
}

function updateSegment(value: string): void {
    localFilters.value.segment_id = value === 'all' ? '' : value;
}

function updateSchoolUnit(value: string): void {
    localFilters.value.school_unit_id = value === 'all' ? '' : value;
}

function updateRegistrationType(value: string): void {
    localFilters.value.registration_type = value === 'all' ? '' : value;
}

function updatePerPage(value: string): void {
    localFilters.value.per_page = parseInt(value) || 10;
    applyFilters();
}

function applyFilters(): void {
    router.get(
        index().url,
        {
            status: localFilters.value.status || undefined,
            grade_id: localFilters.value.grade_id || undefined,
            school_year_id: localFilters.value.school_year_id || undefined,
            responsible_user_id:
                localFilters.value.responsible_user_id || undefined,
            lead_source_id: localFilters.value.lead_source_id || undefined,
            segment_id: localFilters.value.segment_id || undefined,
            school_unit_id: localFilters.value.school_unit_id || undefined,
            registration_type:
                localFilters.value.registration_type || undefined,
            date_from: localFilters.value.date_from || undefined,
            date_to: localFilters.value.date_to || undefined,
            student_cpf: localFilters.value.student_cpf || undefined,
            guardian_cpf: localFilters.value.guardian_cpf || undefined,
            per_page: localFilters.value.per_page,
            view: props.view,
        },
        { preserveUrl: true },
    );
}

function formatCpf(value: string): string {
    return value
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

function clearFilters(): void {
    router.get(index().url, { view: props.view }, { preserveUrl: true });
}

function switchView(newView: 'kanban' | 'list'): void {
    router.get(
        index().url,
        { ...currentFilters.value, view: newView },
        { preserveUrl: true },
    );
}

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
            <div class="flex items-center justify-between">
                <Heading title="Oportunidades" />

                <Accordion
                    type="single"
                    collapsible
                    class="w-[560px]"
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
                                    class="space-y-3"
                                >
                                    <!-- Row 1: Status | Série/Turma -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-status"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Status
                                            </Label>
                                            <Select
                                                :default-value="
                                                    localFilters.status || 'all'
                                                "
                                                @update:model-value="
                                                    updateStatus
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
                                                    <SelectItem value="all"
                                                        >Todos</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="cadastro_inicial"
                                                        >Cadastro
                                                        Inicial</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="agendamento"
                                                        >Agendamento</SelectItem
                                                    >
                                                    <SelectItem value="visita"
                                                        >Visita</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="matricula"
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
                                                    localFilters.grade_id ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateGrade
                                                "
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
                                                        {{ grade.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <!-- Row 2: Ano Letivo | Responsável -->
                                    <div class="grid grid-cols-2 gap-3">
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
                                                    'all'
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
                                                        {{ sy.name }}
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
                                                    'all'
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
                                    </div>

                                    <!-- Row 3: Origem | Segmento -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-lead-source"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Origem
                                            </Label>
                                            <Select
                                                :default-value="
                                                    localFilters.lead_source_id ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateLeadSource
                                                "
                                            >
                                                <SelectTrigger
                                                    id="filter-lead-source"
                                                    class="h-8"
                                                >
                                                    <SelectValue
                                                        placeholder="Todas"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all"
                                                        >Todas as
                                                        origens</SelectItem
                                                    >
                                                    <SelectItem
                                                        v-for="ls in props.leadSources"
                                                        :key="ls.uuid"
                                                        :value="ls.uuid"
                                                    >
                                                        {{ ls.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
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
                                                    localFilters.segment_id ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateSegment
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
                                                        >Todos os
                                                        segmentos</SelectItem
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
                                    </div>

                                    <!-- Row 4: Unidade | Tipo de Cadastro -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-school-unit"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Unidade
                                            </Label>
                                            <Select
                                                :default-value="
                                                    localFilters.school_unit_id ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateSchoolUnit
                                                "
                                            >
                                                <SelectTrigger
                                                    id="filter-school-unit"
                                                    class="h-8"
                                                >
                                                    <SelectValue
                                                        placeholder="Todas"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all"
                                                        >Todas as
                                                        unidades</SelectItem
                                                    >
                                                    <SelectItem
                                                        v-for="unit in props.schoolUnits"
                                                        :key="unit.uuid"
                                                        :value="unit.uuid"
                                                    >
                                                        {{ unit.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-registration-type"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Tipo de Cadastro
                                            </Label>
                                            <Select
                                                :default-value="
                                                    localFilters.registration_type ||
                                                    'all'
                                                "
                                                @update:model-value="
                                                    updateRegistrationType
                                                "
                                            >
                                                <SelectTrigger
                                                    id="filter-registration-type"
                                                    class="h-8"
                                                >
                                                    <SelectValue
                                                        placeholder="Todos"
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all"
                                                        >Todos os
                                                        tipos</SelectItem
                                                    >
                                                    <SelectItem
                                                        value="agendamento"
                                                        >Agendamento</SelectItem
                                                    >
                                                    <SelectItem value="evento"
                                                        >Evento</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <!-- Row 5: CPF do Aluno | CPF do Responsável -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-student-cpf"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                CPF do Aluno
                                            </Label>
                                            <Input
                                                id="filter-student-cpf"
                                                :value="
                                                    localFilters.student_cpf
                                                "
                                                class="h-8 text-xs"
                                                placeholder="000.000.000-00"
                                                @input="
                                                    (e) => {
                                                        const v = formatCpf(
                                                            (
                                                                e.target as HTMLInputElement
                                                            ).value,
                                                        );
                                                        (
                                                            e.target as HTMLInputElement
                                                        ).value = v;
                                                        localFilters.student_cpf =
                                                            v;
                                                    }
                                                "
                                            />
                                        </div>

                                        <div class="space-y-1.5">
                                            <Label
                                                for="filter-guardian-cpf"
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                CPF do Responsável
                                            </Label>
                                            <Input
                                                id="filter-guardian-cpf"
                                                :value="
                                                    localFilters.guardian_cpf
                                                "
                                                class="h-8 text-xs"
                                                placeholder="000.000.000-00"
                                                @input="
                                                    (e) => {
                                                        const v = formatCpf(
                                                            (
                                                                e.target as HTMLInputElement
                                                            ).value,
                                                        );
                                                        (
                                                            e.target as HTMLInputElement
                                                        ).value = v;
                                                        localFilters.guardian_cpf =
                                                            v;
                                                    }
                                                "
                                            />
                                        </div>
                                    </div>

                                    <!-- Período -->
                                    <div class="space-y-1.5">
                                        <Label
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Período
                                        </Label>
                                        <div class="flex gap-2">
                                            <Input
                                                v-model="localFilters.date_from"
                                                type="date"
                                                class="h-8 text-xs"
                                                placeholder="De"
                                            />
                                            <Input
                                                v-model="localFilters.date_to"
                                                type="date"
                                                class="h-8 text-xs"
                                                placeholder="Até"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center justify-between gap-2 pt-1"
                                    >
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
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

            <!-- Page header — line 2: New button | View toggle | PerPageSelect -->
            <div class="flex items-center justify-between">
                <Link
                    :href="create().url"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nova Oportunidade
                </Link>

                <div class="flex rounded-lg border">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-l-lg px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            props.view === 'kanban'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="switchView('kanban')"
                    >
                        <KanbanSquare class="h-4 w-4" />
                        Kanban
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-r-lg border-l px-3 py-2 text-sm font-medium transition-colors"
                        :class="
                            props.view === 'list'
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                        "
                        @click="switchView('list')"
                    >
                        <List class="h-4 w-4" />
                        Lista
                    </button>
                </div>

                <div class="flex items-center">
                    <PerPageSelect
                        v-if="props.view === 'list'"
                        :model-value="String(localFilters.per_page)"
                        @update:model-value="updatePerPage"
                    />
                    <div v-else class="w-32" />
                </div>
            </div>

            <!-- Kanban View -->
            <div v-if="props.view === 'kanban'">
                <KanbanBoard
                    v-if="props.kanban_columns"
                    :columns="props.kanban_columns"
                    :filters="currentFilters"
                />
            </div>

            <!-- List View -->
            <div v-else>
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
                            v-for="opportunity in props.opportunities?.data"
                            :key="opportunity.uuid"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-3 py-3 font-medium text-foreground">
                                {{ opportunity.student?.name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.guardian?.name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.grade?.name ?? '—' }}
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">
                                {{ opportunity.school_year?.name ?? '—' }}
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
                    v-if="!props.opportunities?.data.length"
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
                        :href="create().url"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nova Oportunidade
                    </Link>
                </div>

                <TablePagination
                    v-if="props.opportunities"
                    :paginator="props.opportunities"
                />
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <ConfirmDeleteModal
            v-if="opportunityToDelete"
            v-model:open="showDeleteModal"
            title="Confirmar Exclusão"
            :message="`Tem certeza que deseja excluir a oportunidade de ${opportunityToDelete.student?.name ?? 'este aluno'}?`"
            :action="
                destroy({
                    opportunity: opportunityToDelete.uuid,
                }).url
            "
            success-message="Oportunidade excluída com sucesso."
            @success="handleDeleteSuccess"
        />
    </AppLayout>
</template>
