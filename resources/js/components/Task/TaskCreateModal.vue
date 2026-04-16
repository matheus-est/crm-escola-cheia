<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taskTypeLabels } from '@/lib/task';
import { store } from '@/routes/tenant/tasks';
import type { TaskType } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    opportunityUuid: string;
    opportunityInfo?: { guardianName?: string; studentName?: string };
    defaultType?: TaskType;
    preselectedType?: TaskType;
    assignedUserUuid?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [];
}>();

const taskTypeLabel = computed<string>(() => {
    const type = props.preselectedType ?? props.defaultType;
    if (!type) return '—';
    return taskTypeLabels[type] ?? type;
});

const form = useForm<{
    opportunity_uuid: string;
    type: TaskType | '';
    assigned_user_uuid: string;
    due_at: string;
}>({
    opportunity_uuid: props.opportunityUuid,
    type: '' as TaskType | '',
    assigned_user_uuid: props.assignedUserUuid ?? '',
    due_at: '',
});

const dueDateError = ref<string | null>(null);

const minDateTime = computed<string>(() => {
    const now = new Date();
    now.setSeconds(0, 0);
    return now.toISOString().slice(0, 16);
});

watch(
    () => props.open,
    (val) => {
        if (val) {
            form.reset();
            form.opportunity_uuid = props.opportunityUuid;
            form.type = props.preselectedType ?? props.defaultType ?? '';
            form.assigned_user_uuid = props.assignedUserUuid ?? '';
            dueDateError.value = null;
        }
    },
    { immediate: true },
);

function validateDueAt(): boolean {
    if (!form.due_at) {
        dueDateError.value = null;
        return true;
    }
    if (new Date(form.due_at) <= new Date()) {
        dueDateError.value = 'O prazo deve ser uma data e hora no futuro.';
        return false;
    }
    dueDateError.value = null;
    return true;
}

function submit(): void {
    if (!validateDueAt()) return;
    form.post(store().url, {
        onSuccess: () => {
            emit('created');
            emit('update:open', false);
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Nova Tarefa</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <!-- Hidden type field -->
                <input type="hidden" name="type" :value="form.type" />

                <!-- Opportunity info (read-only) -->
                <div
                    v-if="
                        opportunityInfo?.guardianName ||
                        opportunityInfo?.studentName
                    "
                    class="rounded-md border bg-muted/50 px-3 py-2 text-sm"
                >
                    <p
                        v-if="opportunityInfo?.guardianName"
                        class="text-muted-foreground"
                    >
                        <span class="font-medium text-foreground"
                            >Responsável:</span
                        >
                        {{ opportunityInfo.guardianName }}
                    </p>
                    <p
                        v-if="opportunityInfo?.studentName"
                        class="text-muted-foreground"
                    >
                        <span class="font-medium text-foreground">Aluno:</span>
                        {{ opportunityInfo.studentName }}
                    </p>
                </div>

                <!-- Task type (read-only display) -->
                <div class="space-y-1.5">
                    <Label
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >Tipo de Tarefa</Label
                    >
                    <div
                        class="rounded-md border bg-muted/50 px-3 py-2 text-sm text-foreground"
                    >
                        {{ taskTypeLabel }}
                    </div>
                    <p v-if="form.errors.type" class="text-xs text-destructive">
                        {{ form.errors.type }}
                    </p>
                </div>

                <!-- Due at -->
                <div class="space-y-1.5">
                    <Label for="task-due-at">Prazo</Label>
                    <Input
                        id="task-due-at"
                        v-model="form.due_at"
                        type="datetime-local"
                        :min="minDateTime"
                        @change="validateDueAt"
                    />
                    <p
                        v-if="dueDateError || form.errors.due_at"
                        class="text-xs text-destructive"
                    >
                        {{ dueDateError ?? form.errors.due_at }}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Criar Tarefa
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
