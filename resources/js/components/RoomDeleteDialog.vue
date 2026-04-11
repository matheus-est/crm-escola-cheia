<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
import { useToast } from '@/composables/useToast';
import { destroy } from '@/routes/tenant/settings/rooms/index';
import type { Room } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    room: Room | null;
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
    success: [];
}>();

const isOpen = ref(props.open);
const password = ref('');
const errorMessage = ref('');
const processing = ref(false);

const toast = useToast();

watch(
    () => props.open,
    (val) => {
        isOpen.value = val;
        if (!val) {
            password.value = '';
            errorMessage.value = '';
        }
    },
);

watch(isOpen, (val) => {
    emit('update:open', val);
});

function handleSubmit(): void {
    if (!props.room || !password.value) return;

    processing.value = true;
    errorMessage.value = '';

    router.delete(destroy({ room: props.room.uuid }).url, {
        data: { password: password.value },
        onSuccess: () => {
            processing.value = false;
            isOpen.value = false;
            toast.success('Sala excluída com sucesso.');
            emit('success');
        },
        onError: (errors: Record<string, string>) => {
            processing.value = false;
            const passwordError =
                errors.password ?? 'Erro ao processar a solicitação.';
            errorMessage.value = passwordError;
        },
    });
}

function handleClose(): void {
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Excluir Sala</DialogTitle>
                <DialogDescription>
                    Digite sua senha para confirmar a exclusão de "{{
                        props.room?.name
                    }}"
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="delete-room-password"
                            >Digite sua senha</Label
                        >
                        <Input
                            id="delete-room-password"
                            v-model="password"
                            type="password"
                            placeholder="Sua senha"
                            required
                        />
                        <InputError :message="errorMessage" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        @click="handleClose"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing || !password"
                    >
                        {{ processing ? 'Excluindo...' : 'Excluir' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
