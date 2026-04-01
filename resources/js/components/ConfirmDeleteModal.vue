<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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

const props = defineProps<{
    open: boolean;
    title: string;
    message: string;
    action: string;
    confirmText?: string;
    processingText?: string;
    successMessage?: string;
}>();

const emit = defineEmits(['update:open', 'success']);

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
});

const password = ref('');
const errorMessage = ref('');
const processing = ref(false);

const toast = useToast();

watch(isOpen, (val) => {
    if (!val) {
        password.value = '';
        errorMessage.value = '';
    }
});

function handleSubmit() {
    if (!password.value) return;

    processing.value = true;
    errorMessage.value = '';

    router.post(
        props.action,
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
            onError: (errors) => {
                const passwordError = errors?.password || 'Erro ao processar.';
                errorMessage.value = passwordError;
                toast.error(passwordError);
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ message }}</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="password"
                            >Digite sua senha para confirmar</Label
                        >
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
                        @click="isOpen = false"
                        :disabled="processing"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing || !password"
                    >
                        {{
                            processing
                                ? processingText || 'Processando...'
                                : confirmText || 'Confirmar'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
