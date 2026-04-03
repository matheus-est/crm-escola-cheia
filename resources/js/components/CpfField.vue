<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useCpfLookup } from '@/composables/useCpfLookup';
import type { Guardian, Student } from '@/types/crm';

const props = withDefaults(
    defineProps<{
        type: 'student' | 'guardian';
        schoolUuid: string;
        modelValue: string;
        label?: string;
        disabled?: boolean;
    }>(),
    {
        label: 'CPF',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    found: [data: Student | Guardian];
    'not-found': [];
}>();

const found = ref(false);

const { isLoading, error, triggerLookup, reset } = useCpfLookup({
    type: props.type,
    schoolUuid: props.schoolUuid,
    onFound: (data) => {
        found.value = true;
        emit('found', data);
    },
    onNotFound: () => {
        found.value = false;
        emit('not-found');
    },
});

function formatCpf(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
    if (digits.length <= 9)
        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;

    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
}

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    const formatted = formatCpf(target.value);

    target.value = formatted;
    found.value = false;
    emit('update:modelValue', formatted);
    void triggerLookup(formatted);
}

watch(
    () => props.schoolUuid,
    () => {
        reset();
        found.value = false;
    },
);
</script>

<template>
    <div class="space-y-2">
        <Label :for="`cpf-field-${type}`">{{ label }}</Label>

        <div class="relative">
            <Input
                :id="`cpf-field-${type}`"
                :value="modelValue"
                :disabled="disabled"
                placeholder="000.000.000-00"
                maxlength="14"
                autocomplete="off"
                @input="handleInput"
            />

            <span
                class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2"
            >
                <Spinner v-if="isLoading" class="text-muted-foreground" />
                <CheckCircle2 v-else-if="found" class="size-4 text-green-500" />
            </span>
        </div>

        <p v-if="error" class="text-xs text-destructive">
            {{ error }}
        </p>
    </div>
</template>
