<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Shield,
    Check,
    X,
    AlertTriangle,
    CheckSquare,
    Square,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/roles';
import { update as updatePermissions } from '@/routes/roles/permissions';
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

interface Module {
    id: number;
    name: string;
    slug: string;
    icon: string;
    actions: Action[];
    permissions: Permission[];
}

interface Role {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    is_default: boolean;
    permission_ids: number[];
}

const props = defineProps<{ role: Role; modules: Module[] }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Funções', href: index().url },
    { title: 'Permissões', href: '#' },
];

const toast = useToast();

const roleData = ref<Role>(props.role);
const modules = computed(() => props.modules);

const originalPermissionIds = ref<number[]>([...roleData.value.permission_ids]);
const selectedModuleId = ref<number | null>(modules.value[0]?.id ?? null);

const form = useForm({
    permissions: [...roleData.value.permission_ids],
});

// — Computed —

const hasChanges = computed(() => {
    const curr = [...form.permissions].sort((a, b) => a - b);
    const orig = [...originalPermissionIds.value].sort((a, b) => a - b);
    return JSON.stringify(curr) !== JSON.stringify(orig);
});

const selectedModule = computed(
    () => modules.value.find((m) => m.id === selectedModuleId.value) ?? null,
);

function moduleActiveCount(module: Module): number {
    return module.permissions.filter((p) => form.permissions.includes(p.id))
        .length;
}

function moduleIsAllSelected(module: Module): boolean {
    return (
        module.permissions.length > 0 &&
        module.permissions.every((p) => form.permissions.includes(p.id))
    );
}

function moduleIsPartial(module: Module): boolean {
    const count = moduleActiveCount(module);
    return count > 0 && count < module.permissions.length;
}

// — Helpers de permissão —

function hasPermission(permissionId: number | null): boolean {
    if (!permissionId) return false;
    return form.permissions.includes(permissionId);
}

function getPermissionId(module: Module, actionName: string): number | null {
    return module.permissions.find((p) => p.action === actionName)?.id ?? null;
}

function togglePermission(permissionId: number | null): void {
    if (!permissionId) return;
    const idx = form.permissions.indexOf(permissionId);
    if (idx === -1) {
        form.permissions.push(permissionId);
    } else {
        form.permissions.splice(idx, 1);
    }
}

function toggleAllInModule(module: Module): void {
    const allSelected = moduleIsAllSelected(module);
    module.permissions.forEach((p) => {
        const idx = form.permissions.indexOf(p.id);
        if (allSelected && idx !== -1) {
            form.permissions.splice(idx, 1);
        } else if (!allSelected && idx === -1) {
            form.permissions.push(p.id);
        }
    });
}

// — Navegação com confirmação —

function checkAndNavigate(callback: () => void): void {
    if (!hasChanges.value) {
        callback();
        return;
    }
    Swal.fire({
        title: 'Alterações não salvas',
        text: 'Você tem alterações não salvas. Deseja sair sem salvar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sair sem salvar',
        cancelButtonText: 'Continuar editando',
    }).then((result) => {
        if (result.isConfirmed) callback();
    });
}

function handleBack(): void {
    checkAndNavigate(() => router.visit(index().url));
}

function handleSubmit(): void {
    form.put(updatePermissions({ role_uuid: roleData.value.uuid }).url, {
        onSuccess: () => {
            originalPermissionIds.value = [...form.permissions];
            toast.success('Permissões atualizadas com sucesso.');
        },
        onError: () => {
            toast.error('Erro ao atualizar permissões.');
        },
    });
}

watch(
    () => props.role,
    (newRole) => {
        if (newRole) {
            roleData.value = newRole;
            originalPermissionIds.value = [...newRole.permission_ids];
            form.permissions = [...newRole.permission_ids];
        }
    },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Permissões do Perfil" />

        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <button
                    class="rounded-md p-2 hover:bg-muted"
                    @click="handleBack"
                >
                    <ArrowLeft class="h-5 w-5" />
                </button>
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        {{ roleData.name }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Gerencie as permissões deste perfil por módulo
                    </p>
                </div>
                <div
                    v-if="hasChanges"
                    class="ml-auto flex items-center gap-2 rounded-md bg-yellow-50 px-3 py-1.5 text-sm text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200"
                >
                    <AlertTriangle class="h-4 w-4 shrink-0" />
                    Alterações não salvas
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="flex gap-0 overflow-hidden rounded-md border">
                    <div class="w-52 shrink-0 border-r bg-muted/20">
                        <div class="border-b px-4 py-3">
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Módulos
                            </p>
                        </div>
                        <nav class="space-y-0.5 p-2">
                            <button
                                v-for="module in modules"
                                :key="module.id"
                                type="button"
                                class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm transition-colors"
                                :class="
                                    selectedModuleId === module.id
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-foreground hover:bg-muted'
                                "
                                @click="selectedModuleId = module.id"
                            >
                                <span class="truncate font-medium">{{
                                    module.name
                                }}</span>
                                <span
                                    class="ml-2 shrink-0 rounded-full px-1.5 py-0.5 text-xs font-medium tabular-nums"
                                    :class="
                                        selectedModuleId === module.id
                                            ? 'bg-white/20 text-white'
                                            : moduleIsAllSelected(module)
                                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                                              : moduleIsPartial(module)
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                                                : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ moduleActiveCount(module) }}/{{
                                        module.permissions.length
                                    }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <div class="flex-1 p-6">
                        <template v-if="selectedModule">
                            <div class="mb-5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Shield
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                    <h3 class="font-semibold text-foreground">
                                        {{ selectedModule.name }}
                                    </h3>
                                    <span class="text-sm text-muted-foreground">
                                        —
                                        {{ moduleActiveCount(selectedModule) }}
                                        de
                                        {{ selectedModule.permissions.length }}
                                        ativas
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-muted"
                                    :class="
                                        moduleIsAllSelected(selectedModule)
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : 'text-muted-foreground'
                                    "
                                    @click="toggleAllInModule(selectedModule)"
                                >
                                    <CheckSquare
                                        v-if="
                                            moduleIsAllSelected(selectedModule)
                                        "
                                        class="h-4 w-4"
                                    />
                                    <Square v-else class="h-4 w-4" />
                                    {{
                                        moduleIsAllSelected(selectedModule)
                                            ? 'Desmarcar tudo'
                                            : 'Selecionar tudo'
                                    }}
                                </button>
                            </div>

                            <div
                                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                            >
                                <button
                                    v-for="action in selectedModule.actions"
                                    :key="action.name"
                                    type="button"
                                    class="flex items-center gap-2.5 rounded-lg border px-4 py-3 text-left transition-all"
                                    :class="
                                        hasPermission(
                                            getPermissionId(
                                                selectedModule,
                                                action.name,
                                            ),
                                        )
                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                            : 'border-border bg-background text-muted-foreground hover:bg-muted/50 hover:text-foreground'
                                    "
                                    @click="
                                        togglePermission(
                                            getPermissionId(
                                                selectedModule,
                                                action.name,
                                            ),
                                        )
                                    "
                                >
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                                        :class="
                                            hasPermission(
                                                getPermissionId(
                                                    selectedModule,
                                                    action.name,
                                                ),
                                            )
                                                ? 'bg-emerald-500 dark:bg-emerald-600'
                                                : 'bg-muted'
                                        "
                                    >
                                        <Check
                                            v-if="
                                                hasPermission(
                                                    getPermissionId(
                                                        selectedModule,
                                                        action.name,
                                                    ),
                                                )
                                            "
                                            class="h-3 w-3 text-white"
                                        />
                                        <X
                                            v-else
                                            class="h-3 w-3 text-muted-foreground"
                                        />
                                    </span>
                                    <span
                                        class="text-sm leading-tight font-medium"
                                        >{{ action.label }}</span
                                    >
                                </button>
                            </div>
                        </template>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center py-16 text-center"
                        >
                            <div class="mb-4 rounded-full bg-muted/50 p-4">
                                <Shield
                                    class="h-8 w-8 text-muted-foreground/50"
                                />
                            </div>
                            <p class="text-sm font-medium text-foreground">
                                Nenhum módulo selecionado
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Selecione um módulo na lista ao lado.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="-mx-6 -mb-6 flex items-center justify-between gap-4 border-t bg-muted/20 px-6 py-4"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        :disabled="form.processing"
                        @click="handleBack"
                    >
                        Cancelar
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || !hasChanges"
                        class="bg-green-600 text-sm text-white hover:bg-green-700"
                    >
                        {{
                            form.processing
                                ? 'Salvando...'
                                : 'Salvar Permissões'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
