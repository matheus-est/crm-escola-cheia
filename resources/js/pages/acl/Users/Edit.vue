<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, History, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LoginHistoryTable from '@/components/LoginHistoryTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update, loginAudits as loginAuditsRoute } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Usuários', href: index().url },
    { title: 'Editar', href: '#' },
];

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    role_id: number;
    role_name: string;
}

interface LoginAudit {
    id: number;
    user_id: number | null;
    ip_address: string | null;
    login_type: string | null;
    browser: string | null;
    browser_version: string | null;
    os: string | null;
    device: string | null;
    session_id: string | null;
    logged_in_at: string;
    logged_out_at: string | null;
    success: boolean;
    failure_reason: string | null;
}

interface PaginatedLoginAudits {
    data: LoginAudit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    user: User;
    roles: Role[];
    isCurrentUserMaster: boolean;
    loginAudits?: PaginatedLoginAudits;
    filters?: { per_page: number | 'all' };
}

const props = defineProps<Props>();

const roleUser = ref(String(props.user.role_id));

const { props: pageProps } = usePage();

const permissions = computed(
    () => (pageProps.auth?.permissions as string[]) ?? [],
);
const canViewLoginHistory = computed(() =>
    permissions.value.includes('users_audit_login'),
);

const activeTab = ref<'details' | 'login-history'>('details');

const canChangeRole = computed(() => {
    if (props.isCurrentUserMaster) return true;
    if (props.user.role_name === 'Master') return false;
    return true;
});

const toast = useToast();

const handleSuccess = () => {
    toast.success('Usuário atualizado com sucesso.');
};

const handleError = () => {
    toast.error('Erro ao atualizar usuário. Verifique os campos.');
};

const handleCancel = () => {
    router.visit(index().url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar Usuário" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading class="pt-8" title="Editar Usuário" />
            </div>

            <div class="rounded-md border">
                <div v-if="canViewLoginHistory" class="flex border-b">
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'details'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'details'"
                    >
                        <User class="h-4 w-4" />
                        Dados do Usuário
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'login-history'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'login-history'"
                    >
                        <History class="h-4 w-4" />
                        Histórico de Login
                    </button>
                </div>

                <Form
                    method="put"
                    :action="update({ user_uuid: user.uuid }).url"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div v-show="activeTab === 'details'" class="space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="name">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    :default-value="user.name"
                                    placeholder="Nome completo"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    :default-value="user.email"
                                    placeholder="email@exemplo.com"
                                    required
                                />
                                <InputError :message="errors.email" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="role_id">Função</Label>
                                <Select
                                    v-if="canChangeRole"
                                    name="role_id"
                                    v-model="roleUser"
                                    required
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Selecione uma função"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="role in roles"
                                            :key="role.id"
                                            :value="String(role.id)"
                                        >
                                            {{ role.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-else
                                    :model-value="user.role_name"
                                    disabled
                                />
                                <InputError :message="errors.role_id" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="password"
                                    >Nova Senha (opcional)</Label
                                >
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Deixe vazio para manter a atual"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation"
                                    >Confirmar Nova Senha</Label
                                >
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    placeholder="Confirme a nova senha"
                                />
                                <InputError
                                    :message="errors.password_confirmation"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'login-history'" class="p-6">
                        <LoginHistoryTable
                            v-if="props.loginAudits"
                            :login-audits="props.loginAudits"
                            :filters="props.filters"
                            :login-audits-route="loginAuditsRoute"
                            :user-uuid="props.user.uuid"
                        />
                    </div>

                    <!-- Botões -->
                    <div
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            @click="handleCancel"
                            variant="outline"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Cancelar
                        </Button>

                        <Button
                            type="submit"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                            :disabled="processing"
                        >
                            Salvar Alterações
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
