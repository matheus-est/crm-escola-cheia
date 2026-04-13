<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, Phone } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { taskTypeLabels } from '@/lib/task';
import { show } from '@/routes/tenant/opportunities';
import type { Outcome, Task } from '@/types/crm';

defineProps<{
    open: boolean;
    task: Task;
    outcomes: Outcome[];
    users: Array<{ uuid: string; name: string }>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    execute: [];
}>();

function formatDate(dateStr?: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <!-- Custom header with two-column layout -->
            <div class="flex items-start justify-between gap-4 pr-8 pb-4">
                <div class="flex items-center gap-3">
                    <div class="h-4 w-4 rounded-sm bg-primary" />
                    <h2 class="text-lg font-semibold">
                        {{ taskTypeLabels[task.type] ?? task.type }}
                    </h2>
                </div>
                <Button
                    v-if="task.status === 'open'"
                    variant="default"
                    size="sm"
                    @click="
                        emit('execute');
                        emit('update:open', false);
                    "
                >
                    Executar Tarefa
                </Button>
            </div>

            <div class="space-y-5 pb-4">
                <!-- Section 1: Contact -->
                <div v-if="task.opportunity?.guardian" class="space-y-1">
                    <p class="text-base font-semibold">
                        {{ task.opportunity.guardian.name }}
                    </p>
                    <p
                        v-if="task.opportunity.guardian.phone"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Phone class="h-3.5 w-3.5" />
                        {{ task.opportunity.guardian.phone }}
                    </p>
                    <p
                        v-if="task.opportunity.guardian.email"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Mail class="h-3.5 w-3.5" />
                        {{ task.opportunity.guardian.email }}
                    </p>
                </div>

                <hr class="border-border" />

                <!-- Section 2: Info grid -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Aluno:</p>
                        <p class="text-sm font-medium">
                            {{ task.opportunity?.student?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Segmento:</p>
                        <p class="text-sm font-medium">
                            {{ task.opportunity?.segment?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Série:</p>
                        <p class="text-sm font-medium">
                            {{ task.opportunity?.grade?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Ano Letivo:</p>
                        <p class="text-sm font-medium">
                            {{ task.opportunity?.school_year?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Unidade:</p>
                        <p class="text-sm font-medium">
                            {{ task.opportunity?.school_unit?.name ?? '—' }}
                        </p>
                    </div>
                </div>

                <hr class="border-border" />

                <!-- Section 3: Dates grid -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Data de Início:
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(task.created_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Previsão de Término:
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(task.due_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Data de Término:
                        </p>
                        <p class="text-sm font-medium">
                            {{ formatDate(task.completed_at) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Comentário:</p>
                        <p class="text-sm font-medium">
                            {{ task.notes ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <DialogFooter class="flex w-full items-center justify-between">
                <Link
                    v-if="task.opportunity?.uuid"
                    :href="show({ opportunity: task.opportunity.uuid }).url"
                    class="text-sm text-primary underline-offset-4 hover:underline"
                    @click="emit('update:open', false)"
                >
                    Ver Oportunidade
                </Link>
                <span v-else />
                <Button variant="outline" @click="emit('update:open', false)">
                    Fechar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
