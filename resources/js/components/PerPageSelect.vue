<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

withDefaults(
    defineProps<{
        modelValue: string | number;
        options?: (string | number)[];
    }>(),
    {
        options: () => [10, 20, 30, 40, 50, 'all'],
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

function getLabel(option: string | number): string {
    return option === 'all' ? 'Todos' : String(option);
}
</script>

<template>
    <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <span>Exibir</span>
        <Select
            :default-value="String(modelValue)"
            @update:model-value="emit('update:modelValue', $event)"
        >
            <SelectTrigger class="h-8 w-24">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="option in options"
                    :key="option"
                    :value="String(option)"
                >
                    {{ getLabel(option) }}
                </SelectItem>
            </SelectContent>
        </Select>
        <span>registros</span>
    </div>
</template>
