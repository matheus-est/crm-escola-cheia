<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { store, update } from '@/routes/tenant/settings/rooms/index';
import type { Room } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    room?: Room | null;
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
    success: [];
}>();

const isExternalCreate = ref(false);
const isExternalEdit = ref(props.room?.is_external ?? false);

function fillInput(id: string, value: string): void {
    const el = document.getElementById(id) as HTMLInputElement | null;
    if (el) el.value = value;
}

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
                    {{ props.mode === 'create' ? 'Nova Sala' : 'Editar Sala' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        props.mode === 'create'
                            ? 'Preencha os dados para cadastrar uma nova sala.'
                            : 'Atualize os dados da sala.'
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
                    <Label for="room-name">
                        Nome
                        <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="room-name"
                        name="name"
                        placeholder="Ex: Sala 101"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="room-capacity">Capacidade</Label>
                    <Input
                        id="room-capacity"
                        type="number"
                        name="capacity"
                        min="1"
                        placeholder="Ex: 30"
                        :disabled="isExternalCreate"
                        :class="{
                            'cursor-not-allowed opacity-50': isExternalCreate,
                        }"
                    />
                    <InputError :message="errors.capacity" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_external" value="0" />
                    <Checkbox
                        id="room-is_external"
                        name="is_external"
                        :default-checked="false"
                        @update:checked="
                            (val: boolean) => {
                                isExternalCreate = val;
                                if (val) fillInput('room-capacity', '');
                            }
                        "
                    />
                    <Label for="room-is_external" class="cursor-pointer">
                        Esta sala é externa
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
                        Criar Sala
                    </Button>
                </DialogFooter>
            </Form>

            <!-- Edit Form -->
            <Form
                v-else-if="props.mode === 'edit' && props.room"
                method="put"
                :action="update({ room: props.room.uuid }).url"
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="handleSuccess"
            >
                <div class="grid gap-2">
                    <Label for="room-edit-name">
                        Nome
                        <span class="text-destructive">*</span>
                    </Label>
                    <Input
                        id="room-edit-name"
                        name="name"
                        :default-value="props.room.name"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="room-edit-capacity">Capacidade</Label>
                    <Input
                        id="room-edit-capacity"
                        type="number"
                        name="capacity"
                        min="1"
                        :default-value="
                            props.room.capacity !== null
                                ? String(props.room.capacity)
                                : ''
                        "
                        :disabled="isExternalEdit"
                        :class="{
                            'cursor-not-allowed opacity-50': isExternalEdit,
                        }"
                    />
                    <InputError :message="errors.capacity" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_external" value="0" />
                    <Checkbox
                        id="room-edit-is_external"
                        name="is_external"
                        :default-checked="props.room.is_external"
                        @update:checked="
                            (val: boolean) => {
                                isExternalEdit = val;
                                if (val) fillInput('room-edit-capacity', '');
                            }
                        "
                    />
                    <Label for="room-edit-is_external" class="cursor-pointer">
                        Esta sala é externa
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
