<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

function toggle() {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="h-9 w-9"
        :title="
            resolvedAppearance === 'dark'
                ? 'Mudar para claro'
                : 'Mudar para escuro'
        "
        @click="toggle"
    >
        <Sun v-if="resolvedAppearance === 'dark'" class="h-4 w-4" />
        <Moon v-else class="h-4 w-4" />
    </Button>
</template>
