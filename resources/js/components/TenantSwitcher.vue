<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store as storeActiveSchool } from '@/routes/active-school';
import type { AuthSchool } from '@/types/auth';

defineProps<{
    schools: AuthSchool[];
    currentSchool: AuthSchool | null;
    isCrossTenant: boolean;
}>();

const processing = ref(false);

function handleSwitch(uuid: string): void {
    processing.value = true;
    router.post(
        storeActiveSchool().url,
        { school_uuid: uuid },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <template v-if="schools.length === 0" />

    <div v-else-if="schools.length === 1" class="flex items-center gap-2">
        <span class="text-sm font-medium">{{ schools[0].nome_fantasia ?? schools[0].razao_social }}</span>
    </div>

    <div v-else>
        <Select
            :model-value="currentSchool?.uuid ?? ''"
            @update:model-value="handleSwitch"
        >
            <SelectTrigger :disabled="processing" class="w-56">
                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                <SelectValue placeholder="Selecionar escola..." />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="school in schools"
                    :key="school.uuid"
                    :value="school.uuid"
                >
                    <span class="flex items-center gap-2">
                        <span class="truncate">{{ school.nome_fantasia ?? school.razao_social }}</span>
                    </span>
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
