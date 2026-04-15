<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarIcon,
    CheckCircle,
    ChevronRight,
    CircleDashed,
    Filter,
    ListTodo,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import TablePagination from '@/components/TablePagination.vue';
import ExecuteTaskModal from '@/components/Task/ExecuteTaskModal.vue';
import TaskDetailModal from '@/components/Task/TaskDetailModal.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
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
import { show } from '@/routes/tenant/opportunities';
import { index } from '@/routes/tenant/tasks';
import type { BreadcrumbItem } from '@/types';
import type { Outcome, PaginatedTasks, Task } from '@/types/crm';

interface Filters {
    status: string;
    type: string;
    assigned_user_uuid: string;
    date_from: string;
    date_to: string;
}

const props = defineProps<{
    tasks: PaginatedTasks;
    filters: Filters;
    users: Array<{ uuid: string; name: string }>;
    outcomes: Outcome[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Tarefas', href: '#' }];

const localFilters = ref<Filters>({
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    assigned_user_uuid: props.filters.assigned_user_uuid ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const filterCount = computed(() =>
    [
        localFilters.value.status ? 1 : 0,
        localFilters.value.type ? 1 : 0,
        localFilters.value.assigned_user_uuid ? 1 : 0,
        localFilters.value.date_from ? 1 : 0,
        localFilters.value.date_to ? 1 : 0,
    ].reduce((a, b) => a + b, 0),
);

const hasActiveFilter = computed(() => filterCount.value > 0);

function updateFilterStatus(value: string): void {
    localFilters.value.status = value === 'all' ? '' : value;
}

function updateFilterType(value: string): void {
    localFilters.value.type = value === 'all' ? '' : value;
}

function updateFilterUser(value: string): void {
    localFilters.value.assigned_user_uuid = value === 'all' ? '' : value;
}

function applyFilters(): void {
    router.get(
        index().url,
        {
            status: localFilters.value.status || undefined,
            type: localFilters.value.type || undefined,
            assigned_user_uuid:
                localFilters.value.assigned_user_uuid || undefined,
            date_from: localFilters.value.date_from || undefined,
            date_to: localFilters.value.date_to || undefined,
        },
        { preserveUrl: true, preserveState: true },
    );
}

function clearFilters(): void {
    localFilters.value.status = '';
    localFilters.value.type = '';
    localFilters.value.assigned_user_uuid = '';
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
    router.visit(index().url);
}

// — Modal state —
const showDetailModal = ref(false);
const showExecuteModal = ref(false);
const selectedTask = ref<Task | null>(null);

function openDetailModal(task: Task): void {
    selectedTask.value = task;
    showDetailModal.value = true;
}

function onDetailExecute(): void {
    showDetailModal.value = false;
    showExecuteModal.value = true;
}

function onTaskCompleted(): void {
    showExecuteModal.value = false;
    selectedTask.value = null;
    router.reload({ preserveUrl: true });
}

function onOpenTaskModal(): void {
    // backend handles follow-up task creation automatically
    showExecuteModal.value = false;
    selectedTask.value = null;
    router.reload({ preserveUrl: true });
}

// — Table Helpers —
function statusClass(status: string): string {
    const classes: Record<string, string> = {
        open: 'border-transparent bg-amber-500/15 text-amber-700 hover:bg-amber-500/25 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20',
        completed:
            'border-transparent bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20',
        cancelled:
            'border-transparent bg-slate-500/15 text-slate-700 hover:bg-slate-500/25 dark:bg-slate-500/10 dark:text-slate-400 dark:hover:bg-slate-500/20',
    };
    return classes[status] || 'bg-muted text-muted-foreground';
}

function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        open: 'Aberta',
        completed: 'Concluída',
        cancelled: 'Cancelada',
    };
    return labels[status] || status;
}

function typeLabel(type: string): string {
    const labels: Record<string, string> = {
        retorno_ligacao: 'Retorno de Ligação',
        agendamento: 'Agendamento',
        lembrete_agenda: 'Lembrete de Agendamento',
        reagendamento: 'Reagendamento',
        double_check: 'Double Check',
        provavel_matricula: 'Provável Matrícula',
        evento: 'Evento',
        lembrete_evento: 'Lembrete de Evento',
        reagendamento_evento: 'Reagendamento de Evento',
        double_check_evento: 'Double Check de Evento',
    };
    return labels[type] || type;
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tarefas" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <Heading title="Tarefas" />

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
                            <span>Pesquisar e Filtrar</span>
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
                                        <Label for="filter-status"
                                            >Status</Label
                                        >
                                        <Select
                                            :default-value="
                                                localFilters.status || 'all'
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
                                                <SelectItem value="all"
                                                    >Todos</SelectItem
                                                >
                                                <SelectItem value="open"
                                                    >Abertas</SelectItem
                                                >
                                                <SelectItem value="completed"
                                                    >Concluídas</SelectItem
                                                >
                                                <SelectItem value="cancelled"
                                                    >Canceladas</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="filter-type">Tipo</Label>
                                        <Select
                                            :default-value="
                                                localFilters.type || 'all'
                                            "
                                            @update:model-value="
                                                updateFilterType
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-type"
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
                                                    value="retorno_ligacao"
                                                    >Retorno de
                                                    Ligação</SelectItem
                                                >
                                                <SelectItem value="agendamento"
                                                    >Agendamento</SelectItem
                                                >
                                                <SelectItem
                                                    value="lembrete_agenda"
                                                    >Lembrete de
                                                    Agendamento</SelectItem
                                                >
                                                <SelectItem
                                                    value="reagendamento"
                                                    >Reagendamento</SelectItem
                                                >
                                                <SelectItem value="double_check"
                                                    >Double Check</SelectItem
                                                >
                                                <SelectItem
                                                    value="provavel_matricula"
                                                    >Provável
                                                    Matrícula</SelectItem
                                                >
                                                <SelectItem value="evento"
                                                    >Evento</SelectItem
                                                >
                                                <SelectItem
                                                    value="lembrete_evento"
                                                    >Lembrete de
                                                    Evento</SelectItem
                                                >
                                                <SelectItem
                                                    value="reagendamento_evento"
                                                    >Reagendamento de
                                                    Evento</SelectItem
                                                >
                                                <SelectItem
                                                    value="double_check_evento"
                                                    >Double Check de
                                                    Evento</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label for="filter-user"
                                            >Responsável</Label
                                        >
                                        <Select
                                            :default-value="
                                                localFilters.assigned_user_uuid ||
                                                'all'
                                            "
                                            @update:model-value="
                                                updateFilterUser
                                            "
                                        >
                                            <SelectTrigger
                                                id="filter-user"
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
                                                    v-for="user in props.users"
                                                    :key="user.uuid"
                                                    :value="user.uuid"
                                                >
                                                    {{ user.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1.5">
                                        <Label>Período</Label>
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

            <!-- Table -->
            <div
                class="rounded-lg border bg-card text-card-foreground shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted"
                        >
                            <th
                                class="h-12 w-[120px] px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Detalhes e Contato
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Data/Prazo
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Responsável
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Ação
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        <tr
                            v-for="task in props.tasks.data"
                            :key="task.uuid"
                            class="cursor-pointer transition-colors hover:bg-muted/30"
                            @click="openDetailModal(task)"
                        >
                            <td class="px-4 py-4">
                                <Badge :class="statusClass(task.status)">
                                    <CircleDashed
                                        v-if="task.status === 'open'"
                                        class="mr-1 h-3 w-3"
                                    />
                                    <CheckCircle
                                        v-else-if="task.status === 'completed'"
                                        class="mr-1 h-3 w-3"
                                    />
                                    <XCircle v-else class="mr-1 h-3 w-3" />
                                    {{ statusLabel(task.status) }}
                                </Badge>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="font-medium text-foreground">
                                        {{ typeLabel(task.type) }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        Para:
                                        {{
                                            task.opportunity?.guardian?.name ??
                                            task.opportunity?.student?.name ??
                                            '—'
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div
                                    class="flex items-center gap-1.5 whitespace-nowrap text-muted-foreground"
                                >
                                    <CalendarIcon class="h-3.5 w-3.5" />
                                    <span>
                                        {{
                                            task.is_schedule
                                                ? formatDate(task.scheduled_at)
                                                : formatDate(task.due_at)
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <p class="text-foreground">
                                    {{
                                        task.assigned_user?.name ??
                                        task.opportunity?.responsible_user
                                            ?.name ??
                                        'Não atribuído'
                                    }}
                                </p>
                            </td>

                            <td class="px-4 py-4 text-right">
                                <Link
                                    v-if="task.opportunity"
                                    :href="
                                        show({
                                            opportunity: task.opportunity.uuid,
                                        }).url
                                    "
                                    class="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                                    @click.stop
                                >
                                    Ver Oportunidade
                                    <ChevronRight class="ml-1 h-4 w-4" />
                                </Link>
                                <span
                                    v-else
                                    class="text-muted-foreground opacity-50"
                                    >—</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div
                    v-if="props.tasks.data.length === 0"
                    class="flex flex-col items-center justify-center border-t py-16 text-center"
                >
                    <div class="mb-4 rounded-full bg-muted/50 p-4">
                        <ListTodo class="h-8 w-8 text-muted-foreground/50" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nenhuma tarefa encontrada
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            hasActiveFilter
                                ? 'Tente ajustar ou remover os filtros aplicados.'
                                : 'Seu histórico está limpo.'
                        }}
                    </p>
                    <Button
                        v-if="hasActiveFilter"
                        variant="outline"
                        class="mt-4"
                        @click="clearFilters"
                    >
                        Limpar Filtros
                    </Button>
                </div>
            </div>

            <TablePagination :paginator="props.tasks" />
        </div>

        <!-- Modais -->
        <TaskDetailModal
            v-if="selectedTask"
            v-model:open="showDetailModal"
            :task="selectedTask"
            :outcomes="
                (props.outcomes as Outcome[]).filter(
                    (o) => o.task_type === selectedTask?.type,
                )
            "
            :users="users"
            @execute="onDetailExecute"
        />

        <ExecuteTaskModal
            v-if="selectedTask && showExecuteModal"
            v-model:open="showExecuteModal"
            :task="selectedTask"
            :outcomes="
                (props.outcomes as Outcome[]).filter(
                    (o) => o.task_type === selectedTask?.type,
                )
            "
            :users="users"
            @open-task-modal="onOpenTaskModal"
            @completed="onTaskCompleted"
        />
    </AppLayout>
</template>
