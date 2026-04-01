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

interface Props {
    open: boolean;
    userUuid: string;
    userName: string;
    successMessage?: string;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:open', 'success']);

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

function handleSubmit() {
    if (!props.userUuid || !password.value) return;

    processing.value = true;
    errorMessage.value = '';

    router.post(
        `/acl/users/${props.userUuid}/restore`,
        { password: password.value },
        {
            onSuccess: () => {
                processing.value = false;
                isOpen.value = false;
                if (props.successMessage) {
                    toast.success(props.successMessage);
                }
                emit('success');
            },
            onError: (errors: any) => {
                processing.value = false;
                const page = usePage();
                const pageErrors = page.props.errors as Record<
                    string,
                    string[]
                >;
                const passwordError =
                    pageErrors?.password?.[0] ||
                    errors?.password ||
                    'Erro ao reativar usuário.';
                errorMessage.value = passwordError;
                toast.error(passwordError);
            },
        },
    );
}

function handleClose() {
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Confirmar Reativação</DialogTitle>
                <DialogDescription>
                    Digite sua senha para confirmar a reativação do usuário "{{
                        userName
                    }}".
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="password">Digite sua senha</Label>
                        <Input
                            id="password"
                            type="password"
                            v-model="password"
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
                        @click="handleClose"
                        :disabled="processing"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        variant="default"
                        :disabled="processing || !password"
                    >
                        {{ processing ? 'Reativando...' : 'Reativar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
