<script setup lang="ts">
import type { AcceptableValue } from 'reka-ui';
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { refusalCategoryLabels, taskTypeLabels } from '@/lib/task';
import { complete } from '@/routes/tenant/tasks';
import type { Outcome, Task } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    task: Task;
    outcomes: Outcome[];
    users: Array<{ uuid: string; name: string }>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    completed: [result: { open_window: string | null }];
    'open-task-modal': [payload: { type: string }];
}>();

const opportunityStatusLabels: Record<string, string> = {
    cadastro_inicial: 'Cadastro Inicial',
    agendamento: 'Agendamento',
    visita: 'Visita',
    matricula: 'Matrícula',
    recusado: 'Recusado',
};

const selectedOutcome = ref<Outcome | null>(null);
const processing = ref(false);
const generalError = ref<string | null>(null);
const form = reactive({
    outcome_uuid: '',
    notes: '',
    refusal_category: '',
    refusal_detail: '',
});
const errors = reactive<Record<string, string>>({});

const isRefusal = computed(() => selectedOutcome.value?.is_refusal ?? false);

watch(
    () => props.open,
    (val) => {
        if (val) {
            selectedOutcome.value = null;
            Object.assign(form, {
                outcome_uuid: '',
                notes: '',
                refusal_category: '',
                refusal_detail: '',
            });
            Object.keys(errors).forEach((k) => delete errors[k]);
            generalError.value = null;
        }
    },
);

function selectOutcome(outcome: Outcome): void {
    selectedOutcome.value = outcome;
    form.outcome_uuid = outcome.uuid;
    if (!outcome.is_refusal) {
        form.refusal_category = '';
        form.refusal_detail = '';
    }
}

function updateRefusalCategory(value: AcceptableValue): void {
    form.refusal_category = value as string;
}

async function submit(): Promise<void> {
    processing.value = true;
    generalError.value = null;
    Object.keys(errors).forEach((k) => delete errors[k]);

    const csrfToken =
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? '';

    try {
        const res = await fetch(complete({ task: props.task.uuid }).url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                outcome_uuid: form.outcome_uuid,
                notes: form.notes || null,
                ...(isRefusal.value
                    ? {
                          refusal_category: form.refusal_category,
                          refusal_detail: form.refusal_detail || null,
                      }
                    : {}),
            }),
        });

        if (!res.ok) {
            const err = (await res.json()) as {
                message?: string;
                errors?: Record<string, string[]>;
            };
            if (err.errors) {
                Object.entries(err.errors).forEach(([key, messages]) => {
                    errors[key] = messages[0] ?? '';
                });
            } else if (err.message) {
                generalError.value = err.message;
            } else {
                generalError.value =
                    'Ocorreu um erro ao executar a tarefa. Tente novamente.';
            }
            return;
        }

        const data = (await res.json()) as { open_window: string | null };
        emit('update:open', false);
        if (data.open_window !== null) {
            emit('open-task-modal', { type: data.open_window });
        }
        emit('completed', { open_window: data.open_window });
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Executar Tarefa</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Read-only info rows -->
                <div class="space-y-2 rounded-md border bg-muted/30 p-3">
                    <div class="grid grid-cols-[140px_1fr] gap-2 text-sm">
                        <span class="text-muted-foreground">Oportunidade:</span>
                        <span class="font-medium">{{
                            task.opportunity?.guardian?.name ?? '—'
                        }}</span>
                    </div>
                    <div class="grid grid-cols-[140px_1fr] gap-2 text-sm">
                        <span class="text-muted-foreground"
                            >Etapa da Oportunidade:</span
                        >
                        <span class="font-medium">{{
                            task.opportunity?.status
                                ? (opportunityStatusLabels[
                                      task.opportunity.status
                                  ] ?? task.opportunity.status)
                                : '—'
                        }}</span>
                    </div>
                    <div class="grid grid-cols-[140px_1fr] gap-2 text-sm">
                        <span class="text-muted-foreground"
                            >Tipo de Tarefa:</span
                        >
                        <span class="font-medium">{{
                            taskTypeLabels[task.type] ?? task.type
                        }}</span>
                    </div>
                </div>

                <!-- Outcome list -->
                <div class="space-y-1.5">
                    <Label>Resposta da Tarefa</Label>
                    <div class="max-h-60 space-y-1.5 overflow-y-auto pr-1">
                        <button
                            v-for="outcome in outcomes"
                            :key="outcome.uuid"
                            type="button"
                            class="w-full rounded-md border px-3 py-2 text-left text-sm transition-colors"
                            :class="
                                selectedOutcome?.uuid === outcome.uuid
                                    ? 'border-primary bg-primary/5 font-medium text-primary'
                                    : 'border-border bg-background hover:bg-muted/50'
                            "
                            @click="selectOutcome(outcome)"
                        >
                            {{ outcome.name }}
                            <span
                                v-if="outcome.is_refusal"
                                class="ml-2 inline-flex items-center rounded-full bg-red-50 px-1.5 py-0.5 text-xs text-red-600 ring-1 ring-red-500/20 ring-inset dark:bg-red-400/10 dark:text-red-400"
                            >
                                Recusa
                            </span>
                        </button>
                    </div>
                    <p
                        v-if="errors.outcome_uuid"
                        class="text-xs text-destructive"
                    >
                        {{ errors.outcome_uuid }}
                    </p>
                </div>

                <!-- Notes -->
                <div class="space-y-1.5">
                    <Label for="execute-notes">Comentário</Label>
                    <textarea
                        id="execute-notes"
                        v-model="form.notes"
                        placeholder="Comentário sobre o resultado..."
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <p v-if="errors.notes" class="text-xs text-destructive">
                        {{ errors.notes }}
                    </p>
                </div>

                <!-- Refusal section -->
                <template v-if="isRefusal">
                    <div class="space-y-1.5">
                        <Label for="execute-refusal-category"
                            >Categoria de Recusa</Label
                        >
                        <Select
                            :model-value="form.refusal_category || undefined"
                            @update:model-value="updateRefusalCategory"
                        >
                            <SelectTrigger id="execute-refusal-category">
                                <SelectValue
                                    placeholder="Selecione a categoria"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(
                                        label, value
                                    ) in refusalCategoryLabels"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="errors.refusal_category"
                            class="text-xs text-destructive"
                        >
                            {{ errors.refusal_category }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="execute-refusal-detail"
                            >Detalhe da Recusa</Label
                        >
                        <textarea
                            id="execute-refusal-detail"
                            v-model="form.refusal_detail"
                            placeholder="Descreva o motivo da recusa..."
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p
                            v-if="errors.refusal_detail"
                            class="text-xs text-destructive"
                        >
                            {{ errors.refusal_detail }}
                        </p>
                    </div>
                </template>
            </div>

            <!-- General error (403, 500, etc.) -->
            <div
                v-if="generalError"
                class="rounded-md border border-destructive/50 bg-destructive/10 px-3 py-2 text-sm text-destructive"
            >
                {{ generalError }}
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('update:open', false)"
                >
                    Cancelar
                </Button>
                <Button
                    type="button"
                    :disabled="!selectedOutcome || processing"
                    @click="submit"
                >
                    Confirmar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
