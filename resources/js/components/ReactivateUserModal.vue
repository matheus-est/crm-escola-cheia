<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { edit, restore } from '@/routes/users';

interface DeletedUserData {
    uuid: string;
    name: string;
    email: string;
}

const props = defineProps<{
    open: boolean;
    userData: DeletedUserData | null;
}>();

const emit = defineEmits(['update:open', 'success']);

const isOpen = ref(props.open);
const processing = ref(false);

const toast = useToast();

watch(
    () => props.open,
    (val) => {
        isOpen.value = val;
    },
);

watch(isOpen, (val) => {
    emit('update:open', val);
});

function handleConfirm() {
    if (!props.userData?.uuid) return;

    processing.value = true;

    router.post(
        restore({ user_uuid: props.userData.uuid }).url,
        {},
        {
            onSuccess: () => {
                processing.value = false;
                isOpen.value = false;
                toast.success('Usuário reativado com sucesso.');
                emit('success', props.userData);
                router.visit(edit({ user_uuid: props.userData!.uuid }).url);
            },
            onError: () => {
                processing.value = false;
                toast.error('Erro ao reativar usuário.');
            },
        },
    );
}

function handleCancel() {
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Usuário Deletado Encontrado</DialogTitle>
                <DialogDescription>
                    Este email pertence a um usuário que foi deletado
                    anteriormente.
                </DialogDescription>
            </DialogHeader>

            <div v-if="userData" class="space-y-3 py-4">
                <div class="rounded-md border bg-muted p-4">
                    <div class="grid gap-1">
                        <Label class="text-xs text-muted-foreground"
                            >Nome</Label
                        >
                        <span class="font-medium">{{ userData.name }}</span>
                    </div>
                    <div class="mt-2 grid gap-1">
                        <Label class="text-xs text-muted-foreground"
                            >Email</Label
                        >
                        <span class="font-medium">{{ userData.email }}</span>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground">
                    Deseja reativar este usuário? Todas as informações serão
                    mantidas.
                </p>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    @click="handleCancel"
                    :disabled="processing"
                >
                    Cancelar
                </Button>
                <Button
                    type="button"
                    variant="default"
                    @click="handleConfirm"
                    :disabled="processing"
                >
                    {{ processing ? 'Reativando...' : 'Sim, Reativar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
