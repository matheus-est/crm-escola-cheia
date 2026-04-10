<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import OpportunityCard from '@/components/opportunities/OpportunityCard.vue';
import { isTerminalStatus, statusLabels } from '@/lib/opportunityStatus';
import { update_status } from '@/routes/tenant/opportunities';
import type {
    KanbanColumn as KanbanColumnType,
    Opportunity,
} from '@/types/crm';

const props = defineProps<{
    status: string;
    column: KanbanColumnType;
    filters: Record<string, string | number | undefined>;
}>();

const items = ref<Opportunity[]>([...props.column.data]);
const sentinel = ref<HTMLDivElement | null>(null);
const loading = ref(false);

watch(
    () => props.column.current_page,
    () => {
        if (props.column.current_page === 1) {
            items.value = [...props.column.data]; // reset
        } else {
            items.value.push(...props.column.data); // append
        }
    },
);

let observer: IntersectionObserver | null = null;

function loadMore(): void {
    if (loading.value || props.column.current_page >= props.column.last_page) {
        return;
    }

    router.reload({
        data: {
            ...props.filters,
            [`page_${props.status}`]: props.column.current_page + 1,
        },
        preserveUrl: true,
        only: ['kanban_columns'],
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
}

function onAdd(event: { newIndex: number }): void {
    const opp = items.value[event.newIndex];
    if (opp === undefined) return;
    router.patch(
        update_status({ opportunity: opp.uuid }).url,
        { status: props.status },
        { preserveUrl: true, only: ['kanban_columns'] },
    );
}

onMounted(() => {
    if (!sentinel.value) return;

    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { threshold: 0.1 },
    );

    observer.observe(sentinel.value);
});

onUnmounted(() => {
    observer?.disconnect();
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <!-- Column header -->
        <div class="flex items-center justify-between px-1 pb-1">
            <h3 class="text-sm font-semibold text-foreground">
                {{
                    statusLabels[props.status as keyof typeof statusLabels] ??
                    props.status
                }}
            </h3>
            <span
                class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
            >
                {{ props.column.total }}
            </span>
        </div>

        <!-- Scrollable card list -->
        <div
            class="flex flex-col gap-2 overflow-y-auto"
            style="max-height: calc(100vh - 220px)"
        >
            <VueDraggable
                v-model="items"
                :group="{
                    name: 'kanban',
                    pull: !isTerminalStatus(status),
                    put: !isTerminalStatus(status),
                }"
                item-key="uuid"
                @add="onAdd"
            >
                <OpportunityCard
                    v-for="item in items"
                    :key="item.uuid"
                    :opportunity="item"
                />
            </VueDraggable>

            <!-- Loading indicator -->
            <div v-if="loading" class="flex justify-center py-2">
                <span
                    class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                ></span>
            </div>

            <!-- Intersection sentinel -->
            <div ref="sentinel" class="h-1" />
        </div>
    </div>
</template>
