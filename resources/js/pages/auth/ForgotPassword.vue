<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    BarChart3,
    ClipboardList,
    GraduationCap,
    Users,
} from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useI18n();

defineProps<{
    status?: string;
}>();

const features = [
    {
        icon: GraduationCap,
        title: 'Gestão de Matrículas',
        description: 'Controle completo do funil de matrículas e rematrículas',
    },
    {
        icon: Users,
        title: 'CRM Comercial',
        description: 'Acompanhamento de leads, oportunidades e tarefas',
    },
    {
        icon: BarChart3,
        title: 'Visão por Escola',
        description: 'Painel multi-tenant com dados isolados por unidade',
    },
    {
        icon: ClipboardList,
        title: 'Auditoria e LGPD',
        description: 'Rastreabilidade de ações e conformidade com a legislação',
    },
];
</script>

<template>
    <AuthSplitLayout
        headline="Matrículas com gestão inteligente."
        subheadline="Plataforma CRM para escolas — gerencie leads, oportunidades e matrículas em um único lugar."
        :features="features"
    >
        <div class="w-full max-w-sm">
            <div class="mb-8">
                <h2
                    class="text-2xl font-semibold tracking-tight text-foreground"
                >
                    {{ t('Forgot password') }}
                </h2>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ t('Enter your email to receive a password reset link') }}
                </p>
            </div>

            <div
                v-if="status"
                class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20"
            >
                {{ status }}
            </div>

            <Head :title="t('Forgot password')" />

            <Form
                v-bind="email.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-1.5">
                    <Label for="email">{{ $t('Email address') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <Button
                    class="mt-2 w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    {{ $t('Email password reset link') }}
                </Button>
            </Form>

            <div class="mt-6 text-center text-sm text-muted-foreground">
                <span> {{ $t('Or, return to') }} </span>&nbsp;
                <TextLink :href="login()"> {{ $t('log in') }}</TextLink>
            </div>
        </div>
    </AuthSplitLayout>
</template>
