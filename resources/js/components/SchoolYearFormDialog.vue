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
import { store, update } from '@/routes/tenant/school_years/index';
import type { SchoolYear } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    schoolYear?: SchoolYear | null;
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
                            ? 'Novo Ano Letivo'
                            : 'Editar Ano Letivo'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        props.mode === 'create'
                            ? 'Preencha os dados para cadastrar um novo ano letivo.'
                            : 'Atualize os dados do ano letivo.'
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
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <Label for="sy-name">
                            Nome
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="sy-name"
                            name="name"
                            placeholder="Ex: 2025"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-status">Status</Label>
                        <Select name="status">
                            <SelectTrigger id="sy-status">
                                <SelectValue placeholder="Selecione o status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="planejamento">
                                    Planejamento
                                </SelectItem>
                                <SelectItem value="ativo">Ativo</SelectItem>
                                <SelectItem value="encerrado">
                                    Encerrado
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-start">
                            Início
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="sy-start"
                            type="date"
                            name="start"
                            required
                        />
                        <InputError :message="errors.start" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-end">
                            Fim
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input id="sy-end" type="date" name="end" required />
                        <InputError :message="errors.end" />
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
                        Criar Ano Letivo
                    </Button>
                </DialogFooter>
            </Form>

            <!-- Edit Form -->
            <Form
                v-else-if="props.mode === 'edit' && props.schoolYear"
                method="put"
                :action="update({ schoolYear: props.schoolYear.uuid }).url"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleSuccess"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <Label for="sy-edit-name">
                            Nome
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="sy-edit-name"
                            name="name"
                            placeholder="Ex: 2025"
                            :default-value="props.schoolYear.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-edit-status">Status</Label>
                        <Select
                            name="status"
                            :default-value="props.schoolYear.status"
                        >
                            <SelectTrigger id="sy-edit-status">
                                <SelectValue placeholder="Selecione o status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="planejamento">
                                    Planejamento
                                </SelectItem>
                                <SelectItem value="ativo">Ativo</SelectItem>
                                <SelectItem value="encerrado">
                                    Encerrado
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-edit-start">
                            Início
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="sy-edit-start"
                            type="date"
                            name="start"
                            :default-value="props.schoolYear.start"
                            required
                        />
                        <InputError :message="errors.start" />
                    </div>

                    <div class="space-y-1">
                        <Label for="sy-edit-end">
                            Fim
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="sy-edit-end"
                            type="date"
                            name="end"
                            :default-value="props.schoolYear.end"
                            required
                        />
                        <InputError :message="errors.end" />
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
