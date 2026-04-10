<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BarChart3, Target, ShieldCheck, Users } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const { t } = useI18n();

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const features = [
    {
        icon: Target,
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
        icon: ShieldCheck,
        title: 'Auditoria Completa',
        description: 'Rastreabilidade de todas as ações realizadas no sistema',
    },
];
</script>

<template>
    <AuthSplitLayout
        headline="Resultados com gestão inteligente."
        subheadline="Plataforma CRM para escolas — gerencie leads, oportunidades e matrículas em um único lugar."
        :features="features"
    >
        <div class="w-full max-w-sm">
            <div class="mb-8">
                <h2
                    class="text-2xl font-semibold tracking-tight text-foreground"
                >
                    {{ t('Log in to your account') }}
                </h2>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ t('Enter your email and password below to log in') }}
                </p>
            </div>

            <div
                v-if="status"
                class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-600/20 ring-inset dark:bg-emerald-950 dark:text-emerald-400 dark:ring-emerald-500/20"
            >
                {{ status }}
            </div>

            <Head :title="t('Log in')" />

            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-1.5">
                    <Label for="email">{{ $t('Email address') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ $t('Password') }}</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs"
                            :tabindex="5"
                        >
                            {{ $t('Forgot password') }}?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center gap-2.5">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <Label
                        for="remember"
                        class="cursor-pointer text-sm font-normal"
                    >
                        {{ $t('Remember me') }}
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    {{ processing ? t('Logging in') + '...' : $t('Log in') }}
                </Button>
            </Form>
        </div>
    </AuthSplitLayout>
</template>
