<script setup lang="ts">
import { Form, Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { index, update } from '@/routes/admin/schools';
import {
    store as storeUser,
    destroy as destroyUser,
} from '@/routes/admin/schools/users';
import type { BreadcrumbItem } from '@/types';
import type { School } from '@/types/crm';

interface Role {
    uuid: string;
    name: string;
}

interface AvailableUser {
    uuid: string;
    name: string;
}

const props = defineProps<{
    school: School;
    availableRoles?: Role[];
    allUsers?: AvailableUser[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Escolas', href: index().url },
    { title: 'Editar Escola', href: '#' },
];

const toast = useToast();

const activeTab = ref<'details' | 'users'>('details');

const selectedStatus = ref(props.school.status);

const handleSuccess = () => {
    toast.success('Escola atualizada com sucesso.');
};

const handleError = () => {
    toast.error('Erro ao atualizar escola. Verifique os campos.');
};

const userForm = useForm({
    user_id: '',
    role_id: '',
});

function attachUser() {
    userForm.post(storeUser({ school: props.school.uuid }).url, {
        onSuccess: () => {
            userForm.reset();
        },
    });
}

function detachUser(userUuid: string) {
    router.delete(destroyUser({ school: props.school.uuid, userUuid }).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Editar Escola" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading
                    title="Editar Escola"
                    description="Alterar os dados da escola cadastrada."
                    class="pt-8"
                />
            </div>

            <div class="rounded-md border">
                <!-- Tab navigation -->
                <div class="flex border-b">
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
                        <Building2 class="h-4 w-4" />
                        Dados da Escola
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'users'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'users'"
                    >
                        <Users class="h-4 w-4" />
                        Usuários Vinculados
                    </button>
                </div>

                <!-- Details tab: wrapped in Inertia Form -->
                <Form
                    method="put"
                    :action="update({ school: props.school.uuid }).url"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div v-show="activeTab === 'details'" class="space-y-6 p-6">
                        <!-- Slug warning alert -->
                        <Alert
                            class="border-amber-500/50 bg-amber-50/50 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-500"
                        >
                            <AlertCircle class="h-4 w-4" />
                            <AlertTitle>Atenção</AlertTitle>
                            <AlertDescription>
                                Alterar o slug atual
                                <strong>quebrará automaticamente</strong>
                                quaisquer links do formulário de captação
                                público espalhados por anúncios ou pelo site da
                                escola. Altere apenas se estritamente
                                necessário.
                            </AlertDescription>
                        </Alert>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="cnpj">CNPJ</Label>
                                <Input
                                    id="cnpj"
                                    name="cnpj"
                                    :default-value="props.school.cnpj"
                                    placeholder="00.000.000/0000-00"
                                />
                                <InputError :message="errors.cnpj" />
                            </div>

                            <div class="space-y-2">
                                <Label for="razao_social">Razão Social</Label>
                                <Input
                                    id="razao_social"
                                    name="razao_social"
                                    :default-value="props.school.razao_social"
                                    placeholder="Escola Exemplo"
                                />
                                <InputError :message="errors.razao_social" />
                            </div>

                            <div class="space-y-2">
                                <Label for="slug">Slug da Instância</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    :default-value="props.school.slug"
                                    placeholder="..."
                                    readonly
                                    class="cursor-not-allowed bg-muted"
                                />
                                <p class="text-xs text-muted-foreground">
                                    O slug é usado nas URLs públicas e não pode
                                    ser editado pelo frontend.
                                </p>
                                <InputError :message="errors.slug" />
                            </div>

                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Select name="status" v-model="selectedStatus">
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Selecione..."
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">
                                            Ativo
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Inativo
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="errors.status" />
                            </div>
                        </div>
                    </div>

                    <!-- Action bar (always visible, belongs to the details form) -->
                    <div
                        v-show="activeTab === 'details'"
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="() => router.visit(index().url)"
                            :disabled="processing"
                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Cancelar
                        </Button>

                        <Button
                            type="submit"
                            :disabled="processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            {{
                                processing ? 'Salvando...' : 'Salvar Alterações'
                            }}
                        </Button>
                    </div>
                </Form>

                <!-- Users tab: lives OUTSIDE the Inertia Form to avoid nested <form> -->
                <div v-show="activeTab === 'users'" class="space-y-6 p-6">
                    <!-- Attach user -->
                    <div
                        class="flex flex-wrap items-end gap-4 rounded-md bg-muted/50 p-4"
                    >
                        <div class="min-w-[200px] flex-1 space-y-2">
                            <Label>Usuário</Label>
                            <Select v-model="userForm.user_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Selecione um usuário..."
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in props.allUsers ?? []"
                                        :key="u.uuid"
                                        :value="u.uuid"
                                    >
                                        {{ u.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="userForm.errors.user_id" />
                        </div>

                        <div class="w-48 space-y-2">
                            <Label>Perfil na Escola</Label>
                            <Select v-model="userForm.role_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Perfil..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="r in props.availableRoles ?? []"
                                        :key="r.uuid"
                                        :value="r.uuid"
                                    >
                                        {{ r.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="userForm.errors.role_id" />
                        </div>

                        <Button
                            type="button"
                            :disabled="
                                userForm.processing ||
                                !userForm.user_id ||
                                !userForm.role_id
                            "
                            @click="attachUser"
                        >
                            Vincular
                        </Button>
                    </div>

                    <!-- Linked users table -->
                    <div class="rounded-md border">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Nome</th>
                                    <th class="px-4 py-3 font-medium">
                                        E-mail
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Perfil
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Ação
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="su in props.school.users ?? []"
                                    :key="su.uuid"
                                >
                                    <td class="px-4 py-3">{{ su.name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ su.email }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary ring-1 ring-primary/20 ring-inset"
                                        >
                                            {{ su.role?.name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            class="rounded p-1 text-destructive hover:bg-muted"
                                            title="Remover Vínculo"
                                            @click="detachUser(su.uuid)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        (props.school.users ?? []).length === 0
                                    "
                                >
                                    <td
                                        colspan="4"
                                        class="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Nenhum usuário vinculado a esta escola
                                        ainda.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
