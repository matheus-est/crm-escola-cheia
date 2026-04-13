<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { taskTypeLabels } from '@/lib/task';
import { store } from '@/routes/tenant/tasks';
import type { RegistrationType, TaskType } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    opportunityUuid: string;
    users: Array<{ id: number; uuid: string; name: string }>;
    defaultType?: TaskType;
    registrationType?: RegistrationType | null;
    preselectedType?: TaskType;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    created: [];
}>();

const AGENDAMENTO_TYPES: TaskType[] = [
    'retorno_ligacao',
    'agendamento',
    'lembrete_agenda',
    'reagendamento',
    'double_check',
    'provavel_matricula',
];

const EVENTO_TYPES: TaskType[] = [
    'evento',
    'lembrete_evento',
    'reagendamento_evento',
    'double_check_evento',
];

const availableTaskTypes = computed<Array<{ value: TaskType; label: string }>>(
    () => {
        const rt = props.registrationType ?? null;
        if (rt === 'agendamento') {
            return AGENDAMENTO_TYPES.filter((t) => t in taskTypeLabels).map(
                (t) => ({ value: t, label: taskTypeLabels[t] }),
            );
        }
        if (rt === 'evento') {
            return EVENTO_TYPES.filter((t) => t in taskTypeLabels).map((t) => ({
                value: t,
                label: taskTypeLabels[t],
            }));
        }
        return (Object.entries(taskTypeLabels) as [TaskType, string][]).map(
            ([value, label]) => ({ value, label }),
        );
    },
);

const form = useForm<{
    opportunity_uuid: string;
    type: TaskType | '';
    assigned_user_uuid: string;
    due_at: string;
    notes: string;
}>({
    opportunity_uuid: props.opportunityUuid,
    type: '',
    assigned_user_uuid: '',
    due_at: '',
    notes: '',
});

watch(
    () => props.open,
    (val) => {
        if (val) {
            form.reset();
            form.opportunity_uuid = props.opportunityUuid;
            if (props.preselectedType) {
                form.type = props.preselectedType;
            } else if (props.defaultType) {
                form.type = props.defaultType;
            }
        }
    },
);

function updateType(value: string): void {
    form.type = value as TaskType;
}

function updateUser(value: string): void {
    form.assigned_user_uuid = value === 'none' ? '' : value;
}

function submit(): void {
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
                <div class="space-y-1.5">
                    <Label for="task-type">Tipo de Tarefa</Label>
                    <Select
                        :default-value="form.type || undefined"
                        :model-value="form.type || undefined"
                        @update:model-value="updateType"
                    >
                        <SelectTrigger id="task-type">
                            <SelectValue placeholder="Selecione o tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in availableTaskTypes"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.type" class="text-xs text-destructive">
                        {{ form.errors.type }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="task-user">Responsável</Label>
                    <Select
                        :model-value="form.assigned_user_uuid || 'none'"
                        @update:model-value="updateUser"
                    >
                        <SelectTrigger id="task-user">
                            <SelectValue placeholder="Sem responsável" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none"
                                >Sem responsável</SelectItem
                            >
                            <SelectItem
                                v-for="user in users"
                                :key="user.uuid"
                                :value="user.uuid"
                            >
                                {{ user.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.assigned_user_uuid"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.assigned_user_uuid }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="task-due-at">Data/Hora de Vencimento</Label>
                    <Input
                        id="task-due-at"
                        v-model="form.due_at"
                        type="datetime-local"
                    />
                    <p
                        v-if="form.errors.due_at"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.due_at }}
                    </p>
                </div>

                <div class="space-y-1.5">
                    <Label for="task-notes">Observações</Label>
                    <textarea
                        id="task-notes"
                        v-model="form.notes"
                        placeholder="Observações sobre a tarefa..."
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <p
                        v-if="form.errors.notes"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.notes }}
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
