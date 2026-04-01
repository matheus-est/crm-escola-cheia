<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Settings, Shield, Mail, FileCheck } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { update as updateEmail } from '@/routes/settings/system/email';
import { update as updateIdentity } from '@/routes/settings/system/identity';
import { update as updateLgpd } from '@/routes/settings/system/lgpd';
import { update as updateSecurity } from '@/routes/settings/system/security';
import { type BreadcrumbItem } from '@/types';

interface SystemSettingGroup {
    [key: string]: string | number | boolean | null;
}

const props = defineProps<{
    identity: SystemSettingGroup;
    security: SystemSettingGroup;
    email: SystemSettingGroup;
    lgpd: SystemSettingGroup;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Configurações do Sistema', href: '#' },
];

const toast = useToast();
const page = usePage();
const permissions = computed(
    () => (page.props.auth?.permissions as string[]) ?? [],
);
const canManage = computed(() => permissions.value.includes('settings_manage'));

type Tab = 'identity' | 'security' | 'email' | 'lgpd';
const activeTab = ref<Tab>('identity');

const tabs = [
    { value: 'identity' as Tab, label: 'Identidade', icon: Settings },
    { value: 'security' as Tab, label: 'Segurança', icon: Shield },
    { value: 'email' as Tab, label: 'E-mail', icon: Mail },
    { value: 'lgpd' as Tab, label: 'LGPD', icon: FileCheck },
];

// — Forms com chaves explícitas e fallbacks garantidos —

const identityForm = useForm({
    app_name: (props.identity.app_name as string) ?? '',
    logo_light:
        (props.identity.logo_light as string) ?? (null as string | null),
    logo_dark: (props.identity.logo_dark as string) ?? (null as string | null),
    favicon: (props.identity.favicon as string) ?? (null as string | null),
});

const securityForm = useForm({
    session_timeout: (props.security.session_timeout as number) ?? 120,
    password_expiration_days:
        (props.security.password_expiration_days as number) ?? 0,
    allow_self_deletion:
        (props.security.allow_self_deletion as boolean) ?? false,
});

const emailForm = useForm({
    mail_from_name: (props.email.mail_from_name as string) ?? '',
    mail_from_address: (props.email.mail_from_address as string) ?? '',
});

const lgpdForm = useForm({
    company_name: (props.lgpd.company_name as string) ?? '',
    dpo_email: (props.lgpd.dpo_email as string) ?? '',
    audit_retention_days: (props.lgpd.audit_retention_days as number) ?? 180,
});

const previewUrls = ref<Record<string, string>>({});

function handleFileChange(
    field: 'logo_light' | 'logo_dark' | 'favicon',
    event: Event,
): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    if (previewUrls.value[field]) {
        URL.revokeObjectURL(previewUrls.value[field]);
        delete previewUrls.value[field];
    }

    if (file) {
        previewUrls.value[field] = URL.createObjectURL(file);
        identityForm[field] = file as unknown as string;
    } else {
        identityForm[field] = null;
    }
}

function removeImage(field: 'logo_light' | 'logo_dark' | 'favicon'): void {
    if (previewUrls.value[field]) {
        URL.revokeObjectURL(previewUrls.value[field]);
        delete previewUrls.value[field];
    }

    const input = document.getElementById(field) as HTMLInputElement | null;
    if (input) input.value = '';

    identityForm[field] = null;
}

function submitIdentity(): void {
    const data = new FormData();
    data.append('_method', 'PUT');
    data.append('app_name', identityForm.app_name ?? '');

    for (const field of ['logo_light', 'logo_dark', 'favicon'] as const) {
        const val = identityForm[field];
        if (val instanceof File) {
            data.append(field, val as File);
        }
    }

    router.post(updateIdentity().url, data, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Identidade atualizada com sucesso.');
            router.reload({ preserveScroll: true });
        },
        onError: (errors) => {
            // Log dos erros específicos
            console.error('Identity update errors:', errors);

            // Montar mensagem detalhada
            const errorMessages = Object.entries(errors)
                .map(
                    ([field, messages]) =>
                        `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`,
                )
                .join('\n');

            toast.error(
                `Erro ao atualizar:\n${errorMessages || 'Verifique os campos e tente novamente.'}`,
            );
        },
    });
}

function submitSecurity(): void {
    securityForm.put(updateSecurity().url, {
        onSuccess: () =>
            toast.success('Configurações de segurança atualizadas.'),
        onError: () => toast.error('Verifique os campos e tente novamente.'),
    });
}

function submitEmail(): void {
    emailForm.put(updateEmail().url, {
        onSuccess: () => toast.success('Configurações de e-mail atualizadas.'),
        onError: () => toast.error('Verifique os campos e tente novamente.'),
    });
}

function submitLgpd(): void {
    lgpdForm.put(updateLgpd().url, {
        onSuccess: () => toast.success('Configurações LGPD atualizadas.'),
        onError: () => toast.error('Verifique os campos e tente novamente.'),
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações do Sistema" />

        <div class="space-y-6">
            <Heading
                title="Configurações do Sistema"
                description="Gerencie as configurações globais da plataforma"
            />

            <!-- Sem permissão -->
            <div
                v-if="!canManage"
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <div class="mb-4 rounded-full bg-muted/50 p-4">
                    <Shield class="h-8 w-8 text-muted-foreground/50" />
                </div>
                <p class="text-sm font-medium text-foreground">
                    Acesso restrito
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Apenas usuários com permissão
                    <strong>settings_manage</strong> podem acessar esta área.
                </p>
            </div>

            <div v-else class="rounded-md border">
                <!-- Tabs -->
                <div class="flex border-b">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.value
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = tab.value"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <div class="p-6">
                    <!-- Identity -->
                    <form
                        v-if="activeTab === 'identity'"
                        class="space-y-6"
                        @submit.prevent="submitIdentity"
                    >
                        <div class="grid gap-2">
                            <Label for="app_name">Nome do Sistema</Label>
                            <Input
                                id="app_name"
                                v-model="identityForm.app_name"
                                type="text"
                                required
                                maxlength="100"
                                placeholder="Ex: Minha Plataforma"
                            />
                            <InputError
                                :message="identityForm.errors.app_name"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div
                                v-for="field in [
                                    'logo_light',
                                    'logo_dark',
                                    'favicon',
                                ] as const"
                                :key="field"
                                class="grid gap-2"
                            >
                                <Label :for="field">
                                    {{
                                        field === 'logo_light'
                                            ? 'Logo (tema claro)'
                                            : field === 'logo_dark'
                                              ? 'Logo (tema escuro)'
                                              : 'Favicon'
                                    }}
                                </Label>

                                <div
                                    v-if="
                                        !previewUrls[field] &&
                                        identityForm[field]
                                    "
                                    class="flex flex-col items-center gap-2 rounded-md border bg-muted/20 p-4"
                                >
                                    <img
                                        :src="`/storage/${identityForm[field]}`"
                                        alt="Imagem atual"
                                        class="h-20 w-auto object-contain"
                                    />
                                    <span class="text-xs text-muted-foreground"
                                        >Imagem atual</span
                                    >
                                </div>

                                <Input
                                    :id="field"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                    @change="(e) => handleFileChange(field, e)"
                                />

                                <!-- Preview da nova imagem selecionada -->
                                <div
                                    v-if="previewUrls[field]"
                                    class="flex flex-col items-center gap-2 rounded-md border bg-muted/20 p-4"
                                >
                                    <img
                                        :src="previewUrls[field]"
                                        alt="Preview"
                                        class="h-20 w-auto object-contain"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="mt-2 w-full"
                                        @click="removeImage(field)"
                                    >
                                        Remover
                                    </Button>
                                </div>

                                <p
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    JPEG, PNG, WebP ou SVG. Máx 2MB.
                                </p>
                                <InputError
                                    :message="identityForm.errors[field]"
                                />
                            </div>
                        </div>

                        <div
                            class="-mx-6 -mb-6 flex items-center justify-end gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="submit"
                                :disabled="identityForm.processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    identityForm.processing
                                        ? 'Salvando...'
                                        : 'Salvar Identidade'
                                }}
                            </Button>
                        </div>
                    </form>

                    <!-- Security -->
                    <form
                        v-else-if="activeTab === 'security'"
                        class="space-y-6"
                        @submit.prevent="submitSecurity"
                    >
                        <div
                            class="grid grid-cols-1 items-start gap-6 md:grid-cols-3"
                        >
                            <div class="grid gap-2">
                                <Label for="session_timeout">
                                    Tempo de inatividade (minutos)
                                </Label>
                                <Input
                                    id="session_timeout"
                                    v-model.number="
                                        securityForm.session_timeout
                                    "
                                    type="number"
                                    required
                                    min="5"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Sessão encerrada logicamente após
                                    inatividade.
                                </p>
                                <InputError
                                    :message="
                                        securityForm.errors.session_timeout
                                    "
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_expiration_days">
                                    Expiração de senha (dias)
                                </Label>
                                <Input
                                    id="password_expiration_days"
                                    v-model.number="
                                        securityForm.password_expiration_days
                                    "
                                    type="number"
                                    required
                                    min="0"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Força a troca da senha. 0 = desativar.
                                </p>
                                <InputError
                                    :message="
                                        securityForm.errors
                                            .password_expiration_days
                                    "
                                />
                            </div>

                            <div
                                class="relative top-0 flex flex-col gap-2 md:top-6"
                            >
                                <div class="flex h-10 items-center gap-2.5">
                                    <input
                                        id="allow_self_deletion"
                                        v-model="
                                            securityForm.allow_self_deletion
                                        "
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                                    />
                                    <Label
                                        for="allow_self_deletion"
                                        class="cursor-pointer text-sm font-normal"
                                    >
                                        Permitir excluir a própria conta
                                    </Label>
                                </div>
                                <InputError
                                    :message="
                                        securityForm.errors.allow_self_deletion
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="-mx-6 -mb-6 flex items-center justify-end gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="submit"
                                :disabled="securityForm.processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    securityForm.processing
                                        ? 'Salvando...'
                                        : 'Salvar Segurança'
                                }}
                            </Button>
                        </div>
                    </form>

                    <!-- Email -->
                    <form
                        v-else-if="activeTab === 'email'"
                        class="space-y-6"
                        @submit.prevent="submitEmail"
                    >
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="mail_from_name"
                                    >Nome do remetente</Label
                                >
                                <Input
                                    id="mail_from_name"
                                    v-model="emailForm.mail_from_name"
                                    type="text"
                                    required
                                    maxlength="100"
                                    placeholder="Ex: Suporte"
                                />
                                <InputError
                                    :message="emailForm.errors.mail_from_name"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="mail_from_address"
                                    >E-mail do remetente</Label
                                >
                                <Input
                                    id="mail_from_address"
                                    v-model="emailForm.mail_from_address"
                                    type="email"
                                    required
                                    maxlength="150"
                                    placeholder="noreply@empresa.com"
                                />
                                <InputError
                                    :message="
                                        emailForm.errors.mail_from_address
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="-mx-6 -mb-6 flex items-center justify-end gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="submit"
                                :disabled="emailForm.processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    emailForm.processing
                                        ? 'Salvando...'
                                        : 'Salvar E-mail'
                                }}
                            </Button>
                        </div>
                    </form>

                    <!-- LGPD -->
                    <form
                        v-else-if="activeTab === 'lgpd'"
                        class="space-y-6"
                        @submit.prevent="submitLgpd"
                    >
                        <div
                            class="grid grid-cols-1 items-start gap-6 md:grid-cols-3"
                        >
                            <div class="grid gap-2">
                                <Label for="company_name"
                                    >Razão social da organização</Label
                                >
                                <Input
                                    id="company_name"
                                    v-model="lgpdForm.company_name"
                                    type="text"
                                    required
                                    maxlength="200"
                                    placeholder="Ex: Empresa LTDA"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Utilizada nos Termos de Uso como organização
                                    controladora dos dados.
                                </p>
                                <InputError
                                    :message="lgpdForm.errors.company_name"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="dpo_email">E-mail do DPO</Label>
                                <Input
                                    id="dpo_email"
                                    v-model="lgpdForm.dpo_email"
                                    type="email"
                                    required
                                    maxlength="150"
                                    placeholder="dpo@empresa.com"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Encarregado de proteção de dados — exibido
                                    nos Termos de Uso.
                                </p>
                                <InputError
                                    :message="lgpdForm.errors.dpo_email"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="audit_retention_days">
                                    Retenção de auditoria (dias)
                                </Label>
                                <Input
                                    id="audit_retention_days"
                                    v-model.number="
                                        lgpdForm.audit_retention_days
                                    "
                                    type="number"
                                    required
                                    min="30"
                                    class="w-full"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Logs antigos são removidos após este prazo.
                                    Mín 30.
                                </p>
                                <InputError
                                    :message="
                                        lgpdForm.errors.audit_retention_days
                                    "
                                />
                            </div>
                        </div>

                        <div
                            class="-mx-6 -mb-6 flex items-center justify-end gap-4 border-t bg-muted/20 px-6 py-4"
                        >
                            <Button
                                type="submit"
                                :disabled="lgpdForm.processing"
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    lgpdForm.processing
                                        ? 'Salvando...'
                                        : 'Salvar LGPD'
                                }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
