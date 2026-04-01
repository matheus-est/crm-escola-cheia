<script setup lang="ts">
import { Shield, Check, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

interface Props {
    modules: Module[];
    modelValue: number[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const localPermissions = ref<number[]>([...props.modelValue]);

watch(
    () => props.modelValue,
    (newVal) => {
        localPermissions.value = [...newVal];
    },
);

const hasPermission = (permissionId: number | null): boolean => {
    if (!permissionId) return false;
    return localPermissions.value.includes(permissionId);
};

const getPermissionId = (module: Module, actionName: string): number | null => {
    const permission = module.permissions.find((p) => p.action === actionName);
    return permission?.id ?? null;
};

const togglePermission = (permissionId: number | null) => {
    if (!permissionId) return;

    const idx = localPermissions.value.indexOf(permissionId);

    if (idx === -1) {
        localPermissions.value.push(permissionId);
    } else {
        localPermissions.value.splice(idx, 1);
    }

    emit('update:modelValue', [...localPermissions.value]);
};

const selectAll = () => {
    const allIds = props.modules.flatMap((m) => m.permissions.map((p) => p.id));
    localPermissions.value = allIds;
    emit('update:modelValue', [...localPermissions.value]);
};

const deselectAll = () => {
    localPermissions.value = [];
    emit('update:modelValue', []);
};

const selectedCount = computed(() => localPermissions.value.length);
const totalCount = computed(() =>
    props.modules.reduce((acc, m) => acc + m.permissions.length, 0),
);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                {{ selectedCount }} de {{ totalCount }} permissões selecionadas
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="selectAll"
                >
                    Selecionar todos
                </button>
                <span class="text-muted-foreground">|</span>
                <button
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="deselectAll"
                >
                    Desmarcar todos
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <div
                v-for="module in modules"
                :key="module.id"
                class="rounded-lg border p-4"
            >
                <div class="mb-3 flex items-center gap-2">
                    <Shield class="h-5 w-5 text-muted-foreground" />
                    <h3 class="font-semibold">{{ module.name }}</h3>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="action in module.actions"
                        :key="action.name"
                        type="button"
                        class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 transition-colors"
                        :class="{
                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400':
                                hasPermission(
                                    getPermissionId(module, action.name),
                                ),
                            'border-border bg-muted text-muted-foreground hover:bg-muted/80':
                                !hasPermission(
                                    getPermissionId(module, action.name),
                                ),
                        }"
                        @click="
                            togglePermission(
                                getPermissionId(module, action.name),
                            )
                        "
                    >
                        <Check
                            v-if="
                                hasPermission(
                                    getPermissionId(module, action.name),
                                )
                            "
                            class="h-4 w-4 text-green-600"
                        />

                        <X v-else class="h-4 w-4" />

                        <span class="text-sm font-medium">
                            {{ action.label }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
