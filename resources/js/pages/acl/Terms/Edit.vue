<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store, update } from '@/routes/terms';
import { type BreadcrumbItem } from '@/types';

interface TermVersion {
    id: number;
    uuid: string;
    version: string;
    title: string;
    content: string;
    effective_at: string;
    is_active: boolean;
}

const props = defineProps<{
    term?: TermVersion | null;
    nextVersion: string;
}>();

const toast = useToast();
const isEditing = computed(() => !!props.term);

const form = useForm({
    title: props.term?.title ?? '',
    version: props.term?.version ?? props.nextVersion,
    content: props.term?.content ?? '',
    effective_at: props.term?.effective_at
        ? props.term.effective_at.split('T')[0]
        : new Date().toISOString().split('T')[0],
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Termos de Uso', href: index().url },
    { title: isEditing.value ? 'Editar' : 'Criar', href: '#' },
];

function handleSubmit(): void {
    if (isEditing.value && props.term?.uuid) {
        form.put(update({ term_uuid: props.term.uuid }).url, {
            onSuccess: () => toast.success('Termo atualizado com sucesso.'),
            onError: () =>
                toast.error('Verifique os campos e tente novamente.'),
        });
    } else {
        form.post(store().url, {
            onSuccess: () => toast.success('Termo criado com sucesso.'),
            onError: () =>
                toast.error('Verifique os campos e tente novamente.'),
        });
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isEditing ? 'Editar Termo' : 'Criar Termo'" />

        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <button
                    type="button"
                    class="rounded-md p-2 hover:bg-muted"
                    @click="router.visit(index().url)"
                >
                    <ArrowLeft class="h-5 w-5" />
                </button>
                <Heading
                    :title="
                        isEditing ? 'Editar Termo de Uso' : 'Criar Termo de Uso'
                    "
                    :description="
                        isEditing
                            ? `Editando versão ${term?.version}`
                            : 'Criar uma nova versão dos termos de uso'
                    "
                />
            </div>

            <div class="rounded-md border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6 grid gap-2">
                            <Label for="title">Título</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                placeholder="Ex: Termos de Uso do Sistema"
                            />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="col-span-2 grid gap-2">
                            <Label for="version">Versão</Label>
                            <Input
                                id="version"
                                v-model="form.version"
                                readonly
                                class="cursor-not-allowed bg-muted text-muted-foreground"
                                tabindex="-1"
                            />
                            <InputError :message="form.errors.version" />
                        </div>

                        <!-- Data de vigência -->
                        <div class="col-span-2 grid gap-2">
                            <Label for="effective_at">Data de Vigência</Label>
                            <Input
                                id="effective_at"
                                v-model="form.effective_at"
                                type="date"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Data passada ou hoje entra em vigor
                                imediatamente. Datas futuras são agendadas.
                            </p>
                            <InputError :message="form.errors.effective_at" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="content">Conteúdo</Label>
                        <TiptapEditor id="content" v-model="form.content" />
                        <InputError :message="form.errors.content" />
                    </div>

                    <div
                        class="-mx-6 -mb-6 flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            :disabled="form.processing"
                            @click="router.visit(index().url)"
                        >
                            Cancelar
                        </Button>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-2 bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            <Save class="h-4 w-4" />
                            {{
                                form.processing ? 'Salvando...' : 'Salvar Termo'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
