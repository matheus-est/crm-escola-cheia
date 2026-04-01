<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Settings, Shield } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RolePermissions from '@/components/RolePermissions.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index, store } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';

interface Action {
    name: string;
    label: string;
}

interface Permission {
    id: number;
    name: string;
    action: string;
}

interface ModuleData {
    id: number;
    name: string;
    slug: string;
    icon: string;
    actions: Action[];
    permissions: Permission[];
}

defineProps<{ modules: ModuleData[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Funções', href: index().url },
    { title: 'Criar', href: create().url },
];

const toast = useToast();
const activeTab = ref<'details' | 'permissions'>('details');

const form = useForm({
    name: '',
    description: '',
    permissions: [] as number[],
});

const handleSubmit = () => {
    form.post(store().url, {
        onSuccess: () => {
            toast.success('Perfil criado com sucesso.');
        },
        onError: (errors) => {
            if (errors.permissions) {
                toast.error('Selecione pelo menos uma permissão.');
            } else {
                toast.error('Erro ao criar perfil.');
            }
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Criar Perfil" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <button
                    @click="router.visit(index().url)"
                    class="rounded-md p-2 hover:bg-muted"
                >
                    <ArrowLeft class="h-5 w-5" />
                </button>
                <Heading title="Criar Perfil" />
            </div>

            <div class="rounded-md border">
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
                        <Settings class="h-4 w-4" />
                        Dados
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'permissions'
                                ? 'border-b-2 border-primary text-primary'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'permissions'"
                    >
                        <Shield class="h-4 w-4" />
                        Permissões
                    </button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="handleSubmit" class="space-y-6">
                        <div v-show="activeTab === 'details'" class="space-y-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="grid gap-2">
                                    <Label for="name">Nome</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Nome do Perfil"
                                        required
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="description">Descrição</Label>
                                    <Input
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Descrição do Perfil"
                                    />
                                    <InputError
                                        :message="form.errors.description"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-show="activeTab === 'permissions'">
                            <RolePermissions
                                v-model="form.permissions"
                                :modules="modules"
                            />
                        </div>

                        <InputError
                            :message="form.errors.permissions"
                            class="mt-2"
                        />

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
                                class="bg-green-600 text-sm text-white hover:bg-green-700"
                            >
                                {{
                                    form.processing
                                        ? 'Criando...'
                                        : 'Criar Perfil'
                                }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
