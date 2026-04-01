<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index, update } from '@/routes/modules';
import { type BreadcrumbItem } from '@/types';

interface Action {
    id?: number;
    name: string;
    label: string;
    order: number;
    _destroy?: boolean;
}

interface MenuGroup {
    id: number;
    uuid: string;
    name: string;
}

interface Module {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon: string;
    url: string | null;
    description: string | null;
    is_active: boolean;
    show_in_menu: boolean;
    order: number;
    menu_group_id: number | null;
    actions: Action[];
}

interface Props {
    module: Module;
    menuGroups: MenuGroup[];
}

const props = defineProps<Props>();
const toast = useToast();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Módulos', href: index().url },
    { title: 'Editar', href: '#' },
];

const form = useForm({
    name: props.module.name,
    slug: props.module.slug,
    icon: props.module.icon,
    url: props.module.url ?? '',
    description: props.module.description ?? '',
    order: props.module.order,
    menu_group_id: props.module.menu_group_id,
    is_active: props.module.is_active,
    show_in_menu: props.module.show_in_menu,
    actions: props.module.actions.map((a) => ({ ...a })),
});

const visibleActions = () => form.actions.filter((a) => !a._destroy);

const addAction = () => {
    const maxOrder =
        form.actions.length > 0
            ? Math.max(...form.actions.map((a) => a.order))
            : 0;

    form.actions.push({
        name: '',
        label: '',
        order: maxOrder + 1,
    });
};

const removeAction = (index: number) => {
    if (form.actions[index].id) {
        form.actions[index]._destroy = true;
    } else {
        form.actions.splice(index, 1);
    }
};

const handleSubmit = () => {
    form.put(update({ module_uuid: props.module.uuid }).url, {
        onSuccess: () => toast.success('Módulo atualizado com sucesso.'),
        onError: () => toast.error('Erro ao atualizar módulo.'),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar Módulo" />

        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="rounded-md p-2 hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <Heading title="Editar Módulo" />
            </div>

            <div class="rounded-md border">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Linha 1: Nome / Slug / Ícone / URL / Grupo / Ordem / Ativo / Exibir Menu -->
                    <div class="space-y-6 p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-8">
                            <div class="grid gap-2">
                                <Label for="name">Nome</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Nome do módulo"
                                    required
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    placeholder="slug-do-modulo"
                                    required
                                />
                                <InputError :message="form.errors.slug" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="icon">Ícone</Label>
                                <Input
                                    id="icon"
                                    v-model="form.icon"
                                    placeholder="LayoutGrid"
                                    required
                                />
                                <InputError :message="form.errors.icon" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="url">URL</Label>
                                <Input
                                    id="url"
                                    v-model="form.url"
                                    placeholder="/acl/modulo"
                                />
                                <InputError :message="form.errors.url" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="menu_group_id">Grupo</Label>
                                <Select v-model="form.menu_group_id">
                                    <SelectTrigger id="menu_group_id">
                                        <SelectValue
                                            placeholder="Selecione um grupo"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="null">—</SelectItem>
                                        <SelectItem
                                            v-for="group in menuGroups"
                                            :key="group.id"
                                            :value="group.id"
                                        >
                                            {{ group.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="form.errors.menu_group_id"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="order">Ordem</Label>
                                <Input
                                    id="order"
                                    v-model="form.order"
                                    type="number"
                                    class="max-w-24"
                                    required
                                />
                                <InputError :message="form.errors.order" />
                            </div>

                            <!-- Checkboxes ocupam as colunas restantes -->
                            <div
                                class="flex items-center gap-4 pt-6 md:col-span-2"
                            >
                                <Label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.is_active"
                                        class="rounded border-input"
                                    />
                                    Módulo Ativo
                                </Label>

                                <Label
                                    class="flex cursor-pointer items-center gap-2"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form.show_in_menu"
                                        class="rounded border-input"
                                    />
                                    Exibir no Menu
                                </Label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-8">
                            <div class="grid gap-2 md:col-span-8">
                                <Label for="description">Descrição</Label>
                                <Input
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Descrição do módulo"
                                />
                                <InputError
                                    :message="form.errors.description"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 border-t px-6 py-4">
                        <div class="flex items-center justify-between">
                            <Label class="text-base font-medium"
                                >Ações do Módulo</Label
                            >
                        </div>

                        <div class="mt-4">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addAction"
                            >
                                <Plus class="mr-1 h-4 w-4" />
                                Adicionar Ação
                            </Button>
                        </div>

                        <div class="rounded-md border">
                            <table class="w-full">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-sm font-medium"
                                        >
                                            Ordem
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-sm font-medium"
                                        >
                                            Nome
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left text-sm font-medium"
                                        >
                                            Label
                                        </th>
                                        <th
                                            class="px-4 py-2 text-right text-sm font-medium"
                                        >
                                            Ação
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="(action, index) in form.actions"
                                        :key="
                                            action.id
                                                ? `action-${action.id}`
                                                : `new-${index}`
                                        "
                                        :class="{
                                            'opacity-50': action._destroy,
                                        }"
                                    >
                                        <td class="px-4 py-2">
                                            <Input
                                                type="number"
                                                v-model="action.order"
                                                class="w-20"
                                                :disabled="action._destroy"
                                            />
                                        </td>
                                        <td class="px-4 py-2">
                                            <Input
                                                v-model="action.name"
                                                placeholder="acao"
                                                :disabled="action._destroy"
                                            />
                                        </td>
                                        <td class="px-4 py-2">
                                            <Input
                                                v-model="action.label"
                                                placeholder="Label"
                                                :disabled="action._destroy"
                                            />
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="removeAction(index)"
                                            >
                                                <Trash2
                                                    class="h-4 w-4 text-destructive"
                                                />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div
                                v-if="visibleActions().length === 0"
                                class="py-4 text-center text-sm text-muted-foreground"
                            >
                                Nenhuma ação adicionada.
                            </div>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div
                        class="flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
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
                                    ? 'Salvando...'
                                    : 'Salvar Alterações'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
