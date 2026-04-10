<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import { store, update } from '@/routes/tenant/grades/index';
import type { Segment } from '@/types/crm';

interface GradeItem {
    uuid: string;
    name: string;
    order: number;
    segment_id: number;
    segment?: Segment | null;
}

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    grade?: GradeItem | null;
    segments: Segment[];
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
    success: [];
}>();

function handleSuccess(): void {
    emit('success');
    emit('update:open', false);
}

function handleClose(): void {
    emit('update:open', false);
}
</script>

<template>
    <Dialog
        :open="props.open"
        @update:open="(val: boolean) => emit('update:open', val)"
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        props.mode === 'create'
                            ? 'Nova Turma/Série'
                            : 'Editar Turma/Série'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        props.mode === 'create'
                            ? 'Preencha os dados para cadastrar uma nova turma/série.'
                            : 'Atualize os dados da turma/série.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <!-- Create Form -->
            <Form
                v-if="props.mode === 'create'"
                method="post"
                :action="store().url"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleSuccess"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="grade-name">
                            Nome
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="grade-name"
                            name="name"
                            placeholder="Ex: 1º Ano A"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="grade-segment">
                            Segmento
                            <span class="text-destructive">*</span>
                        </Label>
                        <Select name="segment_uuid" required>
                            <SelectTrigger id="grade-segment">
                                <SelectValue
                                    placeholder="Selecione o segmento"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="segment in props.segments"
                                    :key="segment.uuid"
                                    :value="segment.uuid"
                                >
                                    {{ segment.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.segment_uuid" />
                    </div>

                    <div class="space-y-1">
                        <Label for="grade-order">Ordem (Opcional)</Label>
                        <Input
                            id="grade-order"
                            type="number"
                            name="order"
                            min="0"
                            placeholder="Ex: 5"
                        />
                        <InputError :message="errors.order" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="bg-green-600 text-white hover:bg-green-700"
                    >
                        Criar Turma
                    </Button>
                </DialogFooter>
            </Form>

            <!-- Edit Form -->
            <Form
                v-else-if="props.mode === 'edit' && props.grade"
                method="put"
                :action="update({ grade: props.grade.uuid }).url"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleSuccess"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <Label for="grade-edit-name">
                            Nome
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="grade-edit-name"
                            name="name"
                            :default-value="props.grade.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="grade-edit-segment">
                            Segmento
                            <span class="text-destructive">*</span>
                        </Label>
                        <Select
                            name="segment_uuid"
                            :default-value="
                                props.segments.find(
                                    (s) =>
                                        s.uuid === props.grade?.segment?.uuid,
                                )?.uuid
                            "
                            required
                        >
                            <SelectTrigger id="grade-edit-segment">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="segment in props.segments"
                                    :key="segment.uuid"
                                    :value="segment.uuid"
                                >
                                    {{ segment.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.segment_uuid" />
                    </div>

                    <div class="space-y-1">
                        <Label for="grade-edit-order">Ordem (Opcional)</Label>
                        <Input
                            id="grade-edit-order"
                            type="number"
                            name="order"
                            min="0"
                            :default-value="props.grade.order?.toString()"
                        />
                        <InputError :message="errors.order" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="handleClose"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                        class="bg-green-600 text-white hover:bg-green-700"
                    >
                        Salvar Alterações
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
