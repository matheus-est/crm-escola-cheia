<script setup lang="ts">
import { Check, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    password: string;
    show?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
});

const requirements = computed(() => [
    {
        label: 'Mínimo de 8 caracteres',
        valid: props.password.length >= 8,
    },
    {
        label: 'Pelo menos 1 letra maiúscula (A-Z)',
        valid: /[A-Z]/.test(props.password),
    },
    {
        label: 'Pelo menos 1 letra minúscula (a-z)',
        valid: /[a-z]/.test(props.password),
    },
    {
        label: 'Pelo menos 1 número (0-9)',
        valid: /\d/.test(props.password),
    },
    {
        label: 'Pelo menos 1 caractere especial (!@#$%^&*()_+-=[]{}|;:,.<>?])',
        valid: /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(props.password),
    },
]);
</script>

<template>
    <div
        v-if="show || password.length > 0"
        class="space-y-2 rounded-md border bg-card p-3 text-sm"
    >
        <p class="mb-2 font-medium text-card-foreground">
            Requisitos da senha:
        </p>
        <ul class="space-y-1">
            <li
                v-for="(req, index) in requirements"
                :key="index"
                class="flex items-center gap-2"
                :class="
                    req.valid
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-muted-foreground'
                "
            >
                <Check v-if="req.valid" class="h-4 w-4 shrink-0" />
                <X v-else class="h-4 w-4 shrink-0" />
                <span :class="req.valid ? 'font-medium' : ''">{{
                    req.label
                }}</span>
            </li>
        </ul>
    </div>
</template>
