<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Clock, Pencil, Plus, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import OutcomeModal from '@/components/Task/OutcomeModal.vue';
import TaskCreateModal from '@/components/Task/TaskCreateModal.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { taskTypeLabels } from '@/lib/task';
import { edit, index } from '@/routes/tenant/opportunities';
import type { BreadcrumbItem } from '@/types';
import type { Opportunity, Outcome, Task, TaskType } from '@/types/crm';

const props = defineProps<{
    opportunity: Opportunity;
    tasks: Task[];
    outcomes: Outcome[];
    users: Array<{ id: number; uuid: string; name: string }>;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index().url },
    { title: props.opportunity.student?.name ?? 'Oportunidade', href: '#' },
    { title: 'Detalhes', href: '#' },
];

// ── Task state ──────────────────────────────────────────────────────────────
const activeTask = computed(
    () => props.tasks.find((t) => t.status === 'open') ?? null,
);
const outcomesForActiveTask = computed(() =>
    activeTask.value
        ? props.outcomes.filter((o) => o.task_type === activeTask.value!.type)
        : [],
);

const showOutcomeModal = ref(false);
const showTaskCreateModal = ref(false);
const pendingWindowType = ref<TaskType | null>(null);

function onTaskCompleted(result: { open_window: string | null }): void {
    router.reload({ preserveUrl: true });
    if (result.open_window !== null) {
        pendingWindowType.value = result.open_window as TaskType;
        showTaskCreateModal.value = true;
    }
}

function onTaskCreated(): void {
    pendingWindowType.value = null;
    router.reload({ preserveUrl: true });
}

// ── Opportunity display ──────────────────────────────────────────────────────
function isTerminal(status: string): boolean {
    return status === 'matricula' || status === 'recusado';
}

const statusLabels: Record<string, string> = {
    cadastro_inicial: 'Cadastro Inicial',
    agendamento: 'Agendamento',
    visita: 'Visita',
    matricula: 'Matrícula',
    recusado: 'Recusado',
};

const statusClasses: Record<string, string> = {
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

const taskStatusClasses: Record<string, string> = {
    open: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20',
    completed:
        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20',
    cancelled:
        'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
};

const taskStatusLabels: Record<string, string> = {
    open: 'Aberta',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="opportunity.student?.name ?? 'Oportunidade'" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ opportunity.student?.name ?? 'Sem aluno' }}
                    </h1>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                            :class="statusClasses[opportunity.status]"
                        >
                            {{
                                statusLabels[opportunity.status] ??
                                opportunity.status
                            }}
                        </span>
                        <span
                            v-if="opportunity.renitente_count > 0"
                            class="text-xs text-muted-foreground"
                        >
                            {{ opportunity.renitente_count }}x renitente
                        </span>
                    </div>
                </div>

                <Link
                    v-if="!isTerminal(opportunity.status)"
                    :href="edit({ opportunity: opportunity.uuid }).url"
                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted/50"
                >
                    <Pencil class="h-4 w-4" />
                    Editar
                </Link>
            </div>

            <!-- Info grid -->
            <div
                class="grid grid-cols-1 gap-4 rounded-lg border bg-card p-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Responsável
                    </p>
                    <p class="text-sm">
                        {{ opportunity.guardian?.name ?? '—' }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Série
                    </p>
                    <p class="text-sm">{{ opportunity.grade?.name ?? '—' }}</p>
                </div>
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Ano Letivo
                    </p>
                    <p class="text-sm">
                        {{ opportunity.school_year?.name ?? '—' }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Origem
                    </p>
                    <p class="text-sm">
                        {{ opportunity.lead_source?.name ?? '—' }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Segmento
                    </p>
                    <p class="text-sm">
                        {{ opportunity.segment?.name ?? '—' }}
                    </p>
                </div>
                <div class="space-y-0.5">
                    <p
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Responsável comercial
                    </p>
                    <p class="text-sm">
                        {{ opportunity.responsible_user?.name ?? '—' }}
                    </p>
                </div>
            </div>

            <!-- Active task panel -->
            <div>
                <h2 class="mb-3 text-base font-semibold">Tarefa Ativa</h2>

                <!-- Has open task -->
                <div
                    v-if="activeTask"
                    class="flex items-center justify-between gap-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-800 dark:bg-blue-400/5"
                >
                    <div class="space-y-1">
                        <p class="font-medium">
                            {{ taskTypeLabels[activeTask.type] }}
                        </p>
                        <div
                            class="flex items-center gap-4 text-xs text-muted-foreground"
                        >
                            <span
                                v-if="activeTask.due_at"
                                class="flex items-center gap-1"
                            >
                                <Clock class="h-3 w-3" />
                                Vence {{ formatDate(activeTask.due_at) }}
                            </span>
                            <span
                                v-if="activeTask.assigned_user"
                                class="flex items-center gap-1"
                            >
                                <User class="h-3 w-3" />
                                {{ activeTask.assigned_user.name }}
                            </span>
                        </div>
                    </div>
                    <Button @click="showOutcomeModal = true">Tabular</Button>
                </div>

                <!-- No open task -->
                <div
                    v-else
                    class="flex items-center justify-between gap-4 rounded-lg border border-dashed p-4"
                >
                    <p class="text-sm text-muted-foreground">
                        Nenhuma tarefa aberta.
                    </p>
                    <Button
                        variant="outline"
                        @click="showTaskCreateModal = true"
                    >
                        <Plus class="mr-2 h-4 w-4" />
                        Nova Tarefa
                    </Button>
                </div>
            </div>

            <!-- Task history -->
            <div>
                <h2 class="mb-3 text-base font-semibold">
                    Histórico de Tarefas
                </h2>

                <div
                    v-if="tasks.length === 0"
                    class="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                >
                    Nenhuma tarefa registrada.
                </div>

                <div v-else class="overflow-hidden rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30">
                                <th
                                    class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >
                                    Resultado
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >
                                    <span class="flex items-center gap-1">
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        Vencimento
                                    </span>
                                </th>
                                <th
                                    class="px-4 py-3 text-left font-medium text-muted-foreground"
                                >
                                    Conclusão
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/50">
                            <tr
                                v-for="task in tasks"
                                :key="task.uuid"
                                class="transition-colors hover:bg-muted/20"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ taskTypeLabels[task.type] }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                        :class="taskStatusClasses[task.status]"
                                    >
                                        {{ taskStatusLabels[task.status] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ task.outcome?.name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatDate(task.due_at) }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatDate(task.completed_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <OutcomeModal
            v-if="activeTask"
            v-model:open="showOutcomeModal"
            :task="activeTask"
            :outcomes="outcomesForActiveTask"
            :users="users"
            @completed="onTaskCompleted"
        />

        <TaskCreateModal
            v-model:open="showTaskCreateModal"
            :opportunity-uuid="opportunity.uuid"
            :users="users"
            :default-type="pendingWindowType ?? undefined"
            @created="onTaskCreated"
        />
    </AppLayout>
</template>
