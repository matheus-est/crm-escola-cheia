<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import ReactivateUserModal from '@/components/ReactivateUserModal.vue';
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
import {
    index,
    create,
    edit,
    store,
    checkEmail as checkEmailRoute,
} from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

interface Role {
    id: number;
    name: string;
}

interface DeletedUserData {
    uuid: string;
    name: string;
    email: string;
}

interface Props {
    roles: Role[];
    errors?: Record<string, string>;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: index().url,
    },
    {
        title: 'Criar',
        href: create().url,
    },
];

const toast = useToast();

const showReactivateModal = ref(false);
const deletedUserData = ref<DeletedUserData | null>(null);
const emailError = ref('');

const handleSuccess = () => {
    toast.success('Usuário criado com sucesso.');
};

const handleError = () => {
    toast.error('Erro ao criar usuário. Verifique os campos.');
};

const handleCancel = () => {
    router.visit(index().url);
};

async function verifyEmail(email: string) {
    if (!email || !email.includes('@')) return;

    emailError.value = '';

    try {
        const url = checkEmailRoute.url({}, { email });
        const { data } = await axios.get<{
            exists: boolean;
            trashed?: boolean;
            uuid?: string;
            name?: string;
        }>(url);

        if (data.exists) {
            if (data.trashed) {
                deletedUserData.value = {
                    uuid: data.uuid ?? '',
                    name: data.name ?? '',
                    email: email,
                };
                showReactivateModal.value = true;
            } else {
                emailError.value = 'Este email já está em uso.';
            }
        }
    } catch (error) {
        console.error('Erro ao verificar email:', error);
    }
}

function handleReactivateSuccess(userData: DeletedUserData) {
    showReactivateModal.value = false;
    router.visit(edit({ user_uuid: userData.uuid }).url);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Criar Usuário" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Criar Usuário" class="pt-8" />
            </div>

            <div class="rounded-md border">
                <Form
                    v-bind="store.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                    @success="handleSuccess"
                    @error="handleError"
                >
                    <div class="space-y-6 p-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="grid gap-2">
                                <Label for="name">Nome</Label>
                                <Input
                                    id="name"
                                    name="name"
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
                                    placeholder="email@exemplo.com"
                                    required
                                    @blur="
                                        verifyEmail(
                                            ($event.target as HTMLInputElement)
                                                .value,
                                        )
                                    "
                                />
                                <InputError
                                    :message="errors.email || emailError"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="role_id">Função</Label>
                                <Select name="role_id" required>
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
                                <InputError :message="errors.role_id" />
                            </div>
                        </div>
                    </div>

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
                            :disabled="processing"
                            class="bg-green-600 text-sm text-white hover:bg-green-700"
                        >
                            Criar Usuário
                        </Button>
                    </div>
                </Form>
            </div>

            <ReactivateUserModal
                v-model:open="showReactivateModal"
                :user-data="deletedUserData"
                @success="handleReactivateSuccess"
            />
        </div>
    </AppLayout>
</template>
