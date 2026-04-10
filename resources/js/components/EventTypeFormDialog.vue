<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { store, update } from '@/routes/tenant/settings/event_types/index';
import type { EventType } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    eventType?: EventType | null;
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
        @update:open="
            (val: boolean) => {
                if (!val) handleClose();
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{
                        props.mode === 'create'
                            ? 'Novo Tipo de Evento'
                            : 'Editar Tipo de Evento'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        props.mode === 'create'
                            ? 'Preencha os dados para cadastrar um novo tipo de evento.'
                            : 'Atualize os dados do tipo de evento.'
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
                <div class="grid gap-2">
                    <Label for="event-type-name">Nome</Label>
                    <Input
                        id="event-type-name"
                        name="name"
                        placeholder="Ex: Palestra"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0" />
                    <Checkbox
                        id="event-type-is_active"
                        name="is_active"
                        :default-checked="true"
                    />
                    <Label for="event-type-is_active" class="cursor-pointer">
                        Tipo ativo
                    </Label>
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
                        Criar Tipo
                    </Button>
                </DialogFooter>
            </Form>

            <!-- Edit Form -->
            <Form
                v-else-if="props.mode === 'edit' && props.eventType"
                method="put"
                :action="update({ event_type: props.eventType.uuid }).url"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleSuccess"
            >
                <div class="grid gap-2">
                    <Label for="event-type-edit-name">Nome</Label>
                    <Input
                        id="event-type-edit-name"
                        name="name"
                        :default-value="props.eventType.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0" />
                    <Checkbox
                        id="event-type-edit-is_active"
                        name="is_active"
                        :default-checked="props.eventType.is_active"
                    />
                    <Label
                        for="event-type-edit-is_active"
                        class="cursor-pointer"
                    >
                        Tipo ativo
                    </Label>
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
