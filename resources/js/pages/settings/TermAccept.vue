<script setup lang="ts">
import { usePage, useForm, router, Head } from '@inertiajs/vue3';
import { FileCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes/index';
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

const page = usePage();
const toast = useToast();

const term = page.props.term as TermVersion;

const formattedDate = term.effective_at
    ? new Date(term.effective_at).toLocaleDateString('pt-BR')
    : '';

const companyName = computed(() => (page.props.company_name as string) || '');
const dpoEmail = computed(() => (page.props.dpo_email as string) || '');

const processedContent = computed(() => {
    if (!term.content) return '';
    let content = term.content;
    if (companyName.value) {
        content = content.replace(/\[RAZÃO\s*SOCIAL\]/gi, companyName.value);
    }
    if (dpoEmail.value) {
        content = content.replace(/\[DPO\s*EMAIL\]/gi, dpoEmail.value);
    }
    return content;
});

const form = useForm({});

function handleAccept() {
    form.post(accept({ term_uuid: term.uuid }).url, {
        onSuccess: () => {
            toast.success('Termos aceitos com sucesso!');
            router.visit(dashboard().url);
        },
        onError: () => {
            toast.error('Erro ao aceitar termos.');
        },
    });
}
</script>

<template>
    <AppLayout :hide-sidebar="true">
        <Head title="Atualização Termos de Uso" />

        <div
            class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12"
        >
            <div class="w-full max-w-3xl">
                <div class="mb-8 text-center">
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10"
                    >
                        <FileCheck class="h-7 w-7 text-primary" />
                    </div>
                    <h1 class="text-2xl font-bold text-foreground">
                        Atualização dos Termos de Uso
                    </h1>
                    <p class="mt-1.5 text-sm text-muted-foreground">
                        Precisamos que você aceite os novos termos para
                        continuar usando o sistema.
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-card-foreground">
                            {{ term.title }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Versão {{ term.version }} · Vigência:
                            {{ formattedDate }}
                        </p>
                    </div>

                    <div
                        class="prose prose-sm max-h-[50vh] max-w-none overflow-y-auto rounded-md border p-4 dark:prose-invert"
                        v-html="processedContent"
                    />

                    <div class="mt-6 space-y-4 border-t pt-6">
                        <div class="flex items-start gap-2.5">
                            <input
                                id="accept_terms"
                                v-model="form.accepted"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <label
                                for="accept_terms"
                                class="cursor-pointer text-sm text-muted-foreground"
                            >
                                Li e aceito os Termos de Uso descritos acima.
                            </label>
                        </div>

                        <Button
                            type="button"
                            class="w-full"
                            :disabled="!form.accepted || form.processing"
                            @click="handleAccept"
                        >
                            {{
                                form.processing
                                    ? 'Aceitando...'
                                    : 'Aceitar e Continuar'
                            }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
