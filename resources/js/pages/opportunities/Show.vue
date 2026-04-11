<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    CalendarDays,
    Check,
    CheckSquare,
    Clock,
    CreditCard,
    History,
    Info,
    Mail,
    MessageSquare,
    Pencil,
    Phone,
    Plus,
    User,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import OutcomeModal from '@/components/Task/OutcomeModal.vue';
import TaskCreateModal from '@/components/Task/TaskCreateModal.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { taskTypeLabels } from '@/lib/task';
import { edit, index } from '@/routes/tenant/opportunities';
import type { BreadcrumbItem } from '@/types';
import type {
    FunnelStage,
    Opportunity,
    Outcome,
    Task,
    TaskType,
} from '@/types/crm';

const props = defineProps<{
    opportunity: Opportunity;
    tasks: Task[];
    outcomes: Outcome[];
    users: Array<{ id: number; uuid: string; name: string }>;
    funnel_stages: FunnelStage[];
    days_in_stage: number;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Oportunidades', href: index().url },
    { title: props.opportunity.student?.name ?? 'Oportunidade', href: '#' },
    { title: 'Detalhes', href: '#' },
];

// ── Tab state ────────────────────────────────────────────────────────────────
const activeTab = ref<'historico' | 'tarefas' | 'info'>('historico');

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

// ── Opportunity helpers ──────────────────────────────────────────────────────
function isTerminal(status: string): boolean {
    return status === 'matricula' || status === 'recusado';
}

// ── Task filtering ───────────────────────────────────────────────────────────
const historicTasks = computed(() =>
    [...props.tasks]
        .filter((t) => t.status === 'completed' || t.status === 'cancelled')
        .sort((a, b) => {
            const aDate = a.completed_at ?? a.cancelled_at ?? '';
            const bDate = b.completed_at ?? b.cancelled_at ?? '';
            return bDate.localeCompare(aDate);
        }),
);

const upcomingTasks = computed(() =>
    [...props.tasks]
        .filter((t) => t.status === 'open')
        .sort((a, b) => {
            const aDate = a.due_at ?? '';
            const bDate = b.due_at ?? '';
            return aDate.localeCompare(bDate);
        }),
);

// ── Task labels ──────────────────────────────────────────────────────────────
const taskStatusLabels: Record<string, string> = {
    open: 'Aberta',
    completed: 'Concluída',
    cancelled: 'Cancelada',
};

// ── Date helpers ─────────────────────────────────────────────────────────────
function formatDateOnly(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    const date = d.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
    const time = d.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
    return `${date} às ${time}`;
}

function taskLabel(task: Task): string {
    return task.task_type?.name ?? taskTypeLabels[task.type];
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="opportunity.student?.name ?? 'Oportunidade'" />

        <div class="space-y-6">
            <div class="rounded-lg border bg-card p-5">
                <div class="mb-5 flex items-center justify-between">
                    <span class="font-medium text-gray-700"
                        >Etapa da Oportunidade</span
                    >
                    <div
                        class="flex items-center gap-1.5 text-sm text-gray-500"
                    >
                        <Clock class="h-4 w-4" />
                        <span>{{ days_in_stage }} dias</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-600 text-xs font-semibold text-white uppercase"
                        >
                            {{
                                opportunity.responsible_user?.name?.charAt(0) ??
                                '?'
                            }}
                        </div>
                        <span class="text-sm font-medium">{{
                            opportunity.responsible_user?.name ?? '—'
                        }}</span>
                    </div>
                </div>

                <div class="flex items-start">
                    <template
                        v-for="(stage, idx) in funnel_stages"
                        :key="stage.slug"
                    >
                        <div class="flex flex-col items-center">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full border-2"
                                :class="{
                                    'border-teal-600 bg-teal-600':
                                        stage.state === 'completed',
                                    'border-blue-500 bg-white':
                                        stage.state === 'active',
                                    'border-gray-300 bg-white':
                                        stage.state === 'pending',
                                }"
                            >
                                <Check
                                    v-if="stage.state === 'completed'"
                                    class="h-4 w-4 text-white"
                                />
                                <div
                                    v-else-if="stage.state === 'active'"
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"
                                />
                            </div>
                            <span
                                class="mt-1 text-center text-xs"
                                :class="{
                                    'font-semibold text-blue-600':
                                        stage.state === 'active',
                                    'text-teal-700':
                                        stage.state === 'completed',
                                    'text-gray-400': stage.state === 'pending',
                                }"
                            >
                                {{ stage.label }}
                            </span>
                        </div>

                        <div
                            v-if="idx < funnel_stages.length - 1"
                            class="mx-1 mt-4 h-0.5 flex-1"
                            :class="{
                                'bg-teal-600': stage.state === 'completed',
                                'bg-blue-300': stage.state === 'active',
                                'bg-gray-200': stage.state === 'pending',
                            }"
                        />
                    </template>
                </div>

                <div
                    v-if="!isTerminal(opportunity.status)"
                    class="mt-4 flex justify-end"
                >
                    <Link
                        :href="edit({ opportunity: opportunity.uuid }).url"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted/50"
                    >
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </div>
            </div>

            <div
                class="flex flex-1 divide-x divide-gray-200 rounded-lg border bg-card p-5"
            >
                <!-- Painel: Responsável -->
                <div class="flex flex-1 items-start gap-4 pr-6">
                    <!-- left: label + icon + name (stacked) -->
                    <div class="flex flex-col items-center border-r pr-4">
                        <p class="mb-1 text-xs text-gray-400">Responsável</p>
                        <div class="flex items-center gap-2">
                            <User class="h-5 w-5 shrink-0 text-gray-400" />
                            <span class="text-base font-semibold">{{
                                opportunity.guardian?.name ?? '—'
                            }}</span>
                        </div>
                    </div>
                    <!-- right: contact data only -->
                    <div class="flex-1 space-y-1.5">
                        <div class="space-y-1.5 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <Phone class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    opportunity.guardian?.phone ?? '—'
                                }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Mail class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    opportunity.guardian?.email ?? '—'
                                }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    opportunity.guardian?.cpf ?? '—'
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel: Aluno -->
                <div class="flex flex-1 items-start gap-4 pl-6">
                    <!-- left: label + icon + name (stacked) -->
                    <div class="flex flex-col items-center border-r pr-4">
                        <p class="mb-1 text-xs text-gray-400">Aluno</p>
                        <div class="flex items-center gap-2">
                            <User class="h-5 w-5 shrink-0 text-gray-400" />
                            <span class="text-base font-semibold">{{
                                opportunity.student?.name ?? '—'
                            }}</span>
                        </div>
                    </div>
                    <!-- right: student data only -->
                    <div class="flex-1 space-y-1.5">
                        <div class="space-y-1.5 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <BookOpen class="h-3.5 w-3.5 shrink-0" />
                                <span>
                                    {{ opportunity.grade?.name ?? '—' }} -
                                    {{ opportunity.school_year?.name ?? '—' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Building2 class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    opportunity.school_unit?.name ?? '—'
                                }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Info class="h-3.5 w-3.5 shrink-0" />
                                <span>{{
                                    opportunity.segment?.name ?? '—'
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── Tarefa Ativa ────────────────────────────────────────────── -->
            <div
                v-if="activeTask"
                class="flex items-center justify-between gap-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-2 w-2 animate-pulse rounded-full bg-blue-500"
                    />
                    <span class="font-medium">{{ taskLabel(activeTask) }}</span>
                    <span
                        v-if="activeTask.due_at"
                        class="flex items-center gap-1 text-xs text-gray-500"
                    >
                        <Clock class="h-3 w-3" />
                        Vence {{ formatDateOnly(activeTask.due_at) }}
                    </span>
                    <span
                        v-if="activeTask.assigned_user"
                        class="flex items-center gap-1 text-xs text-gray-500"
                    >
                        <User class="h-3 w-3" />
                        {{ activeTask.assigned_user.name }}
                    </span>
                </div>
                <Button @click="showOutcomeModal = true">Tabular</Button>
            </div>
            <div
                v-else
                class="flex items-center justify-between gap-4 rounded-lg border border-dashed p-4"
            >
                <p class="text-sm text-muted-foreground">
                    Nenhuma tarefa aberta.
                </p>
                <Button variant="outline" @click="showTaskCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Nova Tarefa
                </Button>
            </div>

            <!-- ─── SEÇÃO 3: Sidebar + Conteúdo ───────────────────────────── -->
            <div class="flex gap-6">
                <!-- Sidebar -->
                <aside class="w-44 shrink-0">
                    <nav class="flex flex-col gap-1">
                        <button
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors"
                            :class="
                                activeTab === 'historico'
                                    ? 'rounded-lg bg-teal-50 font-semibold text-teal-700'
                                    : 'rounded-lg text-gray-600 hover:bg-gray-100'
                            "
                            @click="activeTab = 'historico'"
                        >
                            <History class="h-4 w-4 shrink-0" />
                            Histórico
                        </button>
                        <button
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors"
                            :class="
                                activeTab === 'tarefas'
                                    ? 'rounded-lg bg-teal-50 font-semibold text-teal-700'
                                    : 'rounded-lg text-gray-600 hover:bg-gray-100'
                            "
                            @click="activeTab = 'tarefas'"
                        >
                            <CheckSquare class="h-4 w-4 shrink-0" />
                            Próximas Tarefas
                        </button>
                        <button
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors"
                            :class="
                                activeTab === 'info'
                                    ? 'rounded-lg bg-teal-50 font-semibold text-teal-700'
                                    : 'rounded-lg text-gray-600 hover:bg-gray-100'
                            "
                            @click="activeTab = 'info'"
                        >
                            <Info class="h-4 w-4 shrink-0" />
                            Mais Informações
                        </button>
                    </nav>
                </aside>

                <!-- Área de conteúdo -->
                <div class="min-w-0 flex-1">
                    <!-- ── Aba: Histórico ─────────────────────────────────── -->
                    <div v-show="activeTab === 'historico'">
                        <div
                            v-if="historicTasks.length === 0"
                            class="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                        >
                            Nenhuma tarefa no histórico.
                        </div>
                        <div v-else class="pl-2">
                            <div
                                class="relative ml-1.5 space-y-6 border-l-2 border-teal-600"
                            >
                                <div
                                    v-for="task in historicTasks"
                                    :key="task.uuid"
                                    class="relative pl-6"
                                >
                                    <div
                                        class="absolute top-1 -left-1.5 h-3 w-3 rounded-full bg-teal-600"
                                    />
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div class="min-w-0 space-y-1">
                                            <p class="font-semibold">
                                                {{ taskLabel(task) }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{
                                                    taskStatusLabels[
                                                        task.status
                                                    ]
                                                }}
                                            </p>
                                            <div
                                                class="space-y-0.5 text-sm text-gray-600"
                                            >
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <CalendarDays
                                                        class="h-3.5 w-3.5 shrink-0"
                                                    />
                                                    <span
                                                        >Data de Início:
                                                        {{
                                                            formatDateOnly(
                                                                task.scheduled_at,
                                                            )
                                                        }}</span
                                                    >
                                                </div>
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <CalendarDays
                                                        class="h-3.5 w-3.5 shrink-0"
                                                    />
                                                    <span
                                                        >Previsão de Término:
                                                        {{
                                                            formatDateOnly(
                                                                task.due_at,
                                                            )
                                                        }}</span
                                                    >
                                                </div>
                                                <div
                                                    v-if="task.notes"
                                                    class="flex items-start gap-1.5"
                                                >
                                                    <MessageSquare
                                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                                    />
                                                    <span>{{
                                                        task.notes
                                                    }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-xs text-gray-400">
                                                Finalizada em:
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    formatDateTime(
                                                        task.completed_at ??
                                                            task.cancelled_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Aba: Próximas Tarefas ──────────────────────────── -->
                    <div v-show="activeTab === 'tarefas'">
                        <div
                            v-if="upcomingTasks.length === 0"
                            class="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                        >
                            Nenhuma tarefa pendente.
                        </div>
                        <div v-else class="pl-2">
                            <div
                                class="relative ml-1.5 space-y-6 border-l-2 border-teal-600"
                            >
                                <div
                                    v-for="task in upcomingTasks"
                                    :key="task.uuid"
                                    class="relative pl-6"
                                >
                                    <div
                                        class="absolute top-1 -left-1.5 h-3 w-3 rounded-full bg-teal-600"
                                    />
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div class="min-w-0 space-y-1">
                                            <p class="font-semibold">
                                                {{ taskLabel(task) }}
                                            </p>
                                            <div
                                                class="space-y-0.5 text-sm text-gray-600"
                                            >
                                                <div
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <CalendarDays
                                                        class="h-3.5 w-3.5 shrink-0"
                                                    />
                                                    <span
                                                        >Previsão de Término:
                                                        {{
                                                            formatDateOnly(
                                                                task.due_at,
                                                            )
                                                        }}</span
                                                    >
                                                </div>
                                                <div
                                                    v-if="task.notes"
                                                    class="flex items-start gap-1.5"
                                                >
                                                    <MessageSquare
                                                        class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                                    />
                                                    <span>{{
                                                        task.notes
                                                    }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-xs text-gray-400">
                                                Iniciado em:
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    formatDateTime(
                                                        task.scheduled_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Aba: Mais Informações ──────────────────────────── -->
                    <div v-show="activeTab === 'info'">
                        <!-- Card: Endereço -->
                        <div
                            class="mb-4 rounded-lg border border-gray-200 bg-white p-4"
                        >
                            <h3 class="mb-3 font-semibold">Endereço</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <p class="text-xs text-gray-400">CEP</p>
                                    <p class="text-sm">
                                        {{
                                            opportunity.guardian?.zip_code ??
                                            '—'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">
                                        Logradouro
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            opportunity.guardian?.street ?? '—'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Número</p>
                                    <p class="text-sm">
                                        {{
                                            opportunity.guardian?.number ?? '—'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Bairro</p>
                                    <p class="text-sm">
                                        {{
                                            opportunity.guardian
                                                ?.neighborhood ?? '—'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Cidade</p>
                                    <p class="text-sm">
                                        {{ opportunity.guardian?.city ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Estado</p>
                                    <p class="text-sm">
                                        {{ opportunity.guardian?.state ?? '—' }}
                                    </p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs text-gray-400">
                                        Complemento
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            opportunity.guardian?.complement ??
                                            '—'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card: Indicação -->
                        <div
                            class="mb-4 rounded-lg border border-gray-200 bg-white p-4"
                        >
                            <h3 class="mb-3 font-semibold">Indicação</h3>
                            <div>
                                <p class="text-xs text-gray-400">
                                    Observações / Indicação
                                </p>
                                <p class="mt-1 text-sm">
                                    {{ opportunity.indications ?? '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modais -->
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
