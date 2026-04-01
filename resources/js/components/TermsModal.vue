<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { accept } from '@/routes/terms';

interface TermVersion {
    id: number;
    uuid: string;
    version: string;
    title: string;
    content: string;
    effective_at: string;
    is_active: boolean;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        term: TermVersion;
        showAcceptButton?: boolean;
    }>(),
    {
        showAcceptButton: true,
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    accept: [];
}>();

const isAccepted = ref(false);
const form = useForm({});

const formattedDate = computed(() =>
    props.term.effective_at
        ? new Date(props.term.effective_at).toLocaleDateString('pt-BR')
        : '',
);

function handleAccept(): void {
    form.post(accept({ term_uuid: props.term.uuid }).url, {
        onSuccess: () => emit('accept'),
        onError: (errors) => console.error('Erro ao aceitar termos:', errors),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[85vh] max-w-3xl flex-col gap-0 p-0">
            <DialogHeader class="shrink-0 border-b px-6 py-4">
                <DialogTitle class="flex items-center gap-2">
                    Termos de Uso e Política de Privacidade
                    <span class="text-sm font-normal text-muted-foreground">
                        v{{ term.version }}
                        <template v-if="formattedDate">
                            · {{ formattedDate }}</template
                        >
                    </span>
                </DialogTitle>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div
                    class="prose prose-sm max-w-none dark:prose-invert"
                    v-html="term.content"
                />
            </div>

            <div v-if="showAcceptButton" class="shrink-0 border-t px-6 py-4">
                <div class="mb-4 flex items-start gap-2.5">
                    <input
                        id="accept_terms"
                        v-model="isAccepted"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-primary"
                    />
                    <label
                        for="accept_terms"
                        class="cursor-pointer text-sm text-muted-foreground"
                    >
                        Eu li e aceito os Termos de Uso descritos acima.
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="emit('update:open', false)"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        :disabled="!isAccepted || form.processing"
                        @click="handleAccept"
                    >
                        {{
                            form.processing ? 'Aceitando...' : 'Aceitar Termos'
                        }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
