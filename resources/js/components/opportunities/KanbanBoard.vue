<script setup lang="ts">
import KanbanColumn from '@/components/opportunities/KanbanColumn.vue';
import type { KanbanColumns } from '@/types/crm';

const props = defineProps<{
    columns: KanbanColumns;
    filters: Record<string, string | number | undefined>;
}>();

const statusOrder = [
    'cadastro_inicial',
    'agendamento',
    'visita',
    'matricula',
    'recusado',
] as const;
</script>

<template>
    <div class="flex gap-4 overflow-x-auto pb-4">
        <div
            v-for="status in statusOrder"
            :key="status"
            class="min-w-[280px] flex-shrink-0"
        >
            <KanbanColumn
                v-if="props.columns[status]"
                :status="status"
                :column="props.columns[status]"
                :filters="props.filters"
            />
        </div>
    </div>
</template>
