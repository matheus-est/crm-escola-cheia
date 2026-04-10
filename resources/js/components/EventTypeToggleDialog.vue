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
import { toggle_active } from '@/routes/tenant/settings/event_types/index';
import type { EventType } from '@/types/crm';

const props = defineProps<{
    open: boolean;
    eventType: EventType | null;
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
    if (!props.eventType || !password.value) return;

    processing.value = true;
    errorMessage.value = '';

    router.post(
        toggle_active({ eventType: props.eventType.uuid }).url,
        { password: password.value },
        {
            onSuccess: () => {
                processing.value = false;
                isOpen.value = false;
                const action = props.eventType?.is_active
                    ? 'inativado'
                    : 'reativado';
                toast.success(`Tipo de evento ${action} com sucesso.`);
                emit('success');
            },
            onError: (errors: Record<string, string>) => {
                processing.value = false;
                const passwordError =
                    errors.password ?? 'Erro ao processar a solicitação.';
                errorMessage.value = passwordError;
                toast.error(passwordError);
            },
        },
    );
}

function handleClose(): void {
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{
                        props.eventType?.is_active
                            ? 'Inativar Tipo de Evento'
                            : 'Reativar Tipo de Evento'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        props.eventType?.is_active
                            ? 'Digite sua senha para confirmar a inativação de "'
                            : 'Digite sua senha para confirmar a reativação de "'
                    }}{{ props.eventType?.name }}"
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="toggle-password">Digite sua senha</Label>
                        <Input
                            id="toggle-password"
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
                        :variant="
                            props.eventType?.is_active
                                ? 'destructive'
                                : 'default'
                        "
                        :disabled="processing || !password"
                    >
                        {{
                            processing
                                ? 'Processando...'
                                : props.eventType?.is_active
                                  ? 'Inativar'
                                  : 'Reativar'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
