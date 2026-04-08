<script setup lang="ts">
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
import { refusalCategoryLabels } from '@/lib/task';
import { complete } from '@/routes/tenant/tasks';
import type { Outcome, Task } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    task: Task;
    outcomes: Outcome[];
    users: Array<{ id: number; uuid: string; name: string }>;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    completed: [result: { open_window: string | null }];
}>();

const selectedOutcome = ref<Outcome | null>(null);
const processing = ref(false);
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

function updateRefusalCategory(value: string): void {
    form.refusal_category = value;
}

async function submit(): Promise<void> {
    processing.value = true;
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
            body: JSON.stringify(form),
        });

        if (!res.ok) {
            const err = (await res.json()) as {
                errors?: Record<string, string[]>;
            };
            if (err.errors) {
                Object.entries(err.errors).forEach(([key, messages]) => {
                    errors[key] = messages[0] ?? '';
                });
            }
            return;
        }

        const data = (await res.json()) as { open_window: string | null };
        emit('update:open', false);
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
                <DialogTitle>Tabular Tarefa</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Outcome list -->
                <div class="space-y-1.5">
                    <Label>Resultado</Label>
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
                    <Label for="outcome-notes">Observações</Label>
                    <textarea
                        id="outcome-notes"
                        v-model="form.notes"
                        placeholder="Observações sobre o resultado..."
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <p v-if="errors.notes" class="text-xs text-destructive">
                        {{ errors.notes }}
                    </p>
                </div>

                <!-- Refusal section -->
                <template v-if="isRefusal">
                    <div class="space-y-1.5">
                        <Label for="refusal-category"
                            >Categoria de Recusa</Label
                        >
                        <Select
                            :model-value="form.refusal_category || undefined"
                            @update:model-value="updateRefusalCategory"
                        >
                            <SelectTrigger id="refusal-category">
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
                        <Label for="refusal-detail">Detalhe da Recusa</Label>
                        <textarea
                            id="refusal-detail"
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
